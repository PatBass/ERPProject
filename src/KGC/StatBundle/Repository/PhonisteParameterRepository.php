<?php

namespace KGC\StatBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Class PhonisteParameterRepository.
 */
class PhonisteParameterRepository extends EntityRepository
{
    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function findLastParamtersQB()
    {
        $qb = $this->createQueryBuilder('p');
        $qb->addOrderBy('p.createdAt', 'DESC');
        $qb->setMaxResults(1);

        return $qb;
    }

    /**
     * @return mixed
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findLastParamters()
    {
        $qb = $this->findLastParamtersQB();

        return $qb->getQuery()->getSingleResult();
    }
}
