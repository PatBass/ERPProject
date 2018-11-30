<?php

namespace KGC\RdvBundle\Elastic\Event;

use KGC\RdvBundle\Entity\RDV;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class CsvExporter.
 */
class RdvEvent extends Event
{
    const REFRESH = 'refresh';

    /**
     * @var RDV
     */
    protected $rdv;

    /**
     * @param RDV $rdv
     */
    public function __construct(RDV $rdv)
    {
        $this->rdv = $rdv;
    }

    /**
     * @return RDV
     */
    public function getRdv()
    {
        return $this->rdv;
    }
}
