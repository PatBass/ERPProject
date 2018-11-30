<?php
// src/KGC/ClientBundle/Repository/MailRepository.php

namespace KGC\ClientBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class MailRepository extends EntityRepository
{
    
    /**
     * @param QueryBuilder $qb
     *
     * @return QueryBuilder
     */
    private function addActiveCriteria($qb)
    {
        $qb->andWhere('m.enabled = 1');

        return $qb;
    }
    
    /**
     * @return QueryBuilder
     */
    public function getSimpleListQB($tchat = 0, $active = true)
    {
        $qb = $this->createQueryBuilder('m')
                   ->where("m.tchat = :tchat")
                   ->setParameter('tchat', $tchat);
        if ($active === true) {
            $qb = $this->addActiveCriteria($qb);
        }
        
        return $qb;
    }

    public function getMailHistoryByRdv($id)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select(['mail', 'h'])
            ->from('KGCClientBundle:Historique', 'h', null)
            ->innerJoin('h.mail', 'mail')
            ->innerJoin('h.rdv', 'rdv')
            ->andWhere('rdv.id = :rdv_id')->setParameter('rdv_id', $id)
            ->addOrderBy('h.createdAt', 'DESC')
            ->setMaxResults(100)
        ;

        return $qb->getQuery()->getResult();
    }

    public function getMailHistoryByClient($id)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select(['mail', 'c'])
            ->from('KGCSharedBundle:Client', 'c', null)
            ->innerJoin('c.mailSents', 'mail')
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
            ->andWhere('m.id = :mail_id')
                ->setParameter('mail_id', $id)
        ;

        return $qb->getQuery()->getSingleResult();
    }
}
