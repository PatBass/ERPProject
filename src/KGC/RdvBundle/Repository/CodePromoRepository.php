<?php

// src/KGC/RdvBundle/Repository/CodePromoRepository.php


namespace KGC\RdvBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * CodePromo Repository.
 *
 * @category EntityRepository
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class CodePromoRepository extends EntityRepository
{
    /**
     * @param bool|null      $active
     * @param \DateTime|null $activedate
     *
     * @return QueryBuilder
     */
    public function findAllQB($active = null, $activedate = null)
    {
        $qb = $this->createQueryBuilder('cp')
                   ->addOrderBy('cp.website', 'ASC')
                   ->addOrderBy('cp.code', 'ASC');
        if ($active === true) {
            if ($activedate === null) {
                $activedate = new \DateTime();
            }
            $qb = $this->addActiveCriteria($qb, $activedate);
        }

        return $qb;
    }

         /**
     * @param array $ids
     * @param bool $active
     * @param \DateTime $activedate
     * @return QuerBuilder
     */
    public function findByIdsQB($ids, $active = null, $activedate = null)
    {
        $qb = $this->createQueryBuilder('cp');
        $qb->where($qb->expr()->in('cp.id', $ids))
            ->addOrderBy('cp.code', 'ASC')
        ;
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
     * @param array $ids
     * @param bool $active
     * @param \DateTime $activedate
     * @return array
     */
    public function findByIds($ids, $active = null, $activedate = null)
    {
        $qb = $this->findByIdsQB($ids, $active, $activedate);

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
        $qb->andWhere('cp.enabled = 1 OR (cp.enabled = 0 AND cp.disabled_date > :date_rdv)')
           ->setParameter('date_rdv', $active_date);

        return $qb;
    }
}
