<?php

// src/KGC/RdvBundle/Repository/MoyenPaiementRepository.php


namespace KGC\RdvBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * MoyenPaiement Repository.
 *
 * @category EntityRepository
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class MoyenPaiementRepository extends EntityRepository
{
    /**
     * @param bool|null      $active
     * @param \DateTime|null $activedate
     *
     * @return QueryBuilder
     */
    public function findAllQB($active = null, $activedate = null)
    {
        $qb = $this->createQueryBuilder('mpm')
                   ->addOrderBy('mpm.libelle', 'ASC');
        if ($active === true) {
            if ($activedate === null) {
                $activedate = new \DateTime();
            }
            $qb = $this->addActiveCriteria($qb, $activedate);
        }

        return $qb;
    }

    /**
     * @param bool      $active
     * @param \DateTime $activedate
     *
     * @return array
     */
    public function findAll($active = null, $activedate = null)
    {
        $qb = $this->findAllQB($active, $activedate);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param bool      $qb
     * @param \DateTime $active_date
     *
     * @return QueryBuilder
     */
    private function addActiveCriteria($qb, \DateTime $active_date)
    {
        $qb->andWhere('mpm.enabled = 1 OR (mpm.enabled = 0 AND mpm.disabled_date > :date)')
           ->setParameter('date', $active_date);

        return $qb;
    }
}
