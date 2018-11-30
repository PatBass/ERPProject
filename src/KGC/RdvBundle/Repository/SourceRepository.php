<?php
// src/KGC/RdvBundle/Repository/SourceRepository.php

namespace KGC\RdvBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Class SourceRepository.
 */
class SourceRepository extends EntityRepository
{
    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function findAllQB($active = null, $activedate = null)
    {
        $qb = $this->createQueryBuilder('s')
            ->addOrderBy('s.label', 'ASC')
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
     * @param array $ids
     * @return QuerBuilder
     */
    public function findByIdsQB($ids)
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where($qb->expr()->in('s.id', $ids))
            ->addOrderBy('s.label', 'ASC');

        return $qb;
    }

    /**
     * @param array $ids
     * @return array
     */
    public function findByIds($ids)
    {
        $qb = $this->findByIdsQB($ids);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array
     */
    public function getSourceLeads()
    {
        $aSources = $this->findAll();
        $sources = [];
        foreach ($aSources as $source) {
            if (!in_array(strtolower($source->getLabel()), ['instagram', 'facebook'])) {
                $sources[] = $source;
            }
        }
        return $sources;
    }

    public function getSourceByAssociationName($label)
    {
        $source = null;
        if (!empty($label)) {
            $sources = $this->getSourceLeads();
            foreach ($sources as $sou) {
                if (is_null($source) && $sou->isSourceAvailable($label)) {
                    $source = $sou;
                }
            }
        }
        return $source;
    }
    
    /**
     * @param QueryBuilder $qb
     * @param \DateTime    $active_date
     *
     * @return QueryBuilder
     */
    private function addActiveCriteria($qb, \DateTime $active_date)
    {
        echo 'active';
        $qb->andWhere('s.enabled = 1 OR (s.enabled = 0 AND s.disabled_date > :date_rdv)')
           ->setParameter('date_rdv', $active_date);

        return $qb;
    }
}
