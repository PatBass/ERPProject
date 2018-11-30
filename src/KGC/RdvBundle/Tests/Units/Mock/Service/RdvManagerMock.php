<?php

namespace KGC\RdvBundle\Tests\Units\Mock\Service;

use KGC\RdvBundle\Entity\RDV;

class RdvManagerMock
{
    /**
     * @inheritdoc
     */
    public function processBillingChanges(RDV $rdv, $suivi = true)
    {
    }
}