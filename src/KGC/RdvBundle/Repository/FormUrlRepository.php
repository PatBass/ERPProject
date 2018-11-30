<?php
// src/KGC/RdvBundle/Repository/FormUrlRepository.php

namespace KGC\RdvBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Class FormUrlRepository.
 */
class FormUrlRepository extends EntityRepository
{
    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function findAll()
    {
        $qb = $this->createQueryBuilder('f')
                   ->leftJoin('f.website', 'w')
                   ->leftJoin('f.source', 's')
                   ->orderBy('s.label', 'ASC')
                   ->addOrderBy('w.libelle', 'ASC')
                   ->addOrderBy('f.label', 'ASC')
        ;
        
        return $qb->getQuery()->getResult();
    }
}
