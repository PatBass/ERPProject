<?php

namespace KGC\ChatBundle\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\Bundle\SharedBundle\Entity\Website;
use KGC\ChatBundle\Entity\ChatFormulaRate;
use KGC\ChatBundle\Entity\ChatPayment;
use KGC\ChatBundle\Entity\ChatPromotion;

use KGC\ChatBundle\Entity\ChatType;
use KGC\StatBundle\Calculator\ChatCalculator;
use KGC\StatBundle\Form\AboColumnType;

class ChatPaymentRepository extends EntityRepository
{
    /**
     * Get oldest payment available not empty (not fully consumed).
     *
     * @param Client   $client
     * @param ChatType $chatType
     * @param Website  $website
     *
     * @return ChatPayment or null if not found
     */
    public function getOldestNotEmptyPayment(Client $client, ChatType $chatType, Website $website)
    {
        $not_empty_payments = $this->getNotEmptyPayments($client, $chatType, $website);
        // getNotEmptyPayments will return results order by date, oldest first
        if (count($not_empty_payments) > 0) {
            return $not_empty_payments[0];
        }

        return;
    }

    /**
     * Get not empty payments.
     *
     * @param paramType $param
     *
     * @return returnType
     */
    public function getNotEmptyPayments(Client $client, ChatType $chatType, Website $website)
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
                    ->select('cp', 'crc', 'cfr')
                    ->from('KGCChatBundle:ChatPayment', 'cp')
                    ->leftJoin('cp.chatRoomConsumptions', 'crc')
                    ->join('cp.chatFormulaRate', 'cfr')
                    ->join('cfr.chatFormula', 'cf', 'WITH', 'cf.chatType = :chatType')
                    ->where('cp.client = :client')
                    ->andWhere('cf.website = :website')
                    ->andWhere('cp.state = :payment_state')
                    ->setParameter('client', $client)
                    ->setParameter('chatType', $chatType)
                    ->setParameter('website', $website)
                    ->setParameter('payment_state', ChatPayment::STATE_DONE)
                    ->orderBy('cp.date')
                    ;

        $not_empties = array();
        foreach ($qb->getQuery()->getResult() as $cp) {
            if ($cp->getRemainingUnits() > 0) {
                $not_empties[] = $cp;
            }
        }

