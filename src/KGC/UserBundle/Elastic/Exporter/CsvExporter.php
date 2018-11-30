<?php

namespace KGC\UserBundle\Elastic\Exporter;

use Doctrine\ORM\EntityManagerInterface;
use Elastica\Query;
use Elastica\Search;
use Elastica\SearchableInterface;
use Elastica\Type;
use FOS\ElasticaBundle\Transformer\ElasticaToModelTransformerInterface;
use JMS\DiExtraBundle\Annotation as DI;
use KGC\RdvBundle\Elastic\Formatter\FormatterInterface;
use KGC\UserBundle\Elastic\Formatter\ProspectFormatter;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class CsvExporter.
 *
 * @DI\Service("kgc.elastic.prospect.csv_exporter")
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
    protected $elasticaIndexProspect;

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
    protected $elasticaProspectTransformer;

    /**
     * @var FormatterInterface
     */
    protected $prospectFormatter;

    /**
     * @param EntityManagerInterface $entityManager
     * @param SearchableInterface $elasticaIndex
     * @param ElasticaToModelTransformerInterface $elasticaProspectTransformer
     * @param SearchableInterface $elasticaIndexProspect
     * @param FormatterInterface $prospectFormatter
     * @param LoggerInterface $logger
     *
     * @DI\InjectParams({
     *      "entityManager" = @DI\Inject("doctrine.orm.entity_manager"),
     *      "elasticaIndex" = @DI\Inject("fos_elastica.index"),
     *      "elasticaProspectTransformer" = @DI\Inject("kgc.elastic.prospect.transformer"),
     *      "elasticaIndexProspect" = @DI\Inject("fos_elastica.index.kgestion_idx.prospect"),
     *      "prospectFormatter" = @DI\Inject("kgc.elastic.prospect.prospect_formatter"),
     *      "logger" = @DI\Inject("logger"),
     * })
     */
    public function __construct(EntityManagerInterface $entityManager,
                                SearchableInterface $elasticaIndex,
                                ElasticaToModelTransformerInterface $elasticaProspectTransformer,
                                SearchableInterface $elasticaIndexProspect,
                                FormatterInterface $prospectFormatter,
                                LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->elasticaIndex = $elasticaIndex;
        $this->elasticaProspectTransformer = $elasticaProspectTransformer;
        $this->elasticaIndexProspect = $elasticaIndexProspect;
        $this->prospectFormatter = $prospectFormatter;
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

    public function export($prospectSearch, $formatter = null)
    {
        if ($formatter !== null) {
            $this->prospectFormatter = $formatter;
        }
        if (!$prospectSearch instanceof Query) {
            throw new \InvalidArgumentException(
                sprintf('Argument must by of type "\Elastica\Query", "%s" given', gettype($prospectSearch))
            );
        }

        try {
            $exportScan = $this->elasticaIndexProspect->search($prospectSearch, array(
                Search::OPTION_SEARCH_TYPE => Search::OPTION_SEARCH_TYPE_SCAN,
                Search::OPTION_SCROLL => self::DEFAULT_OPTION_SCROLL,
                Search::OPTION_SIZE => self::DEFAULT_OPTION_SIZE,
            ));

            $self = $this;

            $response = new StreamedResponse(function () use ($exportScan, $self) {
                $countProspects = 0;
                $total = $exportScan->getTotalHits();
                $scrollId = $exportScan->getResponse()->getScrollId();

                $handle = fopen('php://output', 'r+');

                //add BOM to fix UTF-8 in Excel
                fputs($handle, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

                fputcsv($handle, array_keys($this->prospectFormatter->getHeaders()), ';');

                while ($countProspects <= $total) {
                    $response = $self->elasticaIndex->search(null, array(
                        Search::OPTION_SCROLL_ID => $scrollId,
                        Search::OPTION_SCROLL => self::DEFAULT_OPTION_SCROLL,
                    ));

                    $scrollId = $response->getResponse()->getScrollId();
                    $prospects = $response->getResults();

                    if (empty($prospects)) {
                        break;
                    }

                    $prospects = $self->elasticaProspectTransformer->transform($prospects);

                    foreach ($prospects as $prospect) {
                        $formatted = $self->prospectFormatter->format($prospect);
                        fputcsv($handle, $formatted, ';');
                        ++$countProspects;
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
