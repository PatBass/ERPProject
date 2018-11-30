<?php

namespace KGC\RdvBundle\GclidExporter;

interface ExporterInterface
{
    public function export(array $config = []);
    public function getResponse();
}