        return $not_empties;
    }

    /**
     * Find all chat payments by client and website.
     *
     * @param Client  $client
     * @param Website $website
     *
     * @return array
     */
    public function findByClientAndWebsite(Client $client, Website $website)
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
                    ->select('cp', 'cfr')
                    ->from('KGCChatBundle:ChatPayment', 'cp')
                    ->join('cp.chatFormulaRate', 'cfr')
                    ->join('cfr.chatFormula', 'cf')
                    ->where('cp.client = :client')
                    ->andWhere('cf.website = :website')
                    ->setParameter('client', $client)
                    ->setParameter('website', $website)
                    ->orderBy('cp.date')
                    ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Client
     *
     * @return array
     */
    public function findByClientWithChatFormulaRate(Client $client)
    {
        return $this->createQueryBuilder('cp')
            ->select('cp', 'cfr')
            ->join('cp.chatFormulaRate', 'cfr')
            ->where('cp.client = :client')
            ->setParameter('client', $client)
            ->getQuery()->getResult();
    }

    /**
     * Find last chat payment by client and website.
     *
     * @param Client  $client
     * @param Website $website
     *
     * @return ChatPayment
     */
    public function findLastBySubscription(Client $client, Website $website)
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('cp')
            ->from('KGCChatBundle:ChatPayment', 'cp')
            ->join('cp.chatFormulaRate', 'cfr')
            ->join('cfr.chatFormula', 'cf')
            ->where('cp.client = :client')
            ->andWhere('cf.website = :website')
            ->setParameter('client', $client)
            ->setParameter('website', $website)
            ->orderBy('cp.date', Criteria::DESC)
            ->setMaxResults(1)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param $websiteId
     * @param $email
     * @param null $roomId
     *
     * @return array
     */
    public function findByClientAndWebsiteForStat($websiteId, $email, $onlyActives = true)
    {
        $qb = $this->createQueryBuilder('p')
            ->innerJoin('p.chatFormulaRate', 'fr')->addSelect('fr')
            ->innerJoin('fr.chatFormula', 'f')->addSelect('f')
            ->innerJoin('p.client', 'client')
            ->innerJoin('f.website', 'website')
            ->leftJoin('p.chatRoomConsumptions', 'cc')->addSelect('cc')
            ->andWhere('client.email = :client_email')->setParameter('client_email', $email)
            ->andWhere('website.id = :website_id')->setParameter('website_id', $websiteId)
            ->orderBy('p.date');

        if ($onlyActives) {
            $qb->andWhere('p.state = :state')->setParameter('state', ChatPayment::STATE_DONE);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get client  payments statistics.
     *
     * @param Client  $client
     * @param Website $website
     *
     * @return array
     */
    public function getClientPaymentsStatistics(Client $client, Website $website)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select([
                sprintf('SUM(CASE cfr.type WHEN %d THEN 1 ELSE 0 END) AS free_offer_formulas', ChatFormulaRate::TYPE_FREE_OFFER),
                sprintf('SUM(CASE cfr.type WHEN %d THEN 1 ELSE 0 END) AS discovery_formulas', ChatFormulaRate::TYPE_DISCOVERY),
                sprintf('SUM(CASE cfr.type WHEN %d THEN 1 ELSE 0 END) AS standard_formulas', ChatFormulaRate::TYPE_STANDARD),
                'COUNT(cfr.id) AS total_formulas',
            ])
            ->from('KGCChatBundle:ChatPayment', 'cp')
            ->join('cp.chatFormulaRate', 'cfr')
            ->join('cfr.chatFormula', 'cf')
            ->where('cp.client = :client')
            ->andWhere('cf.website = :website')
            ->groupBy('cp.client')
            ->setParameter('client', $client)
            ->setParameter('website', $website)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param \Datetime    $begin
     * @param \Datetime    $end
     *
     * @return QueryBuilder
     */
    public function getChatDateIntervalTotalTurnoverQB(\Datetime $begin, \Datetime $end)
    {
        return $this->_em->createQueryBuilder()
            ->select('SUM(cp.amount) as total')
            ->from('KGCChatBundle:ChatPayment', 'cp')
            ->innerJoin('cp.chatFormulaRate', 'cfr')
            ->andWhere('cp.date >= :begin AND cp.date < :end')
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
        ;
    }

    /**
     * @param \Datetime    $begin
     * @param \Datetime    $end
     *
     * @return QueryBuilder
     */
    protected function getChatDateIntervalTurnoverQB(\Datetime $begin, \Datetime $end)
    {
        return $this->_em->createQueryBuilder()
            ->select('cp')
            ->from('KGCChatBundle:ChatPayment', 'cp')
            ->innerJoin('cp.chatFormulaRate', 'cfr')
            ->andWhere('cp.date >= :begin AND cp.date < :end')
            ->andWhere('cp.amount > 0')
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
        ;
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return mixed
     */
    public function getChatDateIntervalTotalTurnover(\Datetime $begin, \Datetime $end)
    {
        $qb = $this->getChatDateIntervalTotalTurnoverQB($begin, $end);
        $this->addPaymentDoneFilter($qb, $end);

        return $qb->getQuery()->getSingleScalarResult() / 100;
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return mixed
     */
    public function getChatDateIntervalTurnover(\Datetime $begin, \Datetime $end)
    {
        $qb = $this->getChatDateIntervalTurnoverQB($begin, $end);
        $this->addPaymentDoneFilter($qb, $end);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return QueryBuilder
     */
    protected function addFormulaDiscoverFilter(QueryBuilder $qb)
    {
        return $qb
            ->andWhere('cfr.type = :type')
            ->setParameter('type', ChatFormulaRate::TYPE_DISCOVERY)
        ;
    }

    protected function addFormulaFreeOfferFilter(QueryBuilder $qb)
    {
        return $qb
            ->andWhere('cfr.type = :type')
            ->setParameter('type', ChatFormulaRate::TYPE_FREE_OFFER)
        ;
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return mixed
     */
    public function getChatDateIntervalTotalTurnoverFormulaDiscover(\Datetime $begin, \Datetime $end)
    {
        $qb = $this->getChatDateIntervalTotalTurnoverQB($begin, $end);
        $this->addFormulaDiscoverFilter($qb);
        $this->addPaymentDoneFilter($qb, $end);

        return $qb->getQuery()->getSingleScalarResult() / 100;
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return mixed
     */
    public function getChatDateIntervalTurnoverFormulaDiscover(\Datetime $begin, \Datetime $end)
    {
        $qb = $this->getChatDateIntervalTurnoverQB($begin, $end);
        $this->addFormulaDiscoverFilter($qb);
        $this->addPaymentDoneFilter($qb, $end);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return QueryBuilder
     */
    protected function addFormulaStandardFilter(QueryBuilder $qb)
    {
        return $qb
            ->andWhere('cfr.type IN (:types)')
            ->setParameter('types', [
                ChatFormulaRate::TYPE_STANDARD,
                ChatFormulaRate::TYPE_PREMIUM,
            ])
        ;
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return mixed
     */
    public function getChatDateIntervalTotalTurnoverFormulaStandard(\Datetime $begin, \Datetime $end)
    {
        $qb = $this->getChatDateIntervalTotalTurnoverQB($begin, $end);
        $this->addFormulaStandardFilter($qb);
        $this->addPaymentDoneFilter($qb, $end);

        return $qb->getQuery()->getSingleScalarResult() / 100;
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return mixed
     */
    public function getChatDateIntervalTurnoverFormulaStandard(\Datetime $begin, \Datetime $end)
    {
        $qb = $this->getChatDateIntervalTurnoverQB($begin, $end);
        $this->addFormulaStandardFilter($qb);
        $this->addPaymentDoneFilter($qb, $end);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return QueryBuilder
     */
    protected function addFormulaPlannedFilter(QueryBuilder $qb)
    {
        return $qb
            ->andWhere('cfr.type = :type')
            ->setParameter('type', ChatFormulaRate::TYPE_SUBSCRIPTION)
        ;
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return mixed
     */
    public function getChatDateIntervalTotalTurnoverSubscriptionPlanned(\Datetime $begin, \Datetime $end)
    {
        $qb = $this->getChatDateIntervalTotalTurnoverQB($begin, $end);
        $this->addFormulaPlannedFilter($qb);
        $this->addPaymentDoneFilter($qb, $end);

        return $qb->getQuery()->getSingleScalarResult() / 100;
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return mixed
     */
    public function getChatDateIntervalTurnoverSubscriptionPlanned(\Datetime $begin, \Datetime $end)
    {
        $qb = $this->getChatDateIntervalTurnoverQB($begin, $end);
        $this->addFormulaPlannedFilter($qb);
        $this->addPaymentDoneFilter($qb, $end);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return mixed
     */
    public function getChatDateIntervalTotalTurnoverRecup(\Datetime $begin, \Datetime $end)
    {
        $qb = $this->getChatDateIntervalTotalTurnoverQB($begin, $end)
            ->leftJoin('cp.previousPayment', 'prev')
            ->andWhere('prev.state IN (:refusedState)')
            ->setParameter('refusedState', ChatPayment::getRefusedStates());

        $this->addFormulaPlannedFilter($qb);
        $this->addPaymentDoneFilter($qb, $end);

        return $qb->getQuery()->getSingleScalarResult() / 100;
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return mixed
     */
    public function getChatDateIntervalTurnoverRecup(\Datetime $begin, \Datetime $end)
    {
        $qb = $this->getChatDateIntervalTurnoverQB($begin, $end)
            ->leftJoin('cp.previousPayment', 'prev')
            ->andWhere('prev.state IN (:refusedState)')
            ->setParameter('refusedState', ChatPayment::getRefusedStates());

        $this->addFormulaPlannedFilter($qb);
        $this->addPaymentDoneFilter($qb, $end);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return QueryBuilder
     */
    protected function addPaymentDoneFilter(QueryBuilder $qb, \DateTime $endDate)
    {
        return $qb
            ->andWhere('cp.state = :done_state OR (cp.state IN (:opposedStates))')
            ->setParameter('done_state', ChatPayment::STATE_DONE)
            ->setParameter('opposedStates', ChatPayment::getOpposedStates())
        ;
    }

    protected function getLastSubscriptionPaymentIds(\DateTime $begin, \DateTime $end)
    {
        $qb = $this->getChatDateIntervalTurnoverQB($begin, $end);
        $this->addFormulaPlannedFilter($qb);

        return $qb->select('MAX(cp.id)')
            ->groupBy('cp.chatFormulaRate')
            ->groupBy('cp.client')
            ->getQuery()
            ->getScalarResult();
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return mixed
     */
    public function getChatDateIntervalTotalTurnoverFna(\Datetime $begin, \Datetime $end)
    {
        if ($ids = $this->getLastSubscriptionPaymentIds($begin, $end)) {
            return $this->createQueryBuilder('cp')
                ->select('SUM(cp.amount)')
                ->where('cp.id IN (:ids)')
                ->andWhere('cp.state IN (:refusedState)')
                ->setParameter('ids', $ids)
                ->setParameter('refusedState', ChatPayment::getRefusedStates())
                ->getQuery()
                ->getSingleScalarResult() / 100;
        } else {
            return 0;
        }
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return mixed
     */
    public function getChatDateIntervalTurnoverFna(\Datetime $begin, \Datetime $end)
    {
        if ($ids = $this->getLastSubscriptionPaymentIds($begin, $end)) {
            return $this->createQueryBuilder('cp')
                ->select('cp')
                ->where('cp.id IN (:ids)')
                ->andWhere('cp.state IN (:refusedState)')
                ->setParameter('ids', $ids)
                ->setParameter('refusedState', ChatPayment::getRefusedStates())
                ->getQuery()
                ->getResult();
        } else {
            return [];
        }
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return mixed
     */
    public function getChatDateIntervalTotalTurnoverOppo(\Datetime $begin, \Datetime $end, $opposedState = ChatPayment::STATE_OPPOSED)
    {
        return $this->createQueryBuilder('cp')
            ->select('SUM(cp.amount)')
            ->where('cp.state = :opposedState')
            ->andWhere('cp.opposedDate >= :beginDate')
            ->andWhere('cp.opposedDate < :endDate')
            ->setParameter('opposedState', $opposedState)
            ->setParameter('beginDate', $begin)
            ->setParameter('endDate', $end)
            ->getQuery()
            ->getSingleScalarResult() / 100;
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return mixed
     */
    public function getChatDateIntervalTurnoverOppo(\Datetime $begin, \Datetime $end, $opposedState = ChatPayment::STATE_OPPOSED)
    {
        return $this->createQueryBuilder('cp')
            ->select('cp')
            ->where('cp.state = :opposedState')
            ->andWhere('cp.opposedDate >= :beginDate')
            ->andWhere('cp.opposedDate < :endDate')
            ->setParameter('opposedState', $opposedState)
            ->setParameter('beginDate', $begin)
            ->setParameter('endDate', $end)
            ->orderBy('cp.opposedDate')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return mixed
     */
    public function getCountChatDateIntervalTurnoverOppo(\Datetime $begin, \Datetime $end, $opposedState = ChatPayment::STATE_OPPOSED)
    {
        $qb =  $this->createQueryBuilder('cp')
            ->select('count(cp)')
            ->where('cp.state = :opposedState')
            ->andWhere('cp.opposedDate >= :beginDate')
            ->andWhere('cp.opposedDate < :endDate')
            ->setParameter('opposedState', $opposedState)
            ->setParameter('beginDate', $begin)
            ->setParameter('endDate', $end)
            ->orderBy('cp.opposedDate');
        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return mixed
     */
    public function getChatDateIntervalTotalTurnoverRefund(\Datetime $begin, \Datetime $end)
    {
        return $this->getChatDateIntervalTotalTurnoverOppo($begin, $end, ChatPayment::STATE_REFUNDED);
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return mixed
     */
    public function getChatDateIntervalTurnoverRefund(\Datetime $begin, \Datetime $end)
    {
        return $this->getChatDateIntervalTurnoverOppo($begin, $end, ChatPayment::STATE_REFUNDED);
    }

    /**
     * @param \Datetime    $begin
     * @param \Datetime    $end
     *
     * @return QueryBuilder
     */
    public function getChatDateIntervalCountSubscriptionQB(\Datetime $begin, \Datetime $end)
    {
        return $this->_em->createQueryBuilder()
            ->select('COUNT(cp)')
            ->from('KGCChatBundle:ChatPayment', 'cp')
            ->innerJoin('cp.chatFormulaRate', 'cfr')
            ->andWhere('cp.date >= :begin AND cp.date < :end')
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
        ;
    }

    /**
     * @param \Datetime    $begin
     * @param \Datetime    $end
     *
     * @return QueryBuilder
     */
    public function getChatDateIntervalListSubscriptionQB(\Datetime $begin, \Datetime $end)
    {
        return $this->_em->createQueryBuilder()
            ->select('cp')
            ->from('KGCChatBundle:ChatPayment', 'cp')
            ->innerJoin('cp.chatFormulaRate', 'cfr')
            ->andWhere('cp.date >= :begin AND cp.date < :end')
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
        ;
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return mixed
     */
    public function getChatDateIntervalCountFormulaStandard(\Datetime $begin, \Datetime $end)
    {
        $qb = $this->getChatDateIntervalCountSubscriptionQB($begin, $end);
        $this->addFormulaStandardFilter($qb);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return mixed
     */
    public function getChatDateIntervalFormulasStandard(\Datetime $begin, \Datetime $end)
    {
        $qb = $this->getChatDateIntervalListSubscriptionQB($begin, $end);
        $this->addFormulaStandardFilter($qb);

        return $qb->getQuery()->getResult();
    }

    public function getCountNonConversionsForInterval(\Datetime $begin, \Datetime $end)
    {
        $qb = $this->createQueryBuilder('cp')
            ->select('COUNT(cp.id)')
            ->innerJoin('cp.chatFormulaRate', 'cfr')
            ->leftJoin('KGCChatBundle:ChatPayment', 'cpPaying', Join::WITH, 'cp.client = cpPaying.client AND cp.chatFormulaRate <> cpPaying.chatFormulaRate')
            ->where('cfr.type = :type')
            ->andWhere('cp.date >= :begin AND cp.date < :end')
            ->andWhere('cpPaying.id IS NULL')
            ->setParameter('type', ChatFormulaRate::TYPE_FREE_OFFER)
            ->setParameter('begin', $begin)
            ->setParameter('end', $end);

        $this->addFormulaFreeOfferFilter($qb);

        $freeNonConverted = $qb->getQuery()->getSingleScalarResult();

        $qb = $this->createQueryBuilder('cp')
            ->select('COUNT(cp.id)')
            ->innerJoin('cp.chatFormulaRate', 'cfr')
            ->where('cfr.type = :type')
            ->andWhere('cp.date >= :begin AND cp.date < :end')
            ->setParameter('type', ChatFormulaRate::TYPE_FREE_OFFER)
            ->setParameter('begin', $begin)
            ->setParameter('end', $end);

        $this->addFormulaFreeOfferFilter($qb);

        $freeTotal = $qb->getQuery()->getSingleScalarResult();

        if ($freeTotal > 0) {
            return $freeNonConverted.'/'.$freeTotal;
        } else {
            return '0';
        }
    }

    public function findNonConversionsForInterval(\Datetime $begin, \Datetime $end)
    {
        $qb = $this->createQueryBuilder('cp')
            ->select('cp')
            ->innerJoin('cp.chatFormulaRate', 'cfr')
            ->leftJoin('KGCChatBundle:ChatPayment', 'cpPaying', Join::WITH, 'cp.client = cpPaying.client AND cp.chatFormulaRate <> cpPaying.chatFormulaRate')
            ->where('cfr.type = :type')
            ->andWhere('cp.date >= :begin AND cp.date < :end')
            ->andWhere('cpPaying.id IS NULL')
            ->setParameter('type', ChatFormulaRate::TYPE_FREE_OFFER)
            ->setParameter('begin', $begin)
            ->setParameter('end', $end);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Client $client
     * @param ChatPromotion $promotion
     *
     * @return bool
     */
    public function hasChatPaymentWithChatPromotion(Client $client, ChatPromotion $promotion)
    {
        return $this->createQueryBuilder('cp')
            ->select('COUNT(cp.id)')
            ->where('cp.client = :client')->setParameter('client', $client)
            ->andWhere('cp.promotion = :promotion')->setParameter('promotion', $promotion)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return mixed
     */
    public function getChatStatAbo($type, \Datetime $begin, \Datetime $end, \Datetime $relatif, $column = 'NB')
    {
        switch ($column) {
            case 'NB':
                switch ($type) {
                    case ChatCalculator::ABO_SOUSCRIT:
                        $repo = $this->getEntityManager()->getRepository('KGCChatBundle:ChatSubscription');
                        return $repo->getCountForInterval($begin, $end);
                        break;
                    case ChatCalculator::DESABO:
                        $repo = $this->getEntityManager()->getRepository('KGCChatBundle:ChatSubscription');
                        return $repo->getCountUnsubscriptionsForInterval($begin, $end, $relatif);
                        break;
                    case ChatCalculator::DESACTIVATION:
                        $repo = $this->getEntityManager()->getRepository('KGCChatBundle:ChatSubscription');
                        return $repo->getCountDesactivationsForInterval($begin, $end, $relatif);
                        break;
                    case ChatCalculator::ABO_ACTIF:
                        $repo = $this->getEntityManager()->getRepository('KGCChatBundle:ChatSubscription');
                        return $repo->getCountActifForInterval($begin, $end, $relatif);
                        break;
                    case ChatCalculator::ABO_SOUSCRIT_MP:
                        $relatifMP = clone $relatif;
                        $relatifMP->sub(new \DateInterval('P1M'));
                        $repo = $this->getEntityManager()->getRepository('KGCChatBundle:ChatSubscription');
                        return $repo->getCountActifForInterval($begin, $end, $relatifMP);
                        break;
                    default:
                        return 0;
                        break;
                }
                break;
            case AboColumnType::CODE_CA_REAL:
                switch ($type) {
                    case ChatCalculator::ABO_SOUSCRIT:
                        $repo = $this->getEntityManager()->getRepository('KGCChatBundle:ChatSubscription');
                        return number_format($repo->getTotalReaForInterval($begin, $end), 2, ',', ' ');
                        break;
                    case ChatCalculator::DESABO:
                        $repo = $this->getEntityManager()->getRepository('KGCChatBundle:ChatSubscription');
                        return number_format($repo->getTotalReaUnsubscriptionsForInterval($begin, $end, $relatif), 2, ',', ' ');
                        break;
                    case ChatCalculator::DESACTIVATION:
                        $repo = $this->getEntityManager()->getRepository('KGCChatBundle:ChatSubscription');
                        return number_format($repo->getTotalReaDesactivationsForInterval($begin, $end, $relatif), 2, ',', ' ');
                        break;
                    case ChatCalculator::ABO_ACTIF:
                        $repo = $this->getEntityManager()->getRepository('KGCChatBundle:ChatSubscription');
                        return number_format($repo->getTotalReaActifForInterval($begin, $end, $relatif), 2, ',', ' ');
                        break;
                    case ChatCalculator::ABO_SOUSCRIT_MP:
                        $relatifMP = clone $relatif;
                        $relatifMP->sub(new \DateInterval('P1M'));
                        $repo = $this->getEntityManager()->getRepository('KGCChatBundle:ChatSubscription');
                        return number_format($repo->getTotalReaActifForInterval($begin, $end, $relatifMP), 2, ',', ' ');
                        break;
                    default:
                        return 0;
                        break;
                }
                break;
            case AboColumnType::CODE_CA_ENC:
                switch ($type) {
                    case ChatCalculator::ABO_SOUSCRIT:
                        $repo = $this->getEntityManager()->getRepository('KGCChatBundle:ChatSubscription');
                        return number_format($repo->getTotalEncForInterval($begin, $end), 2, ',', ' ');
                        break;
                    case ChatCalculator::DESABO:
                        $repo = $this->getEntityManager()->getRepository('KGCChatBundle:ChatSubscription');
                        return number_format($repo->getTotalEncUnsubscriptionsForInterval($begin, $end, $relatif), 2, ',', ' ');
                        break;
                    case ChatCalculator::DESACTIVATION:
                        return 0;
                        break;
                    case ChatCalculator::ABO_ACTIF:
                        $repo = $this->getEntityManager()->getRepository('KGCChatBundle:ChatSubscription');
                        return number_format($repo->getTotalEncActifForInterval($begin, $end, $relatif), 2, ',', ' ');
                        break;
                    case ChatCalculator::ABO_SOUSCRIT_MP:
                        $relatifMP = clone $relatif;
                        $relatifMP->sub(new \DateInterval('P1M'));
                        $repo = $this->getEntityManager()->getRepository('KGCChatBundle:ChatSubscription');
                        return number_format($repo->getTotalEncActifForInterval($begin, $end, $relatifMP), 2, ',', ' ');
                        break;
                    default:
                        return 0;
                        break;
                }
                break;
            case AboColumnType::CODE_OPPO:
            case AboColumnType::CODE_REFUND:
                switch ($type) {
                    case ChatCalculator::ABO_SOUSCRIT:
                        $repo = $this->getEntityManager()->getRepository('KGCChatBundle:ChatSubscription');
                        return $repo->getCountForInterval($begin, $end, null, $column);
                        break;
                    case ChatCalculator::DESABO:
                        $repo = $this->getEntityManager()->getRepository('KGCChatBundle:ChatSubscription');
                        return $repo->getCountUnsubscriptionsForInterval($begin, $end, $relatif, $column);
                        break;
                    case ChatCalculator::DESACTIVATION:
                        $repo = $this->getEntityManager()->getRepository('KGCChatBundle:ChatSubscription');
                        return $repo->getCountDesactivationsForInterval($begin, $end, $relatif, $column);
                        break;
                    case ChatCalculator::ABO_ACTIF:
                        $repo = $this->getEntityManager()->getRepository('KGCChatBundle:ChatSubscription');
                        return $repo->getCountActifForInterval($begin, $end, $relatif, $column);
                        break;
                    case ChatCalculator::ABO_SOUSCRIT_MP:
                        $relatifMP = clone $relatif;
                        $relatifMP->sub(new \DateInterval('P1M'));
                        $repo = $this->getEntityManager()->getRepository('KGCChatBundle:ChatSubscription');
                        return $repo->getCountActifForInterval($begin, $end, $relatifMP, $column);
                        break;
                    default:
                        return 0;
                        break;
                }
                break;
            default:
                return 0;
                break;
        }
    }
}
