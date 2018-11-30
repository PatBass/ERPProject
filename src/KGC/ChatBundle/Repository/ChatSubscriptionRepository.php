<?php

namespace KGC\ChatBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr\Join;
use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\Bundle\SharedBundle\Entity\Website;
use KGC\ChatBundle\Entity\ChatPayment;
use KGC\ChatBundle\Entity\ChatSubscription;
use KGC\StatBundle\Form\AboColumnType;

class ChatSubscriptionRepository extends EntityRepository
{
    /**
     * Get chat subscriptions with chat type.
     *
     * @param Client  $client
     * @param Website $website
     *
     * @return array()
     */
    public function findWithChatType(Client $client, Website $website, $active = true)
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('cs', 'cfr', 'cf', 'ct')
            ->from('KGCChatBundle:ChatSubscription', 'cs')
            ->leftJoin('cs.chatFormulaRate', 'cfr')
            ->join('cfr.chatFormula', 'cf')
            ->join('cf.chatType', 'ct')
            ->where('cs.client = :client')
            ->andWhere('cs.website = :website')
            ->groupBy('cs.id')
            ->setParameter('client', $client)
            ->setParameter('website', $website)
            ->orderBy('cs.subscriptionDate');

        if ($active) {
            $qb
                ->leftJoin('cfr.chatPayments', 'cp', Join::WITH, $qb->expr()->andx(
                    $qb->expr()->eq('cp.client', 'cs.client'),
                    $qb->expr()->eq('cp.state', "'".ChatPayment::STATE_DONE."'"),
                    $qb->expr()->gt('cp.date', 'cs.desactivationDate')
                ))
                ->andWhere('cs.desactivationDate IS NULL OR (cs.desactivationDate IS NOT NULL AND cp.date IS NULL) ');
        }

