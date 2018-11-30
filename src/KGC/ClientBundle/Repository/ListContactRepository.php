<?php

namespace KGC\ClientBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class ListContactRepository extends EntityRepository
{
    /**
     * @return QueryBuilder
     */
    public function getSimpleListQB($tchat = 0)
    {
        return $this->createQueryBuilder('l')
            ->where("l.tchat = :tchat")
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
        $qb = $this->createQueryBuilder('l')
            ->andWhere('l.id = :list_id')
                ->setParameter('list_id', $id)
        ;

        return $qb->getQuery()->getSingleResult();
    }
}
