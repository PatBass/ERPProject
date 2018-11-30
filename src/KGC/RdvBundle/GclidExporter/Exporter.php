<?php

namespace KGC\RdvBundle\GclidExporter;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

abstract class Exporter
{
    /**
     * @var Response
     */
    protected $response;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param $begin
     * @param $end
     * @param bool|false $wantQB
     *
     * @return mixed
     */
    public function getGclidItemsOrQb($begin, $end, $wantQB = false)
    {
        $method = $wantQB ? 'getGclidExportInfoQB' : 'getGclidExportInfo';

        $endClone = null;
        if ($end) {
            $endClone = clone $end;
            $endClone->add(new \DateInterval('P1D'));
        }

        return $this->entityManager
            ->getRepository('KGCRdvBundle:RDV')
            ->$method($begin, $endClone);
    }

    /**
     * @return string
     */
    protected function generateFileName()
    {
        return sprintf('%s_export_gclid.%s', date('Y-m-d-His', time()), $this->getExtension());
    }

    /**
     * @return array
     */
    protected function getParametersLine()
    {
        $timezone = sprintf('+0%s00', (int) date('Z') / 3600);

        return [
            sprintf('Parameters:EntityType=OFFLINECONVERSION;TimeZone=%s;', $timezone),
        ];
    }

    /**
     * @param $row
     *
     * @return array
     */
    protected function getBusinessLine($row)
    {
        return [
            $row->getGclid(),
            'vente',
            date_format($row->getDateConsultation(), 'm/d/Y H:i:s'),
            $row->getTarification()->getMontantTotal(),
            'EUR',
        ];
    }

    protected function getHeadersLine()
    {
        return [
            'Google Click Id',
            'Conversion Name',
            'Conversion Time',
            'Conversion Value',
            'Conversion Currency',
        ];
    }

    /**
     * @return string
     */
    abstract protected function getExtension();
}
