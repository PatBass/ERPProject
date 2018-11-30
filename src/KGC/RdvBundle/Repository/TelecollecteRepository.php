<?php
// src/KGC/RdvBundle/Repository/TelecollecteRepository.php

namespace KGC\RdvBundle\Repository;

use Doctrine\ORM\EntityRepository;
use KGC\RdvBundle\Entity\TPE;

/**
 * TelecollecteRepository.
 *
 * @category EntityRepository
 */
class TelecollecteRepository extends EntityRepository
{

    /**
     * @param bool $active
     * @param \DateTime $activedate
     * @return array
     */
    public function getByDateAndTpe(\DateTime $begin, \DateTime $end, TPE $tpe)
    {
        $qb = $this->createQueryBuilder('t')
                ->where('t.tpe = :tpe')
                ->setParameter('tpe', $tpe)
                ->andWhere('t.date >= :begin AND t.date < :end')
                ->setParameter('begin', $begin)
                ->setParameter('end', $end)
                ->orderBy('t.date');

        return $qb->getQuery()->getResult();
    }
}
