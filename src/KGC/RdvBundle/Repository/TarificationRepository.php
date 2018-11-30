<?php

// src/KGC/RdvBundle/Repository/TarificationRepository.php


namespace KGC\RdvBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;


/**
 * Class TarificationRepository.
 */
class TarificationRepository extends EntityRepository
{

    /**
     * Called after elastic returns documents to have a single SQL query.
     *
     * @param $entityAlias
     *
     * @return QueryBuilde
     */
    public function createQueryBuilderSearch($entityAlias)
    {
        return $this->createQueryBuilderElastic($entityAlias);
    }

    /**
     * Return base query builder to get a single Tarification or collection.
     *
     * @return QueryBuilder
     */
    protected function createTarificationQB($alias = null)
    {
        $alias = $alias ?: 'tarification';

        $queryBuilder = $this->createQueryBuilder($alias)
            ->select([$alias, 'rdv'])
            ->innerJoin($alias . '.rdv', 'rdv');

        return $queryBuilder;
    }

    /**
     * @param $alias
     *
     * @return QueryBuilder
     */
    protected function createQueryBuilderElastic($alias)
    {
        $queryBuilder = $this->createTarificationQB($alias);

        return $queryBuilder;
    }

    /**
     * Called before elastic indexes documents to have a single SQL query.
     *
     * @param $alias
     * @param null $indexBy
     *
     * @return QueryBuilder
     */
    public function createQueryBuilderIndex($alias, $indexBy = null)
    {
        return $this->createQueryBuilderElastic($alias);
    }
}
