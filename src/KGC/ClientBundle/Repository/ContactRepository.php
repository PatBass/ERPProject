<?php

namespace KGC\ClientBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class ContactRepository extends EntityRepository
{
    /**
     * @return QueryBuilder
     */
    public function getSimpleListQB($listId)
    {
        return $this->createQueryBuilder('c')
            ->where("c.list = :list")
            ->setParameter('list', $listId);
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
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.id = :contact_id')
                ->setParameter('contact_id', $id)
        ;

        return $qb->getQuery()->getSingleResult();
    }
}
