<?php

namespace KGC\ClientBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class CampagneSmsRepository extends EntityRepository
{
    /**
     * @return QueryBuilder
     */
    public function getSimpleListQB($tchat = 0)
    {
        return $this->createQueryBuilder('m')
            ->where("m.tchat = :tchat")
            ->setParameter('tchat', $tchat);
    }

    /**
     * @param $id
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getOneById($id)
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.id = :campagne_id')
                ->setParameter('campagne_id', $id)
        ;

        return $qb->getQuery()->getSingleResult();
    }
}
