<?php

namespace KGC\ClientBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class SmsRepository extends EntityRepository
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

    public function getSmsHistoryByRdv($id)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select(['sms', 'h'])
            ->from('KGCClientBundle:Historique', 'h', null)
            ->innerJoin('h.sms', 'sms')
            ->innerJoin('h.rdv', 'rdv')
            ->andWhere('rdv.id = :rdv_id')->setParameter('rdv_id', $id)
            ->addOrderBy('h.createdAt', 'DESC')
            ->setMaxResults(100)
        ;

        return $qb->getQuery()->getResult();
    }

    public function getSmsHistoryByClient($id)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select(['sms', 'c'])
            ->from('KGCSharedBundle:Client', 'c', null)
            ->innerJoin('c.smsSents', 'sms')
            ->andWhere('c.id = :client_id')->setParameter('client_id', $id)
            ->setMaxResults(100)
        ;

        return $qb->getQuery()->getResult();
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
            ->andWhere('m.id = :sms_id')
                ->setParameter('sms_id', $id)
        ;

        return $qb->getQuery()->getSingleResult();
    }
}
