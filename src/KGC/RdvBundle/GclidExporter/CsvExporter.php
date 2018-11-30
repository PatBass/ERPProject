<?php

namespace KGC\RdvBundle\GclidExporter;

use Doctrine\ORM\EntityManagerInterface;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @DI\Service("kgc.rdv.exporter.gclid.csv")
 */
class CsvExporter extends Exporter implements ExporterInterface
{
    /**
     * @return string
     */
    protected function getExtension()
    {
        return 'csv';
    }

    /**
     * @param EntityManagerInterface $entityManager
     *
     * @DI\InjectParams({
     *     "entityManager" = @DI\Inject("doctrine.orm.entity_manager")
     * })
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    /**
     * @param array $config
     */
    public function export(array $config = [])
    {
        $self = $this;
        $begin = $config['begin'];
        $end = $config['end'];

        $response = new StreamedResponse(function () use ($self, $begin, $end) {
            $items = $self->getGclidItemsOrQb($begin, $end, true)->getQuery()->iterate();
            $handle = fopen('php://output', 'r+');
            fputcsv($handle, $this->getParametersLine());
            fputcsv($handle, $this->getHeadersLine());

            while (false !== ($row = $items->next())) {
                $data = $this->getBusinessLine($row[0]);
                fputcsv($handle, $data);
                $self->entityManager->detach($row[0]);
            }
            fclose($handle);
        });

        $filename = $this->generateFileName();
        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
        $this->response = $response;
    }
}