        return $qb->getQuery()->getResult(AbstractQuery::HYDRATE_OBJECT);
    }

    protected function findClientSubscriptionsQueryBuilder($checkedUsers = null)
    {
        $qb = $this->createQueryBuilder('cs')
            ->select('cs.id', 'cfr.id AS cfrId', 'cs.subscriptionDate', 'cs.desactivationDate', 'cs.nextPaymentDate', 'MAX(cp.date) AS lastFailedPaymentDate', 'c.origin')
            ->innerJoin('cs.chatFormulaRate', 'cfr')
            ->innerJoin('cs.client', 'c');

        $qb
            // join with failed payments done after last planned date
            ->leftJoin('cfr.chatPayments', 'cp', Join::WITH, $qb->expr()->andx(
                $qb->expr()->eq('cp.client', 'cs.client'),
                $qb->expr()->eq('cp.state', "'".ChatPayment::STATE_ERROR."'"),
                $qb->expr()->gt('cp.date', 'cs.nextPaymentDate')
            ))
            ->where('cs.nextPaymentDate IS NOT NULL')
            ->groupBy('cs.id');

        if ($checkedUsers !== null) {
            $qb->andWhere($qb->expr()->in('cs.client', $checkedUsers));
        }

        return $qb;
    }

    /**
     * Find chat subscription for which we can process new payments (without taking care of previously failed payments).
     *
     * @param array|null $checkedUsers
     *
     * @return array
     */
    public function findReadySubscriptionsBeforeFailCheck($checkedUsers = null, \DateTime $beforeDt = null)
    {
        $qb = $this->findClientSubscriptionsQueryBuilder($checkedUsers)
            ->orderBy('cs.id', 'DESC');
        if ($beforeDt !== null) {
            $qb->andWhere('cs.nextPaymentDate < :current_date')
                ->setParameter('current_date', $beforeDt);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array $ids
     *
     * @return array
     */
    public function findShortenSubscriptionListByIds($ids)
    {
        $qb = $this->createQueryBuilder('cs');
        return $qb->select('cs.id', 'cfr.type', 'cfr.price')
            ->innerJoin('cs.chatFormulaRate', 'cfr')
            ->where($qb->expr()->in('cs.id', $ids))
            ->getQuery()->getResult();
    }

    public function findSubscriptionsWithFormulaRateByIds($ids)
    {
        $qb = $this->createQueryBuilder('cs');
        return $qb->select('cs', 'cfr', 'c', 'cp')
            ->innerJoin('cs.chatFormulaRate', 'cfr')
            ->innerJoin('cs.client', 'c')
            ->leftJoin('cfr.chatPayments', 'cp', Join::WITH, $qb->expr()->eq('cp.client', 'cs.client'))
            ->where($qb->expr()->in('cs.id', $ids))
            ->getQuery()->getResult();
    }

    public function addFiltrerOpposedOrRefundColumn($qb, $column) {
        switch ($column) {
            case AboColumnType::CODE_OPPO:
                $qb->innerJoin('cs.chatFormulaRate', 'cfr')
                    ->innerJoin('cfr.chatPayments', 'cp')
                    ->andWhere('cp.client = cs.client AND cp.state = :opposedState')
                    ->setParameter('opposedState', ChatPayment::STATE_OPPOSED);
                break;
            case AboColumnType::CODE_REFUND:
                $qb->innerJoin('cs.chatFormulaRate', 'cfr')
                    ->innerJoin('cfr.chatPayments', 'cp')
                    ->andWhere('cp.client = cs.client AND cp.state = :refundedState')
                    ->setParameter('refundedState', ChatPayment::STATE_REFUNDED);
                break;
        }
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     * @param $website
     *
     * @return mixed
     */
    public function getCountForInterval(\Datetime $begin, \Datetime $end, $website = null, $column = null)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('COUNT(DISTINCT(cs))')
            ->from('KGCChatBundle:ChatSubscription', 'cs')
            ->andWhere('cs.subscriptionDate >= :begin AND cs.subscriptionDate < :end')
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
        ;
        if(!is_null($column)) {
            $this->addFiltrerOpposedOrRefundColumn($qb, $column);
        }

        if (null !== $website) {
            $qb
                ->innerJoin('cs.website', 'w')
                ->andWhere('w.reference = :reference')
                ->setParameter('reference', $website)
            ;
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     * @param $website
     *
     * @return mixed
     */
    public function findSubscriptionsForInterval(\Datetime $begin, \Datetime $end, $website = null, $column = null)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('cs')
            ->from('KGCChatBundle:ChatSubscription', 'cs')
            ->andWhere('cs.subscriptionDate >= :begin AND cs.subscriptionDate < :end')
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
        ;

        if(!is_null($column)) {
            $this->addFiltrerOpposedOrRefundColumn($qb, $column);
        }

        if (null !== $website) {
            $qb
                ->innerJoin('cs.website', 'w')
                ->andWhere('w.reference = :reference')
                ->setParameter('reference', $website)
            ;
        }

        return $qb->getQuery()->getResult();
    }


    /**
     * @param \Datetime    $begin
     * @param \Datetime    $end
     *
     * @return QueryBuilder
     */
    public function getTotalEncForInterval(\Datetime $begin, \Datetime $end)
    {
        return $this->_em->createQueryBuilder()
            ->select('SUM(cp.amount) as total')
            ->from('KGCChatBundle:ChatSubscription', 'cs')
            ->innerJoin('cs.chatFormulaRate', 'cfr')
            ->innerJoin('cfr.chatPayments', 'cp')
            ->andWhere('cp.client = cs.client AND cp.state = :done AND cs.subscriptionDate >= :begin AND cs.subscriptionDate < :end')
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('done', ChatPayment::STATE_DONE)
            ->getQuery()
            ->getSingleScalarResult() / 100
            ;
    }



    /**
     * @param \Datetime    $begin
     * @param \Datetime    $end
     *
     * @return QueryBuilder
     */
    public function getTotalReaForInterval(\Datetime $begin, \Datetime $end)
    {
        return $this->_em->createQueryBuilder()
            ->select('SUM(cfr.price) as total')
            ->from('KGCChatBundle:ChatSubscription', 'cs')
            ->innerJoin('cs.chatFormulaRate', 'cfr')
            ->andWhere('cs.subscriptionDate >= :begin AND cs.subscriptionDate < :end')
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult()
            ;
    }


    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return mixed
     */
    public function findUnsubscriptionsForInterval(\Datetime $begin, \Datetime $end, \Datetime $relatif = null, $column = null)
    {
        if(!is_null($relatif)) {
            $relatifBegin = clone $relatif;
            $relatifBegin->modify('first day of this month');
            $relatifEnd = clone $relatifBegin;
            $relatifEnd->add(new \DateInterval('P1M'));
            $qb = $this->_em->createQueryBuilder()
                ->select('cs')
                ->from('KGCChatBundle:ChatSubscription', 'cs')
                ->andWhere('cs.desactivationDate >= :relatifbegin AND cs.desactivationDate < :relatifend AND cs.subscriptionDate >= :begin AND cs.subscriptionDate < :end')
                ->setParameter('relatifbegin', $relatifBegin)
                ->setParameter('relatifend', $relatifEnd)
                ->setParameter('begin', $begin)
                ->setParameter('end', $end)
            ;
        } else {
            $qb = $this->_em->createQueryBuilder()
                ->select('cs')
                ->from('KGCChatBundle:ChatSubscription', 'cs')
                ->andWhere('cs.desactivationDate >= :begin AND cs.desactivationDate < :end')
                ->setParameter('begin', $begin)
                ->setParameter('end', $end)
            ;
        }
        if(!is_null($column)) {
            $this->addFiltrerOpposedOrRefundColumn($qb, $column);
        }
        $qb->addOrderBy('cs.desactivationDate', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return mixed
     */
    public function getCountUnsubscriptionsForInterval(\Datetime $begin, \Datetime $end, $relatif = null, $column = null)
    {
        if(!is_null($relatif)) {
            $relatifBegin = clone $relatif;
            $relatifBegin->modify('first day of this month');
            $relatifEnd = clone $relatifBegin;
            $relatifEnd->add(new \DateInterval('P1M'));
            $qb = $this->_em->createQueryBuilder()
                ->select('COUNT(DISTINCT(cs))')
                ->from('KGCChatBundle:ChatSubscription', 'cs')
                ->andWhere('cs.desactivationDate >= :relatifbegin AND cs.desactivationDate < :relatifend AND cs.subscriptionDate >= :begin AND cs.subscriptionDate < :end')
                ->setParameter('relatifbegin', $relatifBegin)
                ->setParameter('relatifend', $relatifEnd)
                ->setParameter('begin', $begin)
                ->setParameter('end', $end)
            ;
        } else {
            $qb = $this->_em->createQueryBuilder()
                ->select('COUNT(cs)')
                ->from('KGCChatBundle:ChatSubscription', 'cs')
                ->andWhere('cs.desactivationDate >= :begin AND cs.desactivationDate < :end')
                ->setParameter('begin', $begin)
                ->setParameter('end', $end)
            ;
        }


        if(!is_null($column)) {
            $this->addFiltrerOpposedOrRefundColumn($qb, $column);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }


    /**
     * @param \Datetime    $begin
     * @param \Datetime    $end
     *
     * @return QueryBuilder
     */
    public function getTotalReaUnsubscriptionsForInterval(\Datetime $begin, \Datetime $end, \Datetime $relatif)
    {
        $relatifBegin = clone $relatif;
        $relatifBegin->modify('first day of this month');
        $relatifEnd = clone $relatifBegin;
        $relatifEnd->add(new \DateInterval('P1M'));
        return $this->_em->createQueryBuilder()
            ->select('SUM(cfr.price) as total')
            ->from('KGCChatBundle:ChatSubscription', 'cs')
            ->innerJoin('cs.chatFormulaRate', 'cfr')
            ->andWhere('cs.desactivationDate >= :relatifbegin AND cs.desactivationDate < :relatifend AND cs.subscriptionDate >= :begin AND cs.subscriptionDate < :end')
            ->setParameter('relatifbegin', $relatifBegin)
            ->setParameter('relatifend', $relatifEnd)
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult()
            ;
    }

    /**
     * @param \Datetime    $begin
     * @param \Datetime    $end
     *
     * @return QueryBuilder
     */
    public function getTotalEncUnsubscriptionsForInterval(\Datetime $begin, \Datetime $end, \Datetime $relatif)
    {
        $relatifBegin = clone $relatif;
        $relatifBegin->modify('first day of this month');
        $relatifEnd = clone $relatifBegin;
        $relatifEnd->add(new \DateInterval('P1M'));
        return $this->_em->createQueryBuilder()
            ->select('SUM(cp.amount) as total')
            ->from('KGCChatBundle:ChatSubscription', 'cs')
            ->innerJoin('cs.chatFormulaRate', 'cfr')
            ->innerJoin('cfr.chatPayments', 'cp')
            ->andWhere('cp.client = cs.client AND cp.state = :done AND cs.desactivationDate >= :relatifbegin AND cs.desactivationDate < :relatifend AND cs.subscriptionDate >= :begin AND cs.subscriptionDate < :end')
            ->setParameter('relatifbegin', $relatifBegin)
            ->setParameter('relatifend', $relatifEnd)
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('done', ChatPayment::STATE_DONE)
            ->getQuery()
            ->getSingleScalarResult() / 100
            ;
    }


    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return mixed
     */
    public function findDesactivationsForInterval(\Datetime $begin, \Datetime $end, \Datetime $relatif, $column = null)
    {
        $relatifBegin = clone $relatif;
        $relatifBegin->modify('first day of this month');
        $relatifEnd = clone $relatifBegin;
        $relatifEnd->add(new \DateInterval('P1M'));
        $qb = $this->_em->createQueryBuilder()
            ->select('cs')
            ->from('KGCChatBundle:ChatSubscription', 'cs')
            ->andWhere('cs.desactivationDate >= :relatifbegin AND cs.desactivationDate < :relatifend AND cs.disableDate >= :relatifbegin AND cs.disableDate < :relatifend AND cs.disableSource = :source AND cs.subscriptionDate >= :begin AND cs.subscriptionDate < :end')
            ->setParameter('relatifbegin', $relatifBegin)
            ->setParameter('relatifend', $relatifEnd)
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('source', ChatSubscription::SOURCE_CRON)
        ;

        if(!is_null($column)) {
            $this->addFiltrerOpposedOrRefundColumn($qb, $column);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return mixed
     */
    public function getCountDesactivationsForInterval(\Datetime $begin, \Datetime $end, \Datetime $relatif, $column =null)
    {
        $relatifBegin = clone $relatif;
        $relatifBegin->modify('first day of this month');
        $relatifEnd = clone $relatifBegin;
        $relatifEnd->add(new \DateInterval('P1M'));
        $qb = $this->_em->createQueryBuilder()
            ->select('COUNT(DISTINCT(cs))')
            ->from('KGCChatBundle:ChatSubscription', 'cs')
            ->andWhere('cs.desactivationDate >= :relatifbegin AND cs.desactivationDate < :relatifend AND cs.disableDate >= :relatifbegin AND cs.disableDate < :relatifend AND cs.disableSource = :sourceCron AND cs.subscriptionDate >= :begin AND cs.subscriptionDate < :end')
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('relatifbegin', $relatifBegin)
            ->setParameter('relatifend', $relatifEnd)
            ->setParameter('sourceCron', ChatSubscription::SOURCE_CRON)
        ;
        if(!is_null($column)) {
            $this->addFiltrerOpposedOrRefundColumn($qb, $column);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }


    /**
     * @param \Datetime    $begin
     * @param \Datetime    $end
     *
     * @return QueryBuilder
     */
    public function getTotalReaDesactivationsForInterval(\Datetime $begin, \Datetime $end, \Datetime $relatif)
    {
        $relatifBegin = clone $relatif;
        $relatifBegin->modify('first day of this month');
        $relatifEnd = clone $relatifBegin;
        $relatifEnd->add(new \DateInterval('P1M'));
        return $this->_em->createQueryBuilder()
            ->select('SUM(cfr.price) as total')
            ->from('KGCChatBundle:ChatSubscription', 'cs')
            ->innerJoin('cs.chatFormulaRate', 'cfr')
            ->andWhere('cs.desactivationDate >= :relatifbegin AND cs.desactivationDate < :relatifend AND cs.disableDate >= :relatifbegin AND cs.disableDate < :relatifend AND cs.disableSource = :source AND cs.subscriptionDate >= :begin AND cs.subscriptionDate < :end')
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('relatifbegin', $relatifBegin)
            ->setParameter('relatifend', $relatifEnd)
            ->setParameter('source', ChatSubscription::SOURCE_CRON)
            ->getQuery()
            ->getSingleScalarResult()
            ;
    }

    /**
     * @param \Datetime    $begin
     * @param \Datetime    $end
     *
     * @return QueryBuilder
     */
    public function getTotalEncDesactivationsForInterval(\Datetime $begin, \Datetime $end, \Datetime $relatif)
    {
        $relatifBegin = clone $relatif;
        $relatifBegin->modify('first day of this month');
        $relatifEnd = clone $relatifBegin;
        $relatifEnd->add(new \DateInterval('P1M'));
        return $this->_em->createQueryBuilder()
            ->select('SUM(cp.amount) as total')
            ->from('KGCChatBundle:ChatSubscription', 'cs')
            ->innerJoin('cs.chatFormulaRate', 'cfr')
            ->innerJoin('cfr.chatPayments', 'cp')
            ->andWhere('cs.desactivationDate >= :relatifbegin AND cs.desactivationDate < :relatifend AND cp.client = cs.client AND cp.state = :done AND cs.disableDate >= :relatifbegin AND cs.disableDate < :relatifend AND cs.disableSource = :source AND cs.subscriptionDate >= :begin AND cs.subscriptionDate < :end')
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('relatifbegin', $relatifBegin)
            ->setParameter('relatifend', $relatifEnd)
            ->setParameter('done', ChatPayment::STATE_DONE)
            ->setParameter('source', ChatSubscription::SOURCE_CRON)
            ->getQuery()
            ->getSingleScalarResult() / 100
            ;
    }


    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     * @param $website
     *
     * @return mixed
     */
    public function getCountActifForInterval(\Datetime $begin, \Datetime $end, \Datetime $relatif, $column = null, $website = null)
    {
        $relatifBegin = clone $relatif;
        $relatifBegin->modify('first day of this month');
        $relatifEnd = clone $relatifBegin;
        $relatifEnd->add(new \DateInterval('P1M'));
        $qb = $this->_em->createQueryBuilder()
            ->select('COUNT(DISTINCT(cs))')
            ->from('KGCChatBundle:ChatSubscription', 'cs')
            ->andWhere('cs.subscriptionDate >= :begin AND cs.subscriptionDate < :end AND (cs.desactivationDate >= :relatifend OR cs.desactivationDate IS NULL)')
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('relatifend', $relatifEnd)
        ;
        if(!is_null($column)) {
            $this->addFiltrerOpposedOrRefundColumn($qb, $column);
        }

        if (null !== $website) {
            $qb
                ->innerJoin('cs.website', 'w')
                ->andWhere('w.reference = :reference')
                ->setParameter('reference', $website)
            ;
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     * @param $website
     *
     * @return mixed
     */
    public function findActifForInterval(\Datetime $begin, \Datetime $end, \Datetime $relatif, $column = null, $website = null)
    {
        $relatifBegin = clone $relatif;
        $relatifBegin->modify('first day of this month');
        $relatifEnd = clone $relatifBegin;
        $relatifEnd->add(new \DateInterval('P1M'));
        $qb = $this->_em->createQueryBuilder()
            ->select('cs')
            ->from('KGCChatBundle:ChatSubscription', 'cs')
            ->andWhere('cs.subscriptionDate >= :begin AND cs.subscriptionDate < :end AND (cs.desactivationDate >= :relatifend OR cs.desactivationDate IS NULL)')
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('relatifend', $relatifEnd)
        ;

        if(!is_null($column)) {
            $this->addFiltrerOpposedOrRefundColumn($qb, $column);
        }

        if (null !== $website) {
            $qb
                ->innerJoin('cs.website', 'w')
                ->andWhere('w.reference = :reference')
                ->setParameter('reference', $website)
            ;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \Datetime    $begin
     * @param \Datetime    $end
     *
     * @return QueryBuilder
     */
    public function getTotalEncActifForInterval(\Datetime $begin, \Datetime $end, \Datetime $relatif)
    {
        $relatifBegin = clone $relatif;
        $relatifBegin->modify('first day of this month');
        $relatifEnd = clone $relatifBegin;
        $relatifEnd->add(new \DateInterval('P1M'));
        return $this->_em->createQueryBuilder()
            ->select('SUM(cp.amount) as total')
            ->from('KGCChatBundle:ChatSubscription', 'cs')
            ->innerJoin('cs.chatFormulaRate', 'cfr')
            ->innerJoin('cfr.chatPayments', 'cp')
            ->andWhere('cp.date >= :relatifbegin AND cp.date < :relatifend')
            ->andWhere('cp.client = cs.client AND cp.state = :done AND cs.subscriptionDate >= :begin AND cs.subscriptionDate < :end AND (cs.desactivationDate >= :relatifend OR cs.desactivationDate IS NULL)')
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('relatifbegin', $relatifBegin)
            ->setParameter('relatifend', $relatifEnd)
            ->setParameter('done', ChatPayment::STATE_DONE)
            ->getQuery()
            ->getSingleScalarResult() / 100
            ;
    }

    /**
     * @param \Datetime    $begin
     * @param \Datetime    $end
     *
     * @return QueryBuilder
     */
    public function getTotalReaActifForInterval(\Datetime $begin, \Datetime $end, \Datetime $relatif)
    {
        $relatifBegin = clone $relatif;
        $relatifBegin->modify('first day of this month');
        $relatifEnd = clone $relatifBegin;
        $relatifEnd->add(new \DateInterval('P1M'));
        return $this->_em->createQueryBuilder()
            ->select('SUM(cfr.price) as total')
            ->from('KGCChatBundle:ChatSubscription', 'cs')
            ->innerJoin('cs.chatFormulaRate', 'cfr')
            ->andWhere('cs.subscriptionDate >= :begin AND cs.subscriptionDate < :end AND (cs.desactivationDate >= :relatifend OR cs.desactivationDate IS NULL)')
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('relatifend', $relatifEnd)
            ->getQuery()
            ->getSingleScalarResult()
            ;
    }

    /**
     * @param ChatSubscription $subscription
     *
     * @return int
     */
    public function countConsecutiveFails(ChatSubscription $subscription)
    {
        $lastSucceedPaymentId = $this->_em->createQueryBuilder()
            ->select('MAX(cp.id)')
            ->from('KGCChatBundle:ChatPayment', 'cp')
            ->where('cp.client = :client')->setParameter('client', $subscription->getClient())
            ->andWhere('cp.chatFormulaRate = :formulaRate')->setParameter('formulaRate', $subscription->getChatFormulaRate())
            ->andWhere('cp.state <> :errorState')->setParameter('errorState', ChatPayment::STATE_ERROR)
            ->getQuery()->getSingleScalarResult();

        $qb = $this->_em->createQueryBuilder('cp')
            ->select('COUNT(cp.id)')
            ->from('KGCChatBundle:ChatPayment', 'cp')
            ->where('cp.client = :client')->setParameter('client', $subscription->getClient())
            ->andWhere('cp.chatFormulaRate = :formulaRate')->setParameter('formulaRate', $subscription->getChatFormulaRate());
        if ($lastSucceedPaymentId) {
            $qb->andWhere('cp.id > :id')->setParameter('id', $lastSucceedPaymentId);
        }
        return $qb->getQuery()->getSingleScalarResult();
    }
}
