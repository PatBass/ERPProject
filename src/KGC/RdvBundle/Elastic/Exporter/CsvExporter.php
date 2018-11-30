<?php

namespace KGC\RdvBundle\Elastic\Exporter;

use Doctrine\ORM\EntityManagerInterface;
use Elastica\Query;
use Elastica\Search;
use Elastica\SearchableInterface;
use Elastica\Type;
use FOS\ElasticaBundle\Transformer\ElasticaToModelTransformerInterface;
use JMS\DiExtraBundle\Annotation as DI;
use KGC\RdvBundle\Elastic\Formatter\FormatterInterface;
use KGC\RdvBundle\Elastic\Formatter\RdvFormatter;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class CsvExporter.
 *
 * @DI\Service("kgc.elastic.rdv.csv_exporter")
 */
class CsvExporter implements ExporterInterface
{
    /**
     * Time the scroll is available, scroll cleared on timeout.
     */
    const DEFAULT_OPTION_SCROLL = '30s';

    /**
     * Range to get on every scroll.
     */
    const DEFAULT_OPTION_SIZE = '100';

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var SearchableInterface
     */
    protected $elasticaIndexRdv;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var SearchableInterface
     */
    protected $elasticaIndex;

    /**
     * @var ElasticaToModelTransformerInterface
     */
    protected $elasticaRdvTransformer;

    /**
     * @var FormatterInterface
     */
    protected $rdvFormatter;

    /**
     * @param EntityManagerInterface              $entityManager
     * @param SearchableInterface                 $elasticaIndex
     * @param ElasticaToModelTransformerInterface $elasticaRdvTransformer
     * @param SearchableInterface                 $elasticaIndexRdv
     * @param FormatterInterface                  $rdvFormatter
     * @param LoggerInterface                     $logger
     *
     * @DI\InjectParams({
     *      "entityManager" = @DI\Inject("doctrine.orm.entity_manager"),
     *      "elasticaIndex" = @DI\Inject("fos_elastica.index"),
     *      "elasticaRdvTransformer" = @DI\Inject("kgc.elastic.rdv.transformer"),
     *      "elasticaIndexRdv" = @DI\Inject("fos_elastica.index.kgestion_idx.rdv"),
     *      "rdvFormatter" = @DI\Inject("kgc.elastic.rdv.rdv_formatter"),
     *      "logger" = @DI\Inject("logger"),
     * })
     */
    public function __construct(EntityManagerInterface $entityManager,
        SearchableInterface $elasticaIndex,
        ElasticaToModelTransformerInterface $elasticaRdvTransformer,
        SearchableInterface $elasticaIndexRdv,
        FormatterInterface $rdvFormatter,
        LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->elasticaIndex = $elasticaIndex;
        $this->elasticaRdvTransformer = $elasticaRdvTransformer;
        $this->elasticaIndexRdv = $elasticaIndexRdv;
        $this->rdvFormatter = $rdvFormatter;
        $this->logger = $logger;
    }

    /**
     * @see ExporterInterface::generateExportFileName()
     */
    public function generateExportFileName()
    {
        $basename = 'export';
        $extension = 'csv';
        $suffix = sprintf('_%s', date('Y-m-d-His', time()));
        $filename = sprintf('%s%s.%s', $basename, $suffix, $extension);

        return $filename;
    }

    /**
     * @see ExporterInterface::getResponse()
     */
    public function getResponse()
    {
        return $this->response;
    }

    public function export($rdvSearch, $formatter = null)
    {
        if ($formatter !== null) {
            $this->rdvFormatter = $formatter;
        }
        if (!$rdvSearch instanceof Query) {
            throw new \InvalidArgumentException(
                sprintf('Argument must by of type "\Elastica\Query", "%s" given', gettype($rdvSearch))
            );
        }

        try {
            $exportScan = $this->elasticaIndexRdv->search($rdvSearch, array(
                Search::OPTION_SEARCH_TYPE => Search::OPTION_SEARCH_TYPE_SCAN,
                Search::OPTION_SCROLL => self::DEFAULT_OPTION_SCROLL,
                Search::OPTION_SIZE => self::DEFAULT_OPTION_SIZE,
            ));

            $self = $this;

            $response = new StreamedResponse(function () use ($exportScan, $self) {
                $countRdvs = 0;
                $total = $exportScan->getTotalHits();
                $scrollId = $exportScan->getResponse()->getScrollId();

                $handle = fopen('php://output', 'r+');

                //add BOM to fix UTF-8 in Excel
                fputs($handle, $bom = (chr(0xEF).chr(0xBB).chr(0xBF)));

                fputcsv($handle, array_keys($this->rdvFormatter->getHeaders()), ';');

                while ($countRdvs <= $total) {
                    $response = $self->elasticaIndex->search(null, array(
                        Search::OPTION_SCROLL_ID => $scrollId,
                        Search::OPTION_SCROLL => self::DEFAULT_OPTION_SCROLL,
                    ));

                    $scrollId = $response->getResponse()->getScrollId();
                    $rdvs = $response->getResults();

                    if (empty($rdvs)) {
                        break;
                    }

                    $rdvs = $self->elasticaRdvTransformer->transform($rdvs);

                    foreach ($rdvs as $rdv) {
                        $formatted = $self->rdvFormatter->format($rdv);
                        fputcsv($handle, $formatted, ';');
                        ++$countRdvs;
                    }

                    $self->entityManager->clear();
                }
                fclose($handle);
            });

            $filename = $this->generateExportFileName();
            $response->headers->set('Content-Type', 'application/force-download');
            $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $filename));

            $this->response = $response;

            $this->logger->info(sprintf('CSV EXPORT OK with name: "%s"', $filename));

            return true;
        } catch (\Exception $e) {
            $this->logger->error(sprintf(
                'CSV EXPORT ERROR %s - %s - %s - %s',
                $e->getFile(), $e->getCode(), $e->getMessage()
            ), $e->getTrace());

            return false;
        }
    }
}
