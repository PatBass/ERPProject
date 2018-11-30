<?php

namespace KGC\UserBundle\Elastic\Exporter;

use Symfony\Component\HttpFoundation\Response;

/**
 * Interface ExporterInterface.
 */
interface ExporterInterface
{
    /**
     * @return Response
     */
    public function getResponse();

    /**
     * Generate an export filename with current datetime.
     *
     * @return string
     */
    public function generateExportFileName();

    /**
     * @param $data
     *
     * @return mixed
     */
    public function export($data, $formatter = null);
}
