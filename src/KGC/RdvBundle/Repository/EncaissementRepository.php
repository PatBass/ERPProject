<?php

// src/KGC/RdvBundle/Repository/EncaissementRepository.php


namespace KGC\RdvBundle\Repository;

use Doctrine\ORM\EntityRepository;
use KGC\RdvBundle\Entity\Encaissement;
use KGC\RdvBundle\Entity\MoyenPaiement;
use KGC\PaymentBundle\Entity\Payment;

/**
 * EncaissementRepository.
 *
 * @category Entityrepository
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class EncaissementRepository extends EntityRepository
{
    /**
     * getToDo : Retourne les encaissements prévus.
     *
     *
     * @return EntityRepository
     */
    public function getToDo()
    {
        $queryBuilder = $this->createQueryBuilder('enc')
              ->select(['enc', 'rdv', 'cli'])
              ->innerJoin('enc.consultation', 'rdv')
              ->leftJoin('KGCRdvBundle:Encaissement', 'enc_done', 'WITH', 'enc_done.consultation = enc.consultation AND enc_done.etat != :planned')
              ->innerJoin('rdv.client', 'cli')
              ->where('enc.date <= :now')
              ->andWhere('enc.etat = :planned')
              ->andWhere('enc_done.id IS NULL')
              ->setParameters(['planned' => Encaissement::PLANNED, 'now' => new \DateTime])
              ->orderBy('enc.date')
              ->addOrderBy('cli.nom')
              ->addOrderBy('cli.prenom')
              ->addOrderBy('rdv.dateConsultation', 'DESC')
        ;

        return $queryBuilder->getQuery()->getResult();
    }

    protected function getDoneQB($type, $period, $isEnc = true)
    {
        if ($isEnc) {
            $qb = $this->createQueryBuilder('enc');
        } else {
            $qb = $this->_em->createQueryBuilder()
                ->select('rdv')
                ->from('KGCRdvBundle:Rdv', 'rdv')
                ->innerJoin('rdv.encaissements', 'enc');
        }

        $qb
            ->leftJoin('enc.payment', 'p')
            ->where('enc.etat = :state')
            ->andWhere('enc.date >= :begin')
            ->andWhere('enc.date <= :end')
            ->orderBy('enc.date', 'ASC')
            ->setParameter('state', $type == 'done' ? Encaissement::DONE : Encaissement::DENIED)
            ->setParameter('begin', $period['begin'])
            ->setParameter('end', $period['end']);

        switch ($type) {
            case 'cbi': // for cbi only
                $qb->andWhere('BIT_AND(p.tags, :cbi) > 0')->setParameter('cbi', Payment::TAG_CBI);
            case 'denied': // for both cbi and denied
                $qb->andwhere('enc.batchRetryFrom IS NULL');
                break;
        }

        return $qb;
    }

    /**
     * @param string $type
     * @param array $period
     *
     * @return array
     */
    public function getDone($type, $period)
    {
        return $this->getDoneQB($type, $period)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $type
     * @param array $period
     *
     * @return array
     */
    public function getDoneRdvQuery($type, $period)
    {
        return $this->getDoneQB($type, $period)
            ->groupBy('enc.consultation')
            ->getQuery();
    }

    public function getForBatchUnpaidProcess(\DateTime $date, $limit)
    {
        $begin = clone $date;
        $end = clone $begin;
        $end->modify('+1 day');

        $queryBuilder = $this->createQueryBuilder('enc')
            ->select(['enc', 'rdv', 'cli'])
            ->innerJoin('enc.consultation', 'rdv')
            ->innerJoin('enc.moyenPaiement', 'moyenPaiement')
            ->innerJoin('rdv.client', 'cli')
            ->andWhere('enc.date >= :begin')->setParameter('begin', $begin)
            ->andWhere('enc.date < :end')->setParameter('end', $end)
            ->andWhere('enc.etat = :etat')->setParameter('etat', Encaissement::PLANNED)
            ->andWhere('rdv.batchStatus IS NULL')
            ->andWhere('DATE(enc.date) != DATE(rdv.dateConsultation)')
            ->andWhere('moyenPaiement.idcode = :enc_type')->setParameter(':enc_type', MoyenPaiement::DEBIT_CARD)
            ->orderBy('rdv.dateConsultation', 'DESC')
            ->setMaxResults($limit)
        ;

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Return previous receipt processed by batch for the same consultation
     *
     * @param Encaissement $receipt
     *
     * @return Encaissement
     */
    public function getPreviousReceiptProcessedFromBatch(Encaissement $receipt)
    {
        return $this->createQueryBuilder('enc')
          ->where('enc.consultation = :consultation')
          ->andWhere('enc.fromBatch = 1')
          ->andWhere('enc.etat = :etat_done OR enc.etat = :etat_denied')
          ->orderBy('enc.date', 'DESC')
          ->setMaxResults(1)
          ->setParameter('consultation', $receipt->getConsultation())
          ->setParameter('etat_done', Encaissement::DONE)
          ->setParameter('etat_denied', Encaissement::DENIED)
          ->getQuery()
          ->getOneOrNullResult();
    }
}
