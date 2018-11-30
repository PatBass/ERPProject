<?php

namespace KGC\RdvBundle\Service\Exporter;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use JMS\DiExtraBundle\Annotation as DI;
use KGC\RdvBundle\Elastic\Formatter\FormatterInterface;
use KGC\RdvBundle\Elastic\Exporter\ExporterInterface;
use KGC\RdvBundle\Entity\RDV;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class CsvExporter.
 *
 * @DI\Service("kgc.rdv.csv_exporter")
 */
class CsvExporter implements ExporterInterface
{

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var FormatterInterface
     */
    protected $rdvFormatter;

    /**
     * @param EntityManagerInterface              $entityManager
     * @param FormatterInterface                  $rdvFormatter
     * @param LoggerInterface                     $logger
     *
     * @DI\InjectParams({
     *      "entityManager" = @DI\Inject("doctrine.orm.entity_manager"),
     *      "rdvFormatter" = @DI\Inject("kgc.elastic.rdv.rdv_formatter"),
     *      "logger" = @DI\Inject("logger"),
     * })
     */
    public function __construct(EntityManagerInterface $entityManager,
        FormatterInterface $rdvFormatter,
        LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
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

    public function export($rdvQuery, $formatter = null)
    {
        if ($formatter !== null) {
            $this->rdvFormatter = $formatter;
        }
        if (!$rdvQuery instanceof Query) {
            throw new \InvalidArgumentException(
                sprintf('Argument must by of type "\Doctrine\ORM\Query", "%s" given', gettype($rdvQuery))
            );
        }

        try {
            $response = new StreamedResponse(function () use ($rdvQuery) {
                $handle = fopen('php://output', 'r+');

                //add BOM to fix UTF-8 in Excel
                fputs($handle, $bom = (chr(0xEF).chr(0xBB).chr(0xBF)));

                fputcsv($handle, array_keys($this->rdvFormatter->getHeaders()), ';');

                $iterator = $rdvQuery->iterate();

                while ($row = $iterator->next()) {
                    if($row[0] instanceof RDV){
                        $formatted = $this->rdvFormatter->format($row[0]);
                    }else{
                        $formatted = $this->rdvFormatter->format($row[0]->getConsultation());
                    }
                    fputcsv($handle, $formatted, ';');

                    $this->entityManager->detach($row[0]);
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
