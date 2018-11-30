<?php

namespace KGC\RdvBundle\Elastic\Formatter;

/**
 * Interface FormatterInterface.
 */
interface FormatterInterface
{
    /**
     * @param $data
     *
     * @return mixed
     */
    public function format($data);
}
