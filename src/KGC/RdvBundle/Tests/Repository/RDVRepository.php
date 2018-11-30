<?php

namespace KGC\RdvBundle\Tests\Repository;

use KGC\RdvBundle\Entity\RDV;
use KGC\UserBundle\Entity\Utilisateur;

class RDVRepository
{
    /**
     * @param \DateTime   $debut
     * @param \DateTime   $fin
     * @param Utilisateur $user
     *
     * @return array
     */
    public function getPlannedBetween(\DateTime $debut, \DateTime $fin, Utilisateur $user = null)
    {
        $rdv1 = new RDV(new Utilisateur());
        $rdv1->setDateConsultation(
            new \DateTime(date('Y-m-d ', time()).'17:30')
        );

        $rdv2 = new RDV(new Utilisateur());
        $rdv2->setDateConsultation(
            (new \DateTime(date('Y-m-d ', time()).'17:30'))
                ->add(new \DateInterval('PT30M'))
        );

        $rdv3 = new RDV(new Utilisateur());
        $rdv3->setDateConsultation(
            (new \DateTime(date('Y-m-d ', time()).'17:30'))
                ->add(new \DateInterval('PT5H'))
        );

        return [$rdv1, $rdv2, $rdv3];
    }

    /**
     * @param \DateTime   $h
     * @param Utilisateur $user
     *
     * @return array
     */
    public function getBefore(\DateTime $h, Utilisateur $user = null)
    {
        $rdv = new RDV(new Utilisateur());
        $rdv->setDateConsultation(
            new \DateTime(date('Y-m-d ', time()).'17:00')
        );

        return [$rdv];
    }
}
