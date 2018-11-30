<?php

// src/KGC/RdvBundle/Repository/TPERepository.php


namespace KGC\RdvBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use KGC\RdvBundle\Entity\TPE;

/**
 * TPERepository.
 *
 * @category EntityRepository
 */
class TPERepository extends EntityRepository
{
    /**
     * @param bool      $active
     * @param \DateTime $activedate
     *
     * @return QuerBuilder
     */
    public function findAllBackofficeQB($active = null, $activedate = null)
    {
        return $this->findAllQB($active, $activedate)
            ->andWhere('t.availableForBackoffice = 1');
    }

    /**
     * @param bool      $active
     * @param \DateTime $activedate
     *
     * @return QuerBuilder
     */
    public function findAllQB($active = null, $activedate = null)
    {
        $qb = $this->createQueryBuilder('t')
            ->addOrderBy('t.libelle', 'ASC')
        ;
        if ($active === true) {
            $qb = $this->addActiveCriteria($qb, $activedate);
        }

        return $qb;
    }

    public function findAllManualQB($active = null, $activedate = null)
    {
        return $this->findAllQB($active, $activedate)
            ->andWhere('t.paymentGateway IS NULL');
    }

    /**
     * @param bool      $active
     * @param \DateTime $activedate
     *
     * @return array
     */
    public function findAll($active = null, $activedate = null)
    {
        $qb = $this->findAllQB($active, $activedate);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param QueryBuilder $qb
     * @param \DateTime    $active_date
     *
     * @return QueryBuilder
     */
    private function addActiveCriteria($qb, $active_date = null)
    {
        if ($active_date === null) {
                $active_date = new \DateTime();
            }
        $qb->andWhere('t.enabled = 1 OR (t.enabled = 0 AND t.disabled_date > :date_rdv)')
           ->setParameter('date_rdv', $active_date);

        return $qb;
    }

    /**
     * Essaie de trouver le TPE d'après son libelle avec une certaine marge d'erreur sur l'orthographe.
     *
     * @param $libelle
     *
     * @return bool
     */
    public function getApproxi($libelle)
    {
        $tpe_list = $this->findAll();
        $find = false;
        $i = 0;
        while ($i < count($tpe_list) and !$find) {
            $similarity_pct = 0;
            $chaine_test = strtoupper($libelle);
            $tpe_name = strtoupper($tpe_list[$i]->getLibelle());
            similar_text($chaine_test, $tpe_name, $similarity_pct);
            if (number_format($similarity_pct) > 90) {
                $find = $tpe_list[$i];
            }
            if (strpos($tpe_name, $chaine_test) !== false) {
                $find = $tpe_list[$i];
            }
            ++$i;
        }

        return $find;
    }

    /**
     * return available payment gateways.
     *
     * @return array
     */
    public function getAvailablePaymentGatewaysForTchat()
    {
        $result = $this->createQueryBuilder('t')
            ->select(['t.paymentGateway', 't.libelle'])
            ->where('t.paymentGateway IS NOT NULL')
            ->andWhere('t.availableForTchat = 1')
            ->addOrderBy('t.libelle', 'ASC')
            ->getQuery()
            ->getResult();

        $gateways = [];
        foreach ($result as $gatewayName => $row) {
            $gateways[$row['paymentGateway']] = $row['libelle'];
        }

        return $gateways;
    }

    public function findOneByWebsiteReference($websiteReference)
    {
        return $this->createQueryBuilder('t')
            ->select('t')
            ->innerJoin('KGCSharedBundle:Website', 'w', Join::WITH, 't.paymentGateway = w.paymentGateway')
            ->where('w.reference = :reference')
            ->setParameter('reference', $websiteReference)
            ->getQuery()->getOneOrNullResult();
    }

    /**
     * find all tpe having payment gateway
     *
     * @param bool $active
     *
     * @return PersistentCollection
     */
    public function findTpesWithPaymentGateway($active = false)
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.paymentGateway IS NOT NULL')
            ->orderBy('t.paymentGateway', 'ASC');
        if ($active) {
            $qb = $this->addActiveCriteria($qb, new \DateTime);
        }
        return $qb->getQuery()->getResult();
    }

    public function getTpeIdsWithPaymentGateway($active = false)
    {
        $qb = $this->createQueryBuilder('t')
            ->select('t.id')
            ->where('t.paymentGateway IS NOT NULL');
        if ($active) {
            $qb = $this->addActiveCriteria($qb, new \DateTime);
        }
        $rows = $qb->getQuery()->getResult();

        $result = [];
        foreach ($rows as $row) {
            $result[] = $row['id'];
        }
        return $result;
    }

    public function getTpeIdsForPreAuth($active = false)
    {
        $qb = $this->createQueryBuilder('t')
            ->select('t.id')
            ->where('t.paymentGateway IN (:allowed_pg)')
            ->setParameter('allowed_pg', [TPE::PAYMENT_GATEWAY_BE2BILL, TPE::PAYMENT_GATEWAY_KLIKANDPAY, TPE::PAYMENT_GATEWAY_HIPAY, TPE::PAYMENT_GATEWAY_HIPAY_MOTO2, TPE::PAYMENT_GATEWAY_HIPAY_MOTO3]);
        if ($active) {
            $qb = $this->addActiveCriteria($qb, new \DateTime);
        }
        $rows = $qb->getQuery()->getResult();

        $result = [];
        foreach ($rows as $row) {
            $result[] = $row['id'];
        }
        return $result;
    }

    public function getLabelsByGateway()
    {
        $rows = $this->createQueryBuilder('t')
            ->select('t.paymentGateway', 't.libelle')
            ->where('t.paymentGateway IS NOT NULL')
            ->getQuery()->getResult();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['paymentGateway']] = $row['libelle'];
        }

        return $result;
    }

    /**
     * @return QueryBuilder
     */
    public function getTelecollecteTPEQB()
    {
        $qb = $this->createQueryBuilder('t')
                   ->where('t.hasTelecollecte = 1');
        $this->addActiveCriteria($qb);

        return $qb;
    }

    /**
     *
     */
    public function getTelecollecteDefaultTPE()
    {
        return $this->findOneById(1);
    }
}
