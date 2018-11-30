<?php

namespace KGC\ClientBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class HistoriqueRepository extends EntityRepository
{
    /**
     * Build a full query builder for history.
     *
     * @param bool $withSelect
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function createHistoryQB($withSelect = true)
    {
        $qb = $this->createQueryBuilder('h')
            ->innerJoin('h.client', 'c')
            ->innerJoin('h.rdv', 'rdv')
            ->leftJoin('h.option', 'o')
            ->leftJoin('h.options', 'os')
            ->leftJoin('h.pendulum', 'pendulum')
            ->leftJoin('pendulum.question', 'question')
        ;
        if ($withSelect) {
            $qb->addSelect(['h', 'c', 'rdv', 'o', 'os']);
        }

        return $qb;
    }

    /**
     * @param $rdvId
     * @param $clId
     * @param $type
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByRdvAndClientAndType($rdvId, $clId, $type)
    {
        $qb = $this->createHistoryQB()
            ->andWhere('h.type = :type')->setParameter('type', $type)
//            ->andWhere('c.id = :cl_id')->setParameter('cl_id', $clId)
            ->andWhere('rdv.id = :rdv_id')->setParameter('rdv_id', $rdvId)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param QueryBuilder $qb
     * @param array        $typeFilters
     */
    protected function addHistoryTypeFilters(QueryBuilder $qb, array $typeFilters = [])
    {
        if (!empty($typeFilters)) {
            $qb->andWhere('h.type IN (:types)')->setParameter('types', $typeFilters);
        }
    }

    /**
     * @param $id
     * @param array $typeFilters
     *
     * @return array
     */
    public function getHistoryPaginatorConfig($id, array $typeFilters = [])
    {
        $qb = $this->createHistoryQB(false)
            ->select('rdv.id')
            ->andWhere('c.id = :cl_id')->setParameter('cl_id', $id)
            ->addOrderBy('rdv.dateConsultation', 'DESC')
            ->addGroupBy('rdv.id')
        ;

        $this->addHistoryTypeFilters($qb, $typeFilters);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param $id
     * @param $start
     * @param $end
     * @param array $typeFilters
     *
     * @return array
     */
    public function getHistoryPaginatorItems($id, $start, $end, array $typeFilters = [])
    {
        $qb = $this->createHistoryQB()
            ->andWhere('c.id = :cl_id')
                ->setParameter('cl_id', $id)
            ->andWhere('rdv.id <= :start AND rdv.id >= :end')
                ->setParameter('start', $start)
                ->setParameter('end', $end)
            ->addOrderBy('rdv.id', 'DESC')
            ->addOrderBy('rdv.dateConsultation', 'DESC')
        ;

        $this->addHistoryTypeFilters($qb, $typeFilters);

        return $qb->getQuery()->getResult();
    }
}
