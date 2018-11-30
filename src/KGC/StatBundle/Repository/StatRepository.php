<?php
// src/KGC/StatBundle/Repository/StatRepository.php

namespace KGC\StatBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use JMS\DiExtraBundle\Annotation as DI;
use KGC\ChatBundle\Entity\ChatPayment;
use KGC\ChatBundle\Entity\ChatRoom;
use KGC\ChatBundle\Entity\ChatFormulaRate;
use KGC\ClientBundle\Entity\Option;
use KGC\RdvBundle\Entity\ActionSuivi;
use KGC\RdvBundle\Entity\Dossier;
use KGC\RdvBundle\Entity\Encaissement;
use KGC\RdvBundle\Entity\Etat;
use KGC\RdvBundle\Entity\RDV;
use KGC\RdvBundle\Entity\Support;
use KGC\StatBundle\Form\SortingColumnType;
use KGC\StatBundle\Form\StatisticColumnType;
use KGC\StatBundle\Form\StatScopeType;
use KGC\StatBundle\Form\SupportType;
use KGC\StatBundle\Form\CodePromoType;
use KGC\StatBundle\Form\SourceType;
use KGC\StatBundle\Form\UrlType;
use KGC\StatBundle\Form\WebsiteType;
use KGC\UserBundle\Entity\Journal;
use KGC\UserBundle\Entity\Profil;
use KGC\UserBundle\Entity\Utilisateur;


/**
 * Class StatRepository.
 *
 * @DI\Service("kgc.stat.repository")
 */
class StatRepository extends EntityRepository
{
    const SUPPORT_INCLUDE = 'include';
    const SUPPORT_EXCLUDE = 'exclude';

    const FILTER_CANCELLED = 'FILTER_CANCELLED';
    const FILTER_DONE = 'FILTER_DONE';
    const FILTER_10MIN = 'FILTER_10MIN';
    const FILTER_VALID = 'FILTER_VALID';

    const VALIDATED_MIN_AMOUNT = 100; // Oui c'est bizarre, mais cela vient du fait que le champs est un entier.
    const VALIDATED_MIN_TIME = 10;

    /**
     * @param \Doctrine\ORM\EntityManager $em
     * @param ClassMetadata               $class
     *
     * @DI\InjectParams({
     *      "em" = @DI\Inject("doctrine.orm.entity_manager"),
     *      "class" = @DI\Inject("kgc.stat.repository.classmetadata"),
     * })
     */
    public function __construct($em, ClassMetadata $class)
    {
        $this->_entityName = $class->name;
        $this->_em = $em;
        $this->_class = $class;
    }

    /**
     * Filtre les encaissements qui sont passés un jour, donc Faits et en Opposition.
     *
     * @param QueryBuilder $qb
     *
     * @return QueryBuilder
     */
    protected function addEncDone(QueryBuilder $qb)
    {
        $qb->andWhere('enc.etat = :etat_done OR enc.etat = :etat_cancelled OR enc.etat = :etat_refunded')
            ->setParameter('etat_done', Encaissement::DONE)
            ->setParameter('etat_cancelled', Encaissement::CANCELLED)
            ->setParameter('etat_refunded', Encaissement::REFUNDED)
        ;

        return $qb;
    }

    /**
     * Filtre les encaissements qui sont passé, et qui n'ont pas été opposé à la date donnée
     * Ont conserve les encaissements "Done", ceux dont la date d'opposition est ulterieurs la fin de l'interval, et
     * ceux dont la date d'opposition ET de creation est comprise dans l'interval.
     *
     * @param QueryBuilder $qb
     * @param \Datetime    $begin
     * @param \Datetime    $end
     *
     * @return QueryBuilder
     */
    protected function addEncDoneAndNotOpposedYet(QueryBuilder $qb, \Datetime $begin, \Datetime $end)
    {
        $qb->andWhere('
            enc.etat = :etat_done
            OR (
                enc.date_oppo >= :date_end
                OR (
                    enc.date_oppo < :date_end
                    AND enc.date_oppo >= :date_begin
                    AND enc.date < :date_end
                    AND enc.date >= :date_begin
                    )
                )'
        )
            ->setParameter('etat_done', Encaissement::DONE)
            ->setParameter('date_end', $end)
            ->setParameter('date_begin', $begin)
        ;

        return $qb;
    }

    protected function addEncDoneAndNotOpposedThisMonth(QueryBuilder $qb)
    {
        $qb->andWhere('enc.etat = :etat_done OR (enc.etat IN (:etat_cancelled, :etat_refunded) AND enc.date_oppo >= LAST_DAY(historique.date) )')
            ->setParameter('etat_done', Encaissement::DONE)
            ->setParameter('etat_cancelled', Encaissement::CANCELLED)
            ->setParameter('etat_refunded', Encaissement::REFUNDED)
        ;

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @param \Datetime    $begin
     * @param \Datetime    $end
     *
     * @return QueryBuilder
     */
    protected function addContactDateIntervalFilter(QueryBuilder $qb, \Datetime $begin, \Datetime $end, $dateType = StatisticColumnType::DATE_TYPE_HISTORIQUE)
    {
        if ($dateType == StatisticColumnType::DATE_TYPE_HISTORIQUE) {
            $subQuery = $this->_em->createQueryBuilder()
                ->select('max(h.date)')
                ->from('KGCRdvBundle:SuiviRdv', 'h', null)
                ->innerJoin('h.mainaction', 'ma')
                ->andWhere('h.date >= :begin_contact AND h.date < :end_contact')
                ->andWhere('ma.idcode = :add_code')
                ->andWhere('h.rdv = rdv.id')
                ->getDQL()
            ;
        }

        if ($dateType == StatisticColumnType::DATE_TYPE_HISTORIQUE) {
            $qb->innerJoin('rdv.historique', 'historique');
            $qb->innerJoin('historique.mainaction', 'mainaction');
            $qb->andWhere('historique.date >= :begin_contact AND historique.date < :end_contact');
        } else {
            $qb->andWhere('rdv.dateConsultation >= :begin_contact AND rdv.dateConsultation < :end_contact');
        }
        if ($dateType == StatisticColumnType::DATE_TYPE_HISTORIQUE) {
            $qb->andWhere('mainaction.idcode = :add_code');
            $qb->andWhere('historique.date = (' . $subQuery . ')');
            $qb->setParameter('add_code', ActionSuivi::ADD_CONSULT);
        }
        $qb->setParameter('begin_contact', $begin);
        $qb->setParameter('end_contact', $end);

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @param \Datetime    $begin
     * @param \Datetime    $end
     *
     * @return QueryBuilder
     */
    protected function addEncDateIntervalFilter(QueryBuilder $qb, \Datetime $begin, \Datetime $end)
    {
        $qb->andWhere('enc.date >= DATE(:begin_enc) AND enc.date < DATE(:end_enc)');
        $qb->setParameter('begin_enc', $begin);
        $qb->setParameter('end_enc', $end);

        return $qb;
    }

    /**
     * Filtre de Recuperation du Mois même
     *
     *
     * @param QueryBuilder $qb
     * @param \DateTime    $begin
     * @param \DateTime    $end
     *
     * @return QueryBuilder
     */
    protected function addEncRecupMMFilter(QueryBuilder $qb, \DateTime $begin, \DateTime $end)
    {
        $qb->andWhere('date(enc.date) != date(rdv.dateConsultation)');
        //remove the same month recuperation. These are no longer "recup"
        $qb->andWhere('rdv.dateConsultation > DATE(:begin_date_consultation) AND rdv.dateConsultation < DATE(:end_date_consultation)');
        $qb->setParameter('begin_date_consultation', $begin);
        $qb->setParameter('end_date_consultation', $end);

        return $qb;
    }

    /**
     * Manage filter for recuperation. If null, there is no filter.
     * If true, keep recup only.
     * If false, keep no-recup only.
     *
     * @param QueryBuilder $qb
     * @param \Boolean    $recup
     *
     * @return QueryBuilder
     */
    protected function addEncRecupFilter(QueryBuilder $qb, $recup = null, $withMM=false)
    {
        if ($recup === true) {
            $qb->andWhere('date(enc.date) != date(rdv.dateConsultation)');
            //remove the same month recuperation. These are no longer "recup"
            $qb->andWhere('MONTH(enc.date) != MONTH(rdv.dateConsultation)');
        } elseif ($recup === false) {
            if($withMM === true) {
                $qb->andWhere('MONTH(enc.date) = MONTH(rdv.dateConsultation) AND YEAR(enc.date) = YEAR(rdv.dateConsultation)');
            }
            else {
                $qb->andWhere('date(enc.date) = date(rdv.dateConsultation)');
            }
        }

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @param $etat
     *
     * @return QueryBuilder
     */
    protected function addConsultationEtatFilter(QueryBuilder $qb, $etat)
    {
        $qb->leftJoin('rdv.etat', 'eta')
            ->andWhere('eta.idcode = :code')
            ->setParameter('code', $etat);

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @param \Datetime    $begin
     * @param \Datetime    $end
     * @param bool         $beforeInterval
     *
     * @return QueryBuilder
     */
    protected function addRefDateIntervalFilter(QueryBuilder $qb, \Datetime $begin, \Datetime $end, $beforeInterval = false, $dateType = StatisticColumnType::DATE_TYPE_HISTORIQUE)
    {
        if ($dateType == StatisticColumnType::DATE_TYPE_HISTORIQUE) {
            $subQuery = $this->_em->createQueryBuilder()
                ->select('max(h.date)')
                ->from('KGCRdvBundle:SuiviRdv', 'h', null)
                ->innerJoin('h.mainaction', 'ma')
                ->andWhere('ma.idcode = :take_code')
                ->andWhere('h.rdv = rdv.id')
                ->getDQL()
            ;
        }

        if ($dateType == StatisticColumnType::DATE_TYPE_HISTORIQUE) {
            $qb->innerJoin('rdv.historique', 'historique');
            $qb->innerJoin('historique.mainaction', 'mainaction');
            if ($beforeInterval) {
                $qb->andWhere('historique.date < :begin_done');
            } else {
                $qb->andWhere('historique.date >= :begin_done AND historique.date < :end_done');
                $qb->setParameter('end_done', $end);
            }
        } else {
            if ($beforeInterval) {
                $qb->andWhere('rdv.dateConsultation < :begin_done');
            } else {
                $qb->andWhere('rdv.dateConsultation >= :begin_done AND rdv.dateConsultation < :end_done');
                $qb->setParameter('end_done', $end);
            }
        }
        $qb->setParameter('begin_done', $begin);
        if ($dateType == StatisticColumnType::DATE_TYPE_HISTORIQUE) {
            $qb->andWhere('mainaction.idcode = :take_code');
            $qb->andWhere('historique.date = (' . $subQuery . ')');
            $qb->setParameter('take_code', ActionSuivi::TAKE_CONSULT);
        }

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $comp
     *
     * @return QueryBuilder
     */
    protected function addRefDateCompEncDate(QueryBuilder $qb, $comp = '!=')
    {
        $qb->andWhere(sprintf('DATE(historique.date) %s DATE(enc.date)', $comp));

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @param \Datetime    $begin
     * @param \Datetime    $end
     * @param string       $suffix
     */
    protected function addSecureDateIntervalFilter(QueryBuilder $qb, \Datetime $begin, \Datetime $end, $suffix = '')
    {
        $historique = 'historique'.$suffix;
        $mainaction = 'mainaction'.$suffix;
        $h = 'h'.$suffix;
        $ma = 'ma'.$suffix;

        $subQuery = $this->_em->createQueryBuilder()
            ->select('max('.$h.'.date)')
            ->from('KGCRdvBundle:SuiviRdv', $h, null)
            ->innerJoin($h.'.mainaction', $ma)
            ->andWhere($h.'.date >= :begin_secu AND '.$h.'.date < :end_secu')
            ->andWhere($ma.'.idcode = :secure_code')
            ->andWhere($h.'.rdv = rdv.id')
            ->getDQL()
        ;

        $qb->innerJoin('rdv.historique', $historique);
        $qb->innerJoin($historique.'.mainaction', $mainaction);
        $qb->andWhere($historique.'.date >= :begin_secu AND '.$historique.'.date < :end_secu');
        $qb->andWhere($mainaction.'.idcode = :secure_code');
        $qb->andWhere($historique.'.date = ('.$subQuery.')');
        $qb->setParameter('begin_secu', $begin);
        $qb->setParameter('end_secu', $end);
        $qb->setParameter('secure_code', ActionSuivi::SECURE_BANKDETAILS);
        $qb->andWhere('rdv.securisation = :secure_status')->setParameter('secure_status', RDV::SECU_DONE);
        $qb->andWhere('rdv.preAuthorization IS NULL');
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $classement
     *
     * @return QueryBuilder
     */
    protected function addCLassementFilter(QueryBuilder $qb, $classement)
    {
        $qb->innerJoin('rdv.classement', 'cla')
            ->andWhere('cla.idcode = :code_10')
            ->setParameter('code_10', $classement)
        ;

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @param $proprioId
     *
     * @return QueryBuilder
     */
    protected function addProprioFilter(QueryBuilder $qb, $proprioId)
    {
        $qb->innerJoin('rdv.proprio', 'proprio')
            ->andWhere('proprio.id = :proprio_id')
            ->setParameter('proprio_id', $proprioId);

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @param $consultantId
     *
     * @return QueryBuilder
     */
    protected function addConsultantFilter(QueryBuilder $qb, $consultantId)
    {
        $qb->innerJoin('rdv.consultant', 'consultant')
            ->andWhere('consultant.id = :consultant_id')
            ->setParameter('consultant_id', $consultantId);

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return bool
     */
    protected function removeCancelledAndFNA(QueryBuilder $qb)
    {
        $qb->leftJoin('rdv.etat', 'e')
            ->leftJoin('rdv.classement', 'cla')
            ->andWhere('e.idcode != :code_annul OR (cla.idcode != :code_cla_fna AND cla.idcode != :code_cla_vide)')
            ->setParameter('code_cla_fna', Dossier::EN_FNA)
            ->setParameter('code_cla_vide', Dossier::CB_VIDE)
            ->setParameter('code_annul', Etat::CANCELLED)
        ;
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return bool
     */
    protected function removeCancelled(QueryBuilder $qb)
    {
        $qb->leftJoin('rdv.etat', 'e')
            ->andWhere('e.idcode != :e_code OR e.idcode IS NULL')
            ->setParameter('e_code', Etat::CANCELLED)
        ;
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return QueryBuilder
     */
    protected function addValidatedFilter(QueryBuilder $qb, $follow = null)
    {
        if ($follow == null) {
            $qb->innerJoin('rdv.tarification', 'tarification')
                ->andWhere('tarification.montant_total > :min_amount')
                    ->setParameter('min_amount', self::VALIDATED_MIN_AMOUNT);
            $qb->andWhere('tarification.temps > :min_time')
               ->setParameter('min_time', self::VALIDATED_MIN_TIME);
        } else {
            $qb->leftJoin('rdv.classement', 'cla')
                ->andWhere('cla.idcode != :code_10 OR cla.idcode IS NULL')
                ->setParameter('code_10', Dossier::DIXMIN)
            ;
        }

        $qb->andWhere('rdv.consultation = 1');

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @param $support_code
     * @param string $follow
     *
     * @return QueryBuilder
     */
    protected function addSupportFilter(QueryBuilder $qb, $support_code, $follow = 'exclude')
    {
        $qb->innerJoin('rdv.support', 'support');
        if (self::SUPPORT_EXCLUDE === $follow) {
            $qb->andWhere('support.idcode != :support_code OR support.idcode IS NULL');
        }
        if (self::SUPPORT_INCLUDE === $follow) {
            $qb->andWhere('support.idcode = :support_code');
        }

        $qb->setParameter('support_code', $support_code);

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return QueryBuilder
     */
    protected function addPhonisteGroup(QueryBuilder $qb)
    {
        $qb->innerJoin('rdv.proprio', 'proprio')
        ->innerJoin('proprio.mainProfil', 'profil')
        ->andWhere('proprio.actif = 1')
        ->andWhere('profil.role = :profil_name')
        ->setParameter('profil_name', Profil::PHONISTE)
        ->addGroupBy('proprio.id')
        ->addOrderBy('proprio.username', 'ASC');

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return QueryBuilder
     */
    protected function addProprioGroup(QueryBuilder $qb, $join = true)
    {
        if($join){
            $qb->innerJoin('rdv.proprio', 'proprio')
                ->innerJoin('proprio.mainProfil', 'profil');
        }
        $qb->andWhere('proprio.actif = 1');
        if($join) {
            $qb->addGroupBy('proprio.id')
                ->addOrderBy('proprio.username', 'ASC');
        }

        return $qb;
    }

    /**
     * @param $userId
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return array|int
     */
    public function getPhonisteDateIntervalRdv($userId, \Datetime $begin, \Datetime $end, $getTotal = false)
    {
        $qb = $this->createQueryBuilder('rdv')->select('count(rdv.id) as nb, DATE(rdv.dateContact) as date');

        $this->addContactDateIntervalFilter($qb, $begin, $end);
        $this->addProprioFilter($qb, $userId);
        $qb->addGroupBy('date');

        $this->removeCancelledAndFNA($qb);

        $rdvByDate = $qb->getQuery()->getResult();

        if($getTotal){
            $total = 0;
            foreach ($rdvByDate as $item) {
                $nb = (int) $item['nb'];
                $total += $nb;
            }

            return $total;
        }

        return $rdvByDate;
    }

    /**
     * @param $userId
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return mixed
     */
    public function getPhonisteDateIntervalValidatedRdv($userId, \Datetime $begin, \Datetime $end)
    {
        $qb = $this->createQueryBuilder('rdv')->select('count(rdv)');

        $this->addProprioFilter($qb, $userId);
        $this->addValidatedFilter($qb);
        $this->addRefDateIntervalFilter($qb, $begin, $end);

        $this->removeCancelledAndFNA($qb);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return mixed
     */
    public function getStandardDateIntervalTurnoverSecure(\Datetime $begin, \Datetime $end)
    {
        $qb = $this->createQueryBuilder('rdv')->select('count(rdv)');

        $this->addSecureDateIntervalFilter($qb, $begin, $end);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Base query for turnover
     * A turnover is always the sum of done receipts,
     * and cancelled but not yet opposed
     *
     * @param \Datetime $begin
     * @param \Datetime $end
     * @return QueryBuilder
     */
    public function getTurnoverQB(\DateTime $begin, \DateTime $end)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('sum(enc.montant) as ca')
            ->from('KGCRdvBundle:Encaissement', 'enc', null)
            ->innerJoin('enc.consultation', 'rdv')
            ->addOrderBy('enc.date')
        ;

        $this->addEncDoneAndNotOpposedYet($qb, $begin, $end);

        return $qb;
    }

    /**
     * Base query for turnover list element
     * A turnover is always the sum of done receipts,
     * and cancelled but not yet opposed
     *
     * @param \Datetime $begin
     * @param \Datetime $end
     * @return QueryBuilder
     */
    public function getTurnoverListQB(\DateTime $begin, \DateTime $end)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('enc')
            ->from('KGCRdvBundle:Encaissement', 'enc', null)
            ->innerJoin('enc.consultation', 'rdv')
            ->addOrderBy('enc.date')
        ;

        $this->addEncDoneAndNotOpposedYet($qb, $begin, $end);

        return $qb;
    }

    /**
     * @param $begin
     * @param $end
     * @return QueryBuilder
     */
    public function getRecupQB($begin, $end) {
        $qb = $this->getTurnoverQB($begin, $end);
        $qb->andWhere('rdv.paiement = 0');

        $this->addEncDateIntervalFilter($qb, $begin, $end);
        $this->addEncRecupFilter($qb, true);

        $subQuery = $this->_em->createQueryBuilder()
            ->select('count(e.id)')
            ->from('KGCRdvBundle:RDV', 'r')
            ->innerJoin('r.encaissements', 'e')
            ->where('e.etat = :etat_done')
            ->andWhere('r.id = rdv.id')
            ->andWhere('e.date < enc.date')
            ->getDQL()
        ;

        $qb->andWhere('0 = ('.$subQuery.')');

        return $qb;
    }

    /**
     * Return "standard" turnover for interval,
     * But doesn't remove opposition made during this interval
     *
     * @param \Datetime $begin
     * @param \Datetime $end
     * @param bool      $recup true if we want recup
     * @param bool      $doneInsideInterval
     *
     * @return float
     */
    public function getStandardDateIntervalTurnover(\Datetime $begin, \Datetime $end, $recup = null, $doneInsideInterval = false, $recupMm = false)
    {
        //initiate the query
        $qb = $this->getTurnoverQB($begin, $end);

        //add filter : keep only receipt between given dates
        $this->addEncDateIntervalFilter($qb, $begin, $end);

        //filter according to $recup value. If null, it does nothing.
        $this->addEncRecupFilter($qb, $recup);

        if($recupMm) {
            $this->addEncRecupMMFilter($qb, $begin, $end);
        }

        if ($doneInsideInterval) {
            $this->addRefDateIntervalFilter($qb, $begin, $end);
        }

        return $qb->getQuery()->getSingleScalarResult() / 100;
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     * @param bool      $doneInsideInterval
     *
     * @return float
     */
    public function getStandardDateIntervalTurnoverList(\Datetime $begin, \Datetime $end, $recup = null, $doneInsideInterval = false)
    {
        $qb = $this->getTurnoverListQB($begin, $end);

        $this->addEncDateIntervalFilter($qb, $begin, $end);

        $this->addEncRecupFilter($qb, $recup);
        if ($doneInsideInterval) {
            $this->addRefDateIntervalFilter($qb, $begin, $end);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \Datetime   $begin
     * @param \Datetime   $end
     * @param bool        $recup
     * @param Utilisateur $consultant
     * @param bool        $follow
     * @param Utilisateur $proprio
     *
     * @return float
     */
    public function getConsultantTurnover(\Datetime $begin, \Datetime $end, $recup = null, $consultant = null, $follow = null, $proprio = null)
    {
        $qb = $this->getTurnoverQB($begin,$end)
            ->andWhere('enc.psychic_asso = 1');

        $this->addEncDateIntervalFilter($qb, $begin, $end);

        //filter to get recupMM
        $this->addEncRecupFilter($qb, $recup, true);
        if ($consultant instanceof Utilisateur) {
            $this->addConsultantFilter($qb, $consultant->getId());
        }
        if ($proprio instanceof Utilisateur) {
            $this->addProprioFilter($qb, $proprio);
        }
        if (null !== $follow) {
            $this->addSupportFilter($qb, Support::SUIVI_CLIENT, $follow);
        }

        return $qb->getQuery()->getSingleScalarResult() / 100;
    }

    /**
     * @param \Datetime   $begin
     * @param \Datetime   $end
     * @param bool        $recup
     * @param Utilisateur $consultant
     * @param bool        $follow
     * @param Utilisateur $proprio
     *
     * @return float
     */
    public function getConsultantTurnoverList(\Datetime $begin, \Datetime $end, $recup = null, $consultant = null, $follow = null, $proprio = null)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('rdv.id, client.prenom, client.nom, SUM(enc.montant) as nb, count(enc) as nb_enc')
            ->from('KGCRdvBundle:Encaissement', 'enc', null)
            ->innerJoin('enc.consultation', 'rdv')
            ->innerJoin('rdv.client', 'client')
            ->addGroupBy('rdv.id')
            ->addGroupBy('enc.montant')
            ->orderBy('nb', 'ASC')
            ->andWhere('enc.psychic_asso = 1');

        $this->addEncDoneAndNotOpposedYet($qb, $begin, $end);

        $this->addEncDateIntervalFilter($qb, $begin, $end);
        //filter to get recupMM
        $this->addEncRecupFilter($qb, $recup, true);
        if ($consultant instanceof Utilisateur) {
            $this->addConsultantFilter($qb, $consultant->getId());
        }
        if ($proprio instanceof Utilisateur) {
            $this->addProprioFilter($qb, $proprio);
        }
        if (null !== $follow) {
            $this->addSupportFilter($qb, Support::SUIVI_CLIENT, $follow);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     * @param null $follow
     * @param null $consultant
     * @return array
     */
    public function getConsultantFnaList(\Datetime $begin, \Datetime $end, $follow = null, $consultant = null)
    {
        $subqb = $this->createQueryBuilder('rdv')->select('rdv.id');
        $this->applyFilterConsultantRdv($subqb, $begin, $end, $follow, $consultant, StatRepository::FILTER_VALID);
        $valid_ids = $subqb->getQuery()->getArrayResult();

        $qb = $this->_em->createQueryBuilder()
            ->select('rdv.id, client.prenom, client.nom, SUM(enc.montant) as nb, count(enc) as nb_enc')
            ->where('rdv.id NOT IN (:valid_ids)')
            ->from('KGCRdvBundle:Encaissement', 'enc', null)
            ->innerJoin('enc.consultation', 'rdv')
            ->innerJoin('rdv.client', 'client')
            ->addGroupBy('rdv.id')
            ->addGroupBy('enc.montant')
            ->orderBy('nb', 'ASC')
            ->setParameter('valid_ids', $valid_ids);

        $this->applyFilterConsultantRdv($qb, $begin, $end, $follow, $consultant, StatRepository::FILTER_DONE);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \Datetime   $begin
     * @param \Datetime   $end
     * @param bool        $follow
     * @param Utilisateur $consultant
     *
     * @return QueryBuilder
     */
    public function getConsultantRecupQb(\Datetime $begin, \Datetime $end, $follow = null, $consultant = null)
    {
        $qb = $this->getRecupQB($begin, $end);

        if ($consultant instanceof Utilisateur) {
            $this->addConsultantFilter($qb, $consultant->getId());
        }
        if (null !== $follow) {
            $this->addSupportFilter($qb, Support::SUIVI_CLIENT, $follow);
        }

        return $qb;
    }

    /**
     * @param \Datetime   $begin
     * @param \Datetime   $end
     * @param bool        $follow
     * @param Utilisateur $consultant
     *
     * @return int
     */
    public function getConsultantRecupCount(\Datetime $begin, \Datetime $end, $follow = null, $consultant = null)
    {
        $qb = $this->getConsultantRecupQB($begin, $end, $follow, $consultant)->addSelect('count(DISTINCT rdv.id) as nb');

        $r = $qb->getQuery()->getResult();

        return $r[0]['nb'];
    }

    /**
     * @param \Datetime   $begin
     * @param \Datetime   $end
     * @param bool        $follow
     * @param Utilisateur $consultant
     *
     * @return int
     */
    public function getConsultantRecupList(\Datetime $begin, \Datetime $end, $follow = null, $consultant = null)
    {
        $subqb = $this->getConsultantRecupQB($begin, $end, $follow, $consultant)->select('DISTINCT IDENTITY(enc.consultation)');
        $ids = $subqb->getQuery()->getArrayResult();

        $qb = $this->createQueryBuilder('rdv')
            ->where('rdv.id IN (:ids)')
            ->setParameter('ids', $ids);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     * @param null      $follow
     *
     * @return float
     */
    public function getStandardDateIntervalBilling(\Datetime $begin, \Datetime $end, $follow = null, $consultant = null, $proprio = null, $dateType = StatisticColumnType::DATE_TYPE_HISTORIQUE)
    {
        $qb = $this->createQueryBuilder('rdv')
            ->select('sum(t.montant_total) as billing')
            ->innerJoin('rdv.tarification', 't')
        ;

        $this->addRefDateIntervalFilter($qb, $begin, $end, false, $dateType);

        if (null !== $follow) {
            $this->addSupportFilter($qb, Support::SUIVI_CLIENT, $follow);
        }

        if ($consultant instanceof Utilisateur) {
            $this->addConsultantFilter($qb, $consultant->getId());
        }

        if ($proprio instanceof Utilisateur) {
            $this->addProprioFilter($qb, $proprio->getId());
        }

        return $qb->getQuery()->getSingleScalarResult() / 100;
    }

    /**
     * @param QueryBuilder     $qb
     * @param \Datetime        $begin
     * @param \Datetime        $end
     * @param string           $follow
     * @param Utilisateur|null $proprio
     */
    protected function applyFilterTakenConsult($qb, \Datetime $begin, \Datetime $end, $follow, $proprio = null)
    {
        $this->addContactDateIntervalFilter($qb, $begin, $end);

        if (null !== $follow) {
            $this->addSupportFilter($qb, Support::SUIVI_CLIENT, $follow);
        }

        if ($proprio instanceof Utilisateur) {
            $this->addProprioFilter($qb, $proprio->getId());
        }

        $this->removeCancelled($qb);
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     * @param string    $follow
     *
     * @return mixed
     */
    public function getStandardDateIntervalTakenCount(\Datetime $begin, \Datetime $end, $follow, $proprio = null)
    {
        $qb = $this->createQueryBuilder('rdv')->select('count(rdv)');

        $this->applyFilterTakenConsult($qb, $begin, $end, $follow, $proprio);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     * @param string    $follow
     *
     * @return mixed
     */
    public function getStandardDateIntervalTakenSpecificList(\Datetime $begin, \Datetime $end, $follow, $proprio = null)
    {
        $filter = [
            'begin' =>  $begin,
            'end' => $end,
            'websites' => [],
            'sources' => [],
            'urls' => [],
            'codesPromo' => [],
            'supports' => [],
            'phonists' => is_null($proprio) ? [] : [$proprio],
            'consultants' => [],
            'ca' => StatisticColumnType::$CA_CHOICES,
            'rdv' => StatisticColumnType::$RDV_LIGHTED_CHOICES,
            'dateType' => StatisticColumnType::DATE_TYPE_CONSULTATION,
            'statScope' => $follow == StatRepository::SUPPORT_INCLUDE ? StatScopeType::KEY_FOLLOW : StatScopeType::KEY_CONSULTATION,
            'sorting_column' => StatisticColumnType::CODE_RDV_TOTAL,
            'sorting_dir' => SortingColumnType::KEY_DESC,
        ];
        return $this->getAdminSpecificTreatedConsultDetails($filter, true);
    }
    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     * @param string    $follow
     *
     * @return mixed
     */
    public function getStandardDateIntervalTakenList(\Datetime $begin, \Datetime $end, $follow, $proprio = null)
    {
        $qb = $this->createQueryBuilder('rdv')->select('rdv');

        $this->applyFilterTakenConsult($qb, $begin, $end, $follow, $proprio);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     * @param string    $follow
     *
     * @return mixed
     */
    public function getStandardDateIntervalTaken(\Datetime $begin, \Datetime $end, $follow, $proprio = null)
    {
        $qb = $this->createQueryBuilder('rdv');

        $this->applyFilterTakenConsult($qb, $begin, $end, $follow, $proprio);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \Datetime   $begin
     * @param \Datetime   $end
     * @param string      $follow
     * @param Utilisateur $consultant
     *
     * @return mixed
     */
    public function getStandardDateInterval10MIN(\Datetime $begin, \Datetime $end, $follow = null, $consultant = null)
    {
        $qb = $this->createQueryBuilder('rdv')->select('count(rdv)');

        $this->addRefDateIntervalFilter($qb, $begin, $end);
        $this->addClassementFilter($qb, Dossier::DIXMIN);

        if (null !== $follow) {
            $this->addSupportFilter($qb, Support::SUIVI_CLIENT, $follow);
        }
        if ($consultant instanceof Utilisateur) {
            $this->addConsultantFilter($qb, $consultant->getId());
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     * @param $follow
     *
     * @return mixed
     */
    public function getStandardDateIntervalDone(\Datetime $begin, \Datetime $end, $follow)
    {
        $qb = $this->createQueryBuilder('rdv')->select('count(rdv)');
        $this->addRefDateIntervalFilter($qb, $begin, $end);

        if (null !== $follow) {
            $this->addSupportFilter($qb, Support::SUIVI_CLIENT, $follow);
        }

        $this->removeCancelledAndFNA($qb);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     * @param null      $follow
     *
     * @return mixed
     */
    public function getStandardDateIntervalDoneNotFree(\Datetime $begin, \Datetime $end, $follow)
    {
        $qb = $this->createQueryBuilder('rdv')->select('count(rdv)');
        $qb->innerJoin('rdv.tarification', 't');
        $qb->andWhere('t.montant_total > 1');
        $this->addRefDateIntervalFilter($qb, $begin, $end);

        if (null !== $follow) {
            $this->addSupportFilter($qb, Support::SUIVI_CLIENT, $follow);
        }

        $this->removeCancelledAndFNA($qb);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return mixed
     */
    public function getStandardDateIntervalSupport(\Datetime $begin, \Datetime $end)
    {
        $qb = $this->createQueryBuilder('rdv')
            ->select('count(rdv) as nb, support.libelle as name, support.id as support_id')
            ->innerJoin('rdv.support', 'support')
            ->andWhere('support.enabled = 1')
            ->addGroupBy('support.id')
        ;

        $this->addContactDateIntervalFilter($qb, $begin, $end);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     * @param string $support
     *
     * @return mixed
     */
    public function getStandardDateIntervalSupportList(\Datetime $begin, \Datetime $end, $support)
    {
        $qb = $this->createQueryBuilder('rdv')
            ->select('rdv')
            ->innerJoin('rdv.support', 'support')
            ->andWhere('support.id = :support')
            ->andWhere('support.enabled = 1')
            ->setParameter('support', $support)
        ;

        $this->addContactDateIntervalFilter($qb, $begin, $end);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     * @param string $support
     *
     * @return mixed
     */
    public function getStandardDateIntervalPhoningList(\Datetime $begin, \Datetime $end, $proprio)
    {
        $qb = $this->createQueryBuilder('rdv')
            ->select('rdv')
            ->innerJoin('rdv.proprio', 'proprio')
            ->innerJoin('proprio.mainProfil', 'profil')
            ->andWhere('proprio.id = :proprio')
            ->andWhere('proprio.actif = 1')
            ->setParameter('proprio', $proprio)
        ;

        $this->addSupportFilter($qb, Support::SUIVI_CLIENT);
        $this->addContactDateIntervalFilter($qb, $begin, $end);
        $this->removeCancelledAndFNA($qb);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return mixed
     */
    public function getStandardDateIntervalPhoning(\Datetime $begin, \Datetime $end)
    {
        $qb = $this->createQueryBuilder('rdv')
            ->select('count(rdv) as nb, proprio.username as name, proprio.id as proprio_id')
            ->innerJoin('rdv.proprio', 'proprio')
            ->innerJoin('proprio.mainProfil', 'profil')
            ->andWhere('proprio.actif = 1')
            ->andWhere('profil.role = :name')
                ->setParameter('name', Profil::PHONISTE)
            ->addGroupBy('proprio.id')
            ->addOrderBy('proprio.username', 'ASC')
        ;

        $this->addSupportFilter($qb, Support::SUIVI_CLIENT);
        $this->addContactDateIntervalFilter($qb, $begin, $end);
        $this->removeCancelledAndFNA($qb);

        return $qb->getQuery()->getResult();
    }

    public function getStandardDateIntervalProcessingQb(\Datetime $begin, \Datetime $end, $follow, $consultant = null)
    {
        $qb = $this->createQueryBuilder('rdv');
        $qb->andWhere('rdv.priseencharge = 1');
        $qb->andWhere('rdv.consultation IS NULL');

        $this->addConsultationDateIntervalFilter($qb, $begin, $end);

        $qb->leftJoin('rdv.etat', 'eta')
            ->andWhere('eta.idcode != :cancelled')
            ->setParameter('cancelled', Etat::CANCELLED);

        if (null !== $follow) {
            $this->addSupportFilter($qb, Support::SUIVI_CLIENT, $follow);
        }
        if ($consultant instanceof Utilisateur) {
            $this->addConsultantFilter($qb, $consultant->getId());
        }

        $this->removeCancelledAndFNA($qb);

        return $qb;
    }

    /**
     * @param $follow
     *
     * @return mixed
     */
    public function getStandardDateIntervalProcessing(\Datetime $begin, \Datetime $end, $follow, $consultant = null)
    {
        $qb = $this->getStandardDateIntervalProcessingQb($begin, $end, $follow, $consultant)->select('count(rdv)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getStandardDateIntervalProcessingList(\Datetime $begin, \Datetime $end, $follow, $consultant = null)
    {
        $qb = $this->getStandardDateIntervalProcessingQb($begin, $end, $follow, $consultant);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     * @param $follow
     *
     * @return mixed
     */
    public function getStandardDateIntervalPending(\Datetime $begin, \Datetime $end, $follow)
    {
        $qb = $this->createQueryBuilder('rdv')->select('count(rdv)');
        $qb->andWhere('rdv.securisation != :etat_secu')->setParameter('etat_secu', RDV::SECU_PENDING);
        $qb->andWhere('rdv.priseencharge IS NULL OR rdv.priseencharge = 0');

        $qb->leftJoin('rdv.etat', 'eta')
            ->andWhere('eta.idcode != :cancelled')
            ->setParameter('cancelled', Etat::CANCELLED);

        $this->addConsultationDateIntervalFilter($qb, $begin, $end);

        if (null !== $follow) {
            $this->addSupportFilter($qb, Support::SUIVI_CLIENT, $follow);
        }

        $this->removeCancelledAndFNA($qb);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     * @param $follow
     *
     * @return mixed
     */
    public function getStandardDateIntervalPendingList(\Datetime $begin, \Datetime $end, $follow)
    {
        $qb = $this->createQueryBuilder('rdv')->select('rdv');
        $qb->andWhere('rdv.securisation != :etat_secu')->setParameter('etat_secu', RDV::SECU_PENDING);
        $qb->andWhere('rdv.priseencharge IS NULL OR rdv.priseencharge = 0');

        $qb->leftJoin('rdv.etat', 'eta')
            ->andWhere('eta.idcode != :cancelled')
            ->setParameter('cancelled', Etat::CANCELLED);

        $this->addConsultationDateIntervalFilter($qb, $begin, $end);

        if (null !== $follow) {
            $this->addSupportFilter($qb, Support::SUIVI_CLIENT, $follow);
        }

        $this->removeCancelledAndFNA($qb);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     * @param $follow
     *
     * @return mixed
     */
    public function applyFilterConsultantRdv($qb, \Datetime $begin, \Datetime $end, $follow = null, $consultant = null, $crit = null, $proprio = null, $dateType = StatisticColumnType::DATE_TYPE_HISTORIQUE)
    {
        $qb->andWhere('rdv.priseencharge = 1');
        $this->addRefDateIntervalFilter($qb, $begin, $end, false, $dateType);

        if (null !== $follow) {
            $this->addSupportFilter($qb, Support::SUIVI_CLIENT, $follow);
        }
        if ($consultant instanceof Utilisateur) {
            $this->addConsultantFilter($qb, $consultant->getId());
        }
        if ($proprio instanceof Utilisateur) {
            $this->addProprioFilter($qb, $proprio);
        }
        switch ($crit) {
            case self::FILTER_CANCELLED :
                $qb->andWhere('rdv.consultation = 0');
                break;
            case self::FILTER_DONE :
                $this->addValidatedFilter($qb, $follow);
                break;
            case self::FILTER_10MIN :
                $this->addClassementFilter($qb, Dossier::DIXMIN);
                break;
            case self::FILTER_VALID :
                $qb->innerJoin('rdv.encaissements', 'enc');
                $qb->andWhere('enc.etat = :done OR (enc.etat IN (:cancelled, :refunded) AND enc.date_oppo > :limit_oppo )');
                $qb->setParameter('done', Encaissement::DONE);
                $qb->setParameter('cancelled', Encaissement::CANCELLED);
                $qb->setParameter('refunded', Encaissement::REFUNDED);
                $qb->setParameter('limit_oppo', $end);
                $this->addEncRecupFilter($qb, false);
                break;
        }
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     * @param $follow
     *
     * @return mixed
     */
    public function getConsultantRdvCount(\Datetime $begin, \Datetime $end, $follow = null, $consultant = null, $crit = null, $proprio = null, $dateType = StatisticColumnType::DATE_TYPE_HISTORIQUE)
    {
        $qb = $this->createQueryBuilder('rdv')->select('count(DISTINCT rdv.id)');

        $this->applyFilterConsultantRdv($qb, $begin, $end, $follow, $consultant, $crit, $proprio, $dateType);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     * @param $follow
     *
     * @return mixed
     */
    public function getConsultantRdvList(\Datetime $begin, \Datetime $end, $follow = null, $consultant = null, $crit = null, $proprio = null)
    {
        $qb = $this->createQueryBuilder('rdv');

        $this->applyFilterConsultantRdv($qb, $begin, $end, $follow, $consultant, $crit, $proprio);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     * @param $follow
     *
     * @return mixed
     */
    public function getConsultantRdv(\Datetime $begin, \Datetime $end, $follow = null, $consultant = null, $crit = null, $proprio = null)
    {
        $qb = $this->createQueryBuilder('rdv');

        $this->applyFilterConsultantRdv($qb, $begin, $end, $follow, $consultant, $crit, $proprio);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     * @param $follow
     *
     * @return mixed
     */
    public function getConsultantRdvFnaList(\Datetime $begin, \Datetime $end, $follow = null, $consultant = null)
    {
        $subqb = $this->createQueryBuilder('rdv')->select('rdv.id');
        $this->applyFilterConsultantRdv($subqb, $begin, $end, $follow, $consultant, StatRepository::FILTER_VALID);
        $valid_ids = $subqb->getQuery()->getArrayResult();

        $qb = $this->createQueryBuilder('rdv')
            ->where('rdv.id NOT IN (:valid_ids)')
            ->setParameter('valid_ids', $valid_ids);
        $this->applyFilterConsultantRdv($qb, $begin, $end, $follow, $consultant, StatRepository::FILTER_DONE);

        return $qb->getQuery()->getResult();
    }


    /**
     * @return QueryBuilder
     */
    public function getChatterUsersStatus()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->addSelect('(SELECT count(room.id)
                FROM KGCChatBundle:ChatRoom room
                INNER JOIN room.chatParticipants AS participants
                INNER JOIN participants.psychic AS psychic
                WHERE room.status = '.ChatRoom::STATUS_ON_GOING.'
                AND psychic.id = u.id
                ) AS processing_count')
            ->from('KGCUserBundle:Utilisateur', 'u')
            ->innerJoin('u.mainProfil', 'mp')
            ->andWhere('mp.role = :main_profil')->setParameter('main_profil', Profil::VOYANT)
            ->andWhere('u.actif = :actif')->setParameter('actif', true)
            ->andWhere('u.chatType IS NOT NULL')
            ->addOrderBy('u.username', 'ASC')
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @return QueryBuilder
     */
    public function getStandardUsersStatus()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->addSelect('(SELECT count(rdv.id)
                FROM KGCRdvBundle:RDV rdv
                WHERE rdv.priseencharge = 1 AND rdv.consultation IS NULL AND rdv.consultant = u.id
                ) AS processing_count')
            ->from('KGCUserBundle:Utilisateur', 'u')
            ->innerJoin('u.mainProfil', 'mp')
            ->andWhere('mp.role = :main_profil')->setParameter('main_profil', Profil::VOYANT)
            ->andWhere('u.actif = :actif')->setParameter('actif', true)
            ->andWhere('u.chatType IS NULL')
            ->addOrderBy('u.username', 'ASC')
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \Datetime   $begin
     * @param \Datetime   $end
     * @param type        $follow
     * @param Utilisateur $consultant
     *
     * @return mixed
     */
    public function getStandardDateIntervalOppo(\Datetime $begin, \Datetime $end, $follow = null, $consultant = null, $status = Encaissement::CANCELLED)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('sum(enc.montant) as ca')
            ->from('KGCRdvBundle:Encaissement', 'enc', null)
            ->innerJoin('enc.consultation', 'rdv')
            ->andWhere('enc.etat = :cancelled AND enc.date_oppo >= :begin AND enc.date_oppo < :end ')
            ->setParameter('cancelled', $status)
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->addOrderBy('enc.date')
        ;

        if ($consultant instanceof Utilisateur) {
            $this->addConsultantFilter($qb, $consultant->getId());
        }
        if (null !== $follow) {
            $this->addSupportFilter($qb, Support::SUIVI_CLIENT, $follow);
        }

        return $qb->getQuery()->getSingleScalarResult() / 100;
    }

    /**
     * @param \Datetime   $begin
     * @param \Datetime   $end
     * @param type        $follow
     * @param Utilisateur $consultant
     *
     * @return mixed
     */
    public function getStandardDateIntervalRefund(\Datetime $begin, \Datetime $end, $follow = null, $consultant = null)
    {
        return $this->getStandardDateIntervalOppo($begin, $end, $follow, $consultant, Encaissement::REFUNDED);
    }

    /**
     * @param \Datetime   $begin
     * @param \Datetime   $end
     * @param type        $follow
     * @param Utilisateur $consultant
     *
     * @return mixed
     */
    public function getStandardDateIntervalOppoList(\Datetime $begin, \Datetime $end, $follow = null, $consultant = null, $status = Encaissement::CANCELLED)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('enc')
            ->from('KGCRdvBundle:Encaissement', 'enc', null)
            ->innerJoin('enc.consultation', 'rdv')
            ->andWhere('enc.etat = :cancelled AND enc.date_oppo >= :begin AND enc.date_oppo < :end ')
            ->setParameter('cancelled', $status)
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->addOrderBy('enc.date')
        ;

        if ($consultant instanceof Utilisateur) {
            $this->addConsultantFilter($qb, $consultant->getId());
        }
        if (null !== $follow) {
            $this->addSupportFilter($qb, Support::SUIVI_CLIENT, $follow);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \Datetime   $begin
     * @param \Datetime   $end
     * @param type        $follow
     * @param Utilisateur $consultant
     *
     * @return mixed
     */
    public function getStandardDateIntervalRefundList(\Datetime $begin, \Datetime $end, $follow = null, $consultant = null)
    {
        return $this->getStandardDateIntervalOppoList($begin, $end, $follow, $consultant, Encaissement::REFUNDED);
    }

    /**
     * @param \Datetime   $begin
     * @param \Datetime   $end
     *
     * @return mixed
     */
    public function getStandardDateIntervalTurnoverFnaList(\Datetime $begin, \Datetime $end, $recup = null, $doneInsideInterval = false)
    {
        $qb = $this->createQueryBuilder('rdv')
            ->select('rdv')
            ->innerJoin('rdv.tarification', 't')
            ->where('t.montant_total > 0')
            ->groupBy('rdv.id');

        $this->addRefDateIntervalFilter($qb, $begin, $end);

        $result = [];

        foreach ($qb->getQuery()->getResult() as $rdv) {
            if (empty($result[$rdv->getId()])) {
                if ($montantImpaye = $rdv->getMontantImpaye($begin, $end)) {
                    $result[$rdv->getId()] = [
                        'consultation' => $rdv,
                        'montantImpaye' => $montantImpaye
                    ];
                }
            }

        }

        return $result;
    }

    /**
     * @param \Datetime   $begin
     * @param \Datetime   $end
     * @param type        $follow
     * @param Utilisateur $consultant
     *
     * @return QueryBuilder
     */
    public function getRdvOppoQb(\Datetime $begin, \Datetime $end, $follow = null, $consultant = null)
    {
        $qb = $this->_em->createQueryBuilder()
            ->from('KGCRdvBundle:Encaissement', 'enc', null)
            ->innerJoin('enc.consultation', 'rdv')
            ->andWhere('enc.etat = :cancelled AND enc.date_oppo >= :begin AND enc.date_oppo < :end ')
            ->setParameter('cancelled', Encaissement::CANCELLED)
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
        ;

        if ($consultant instanceof Utilisateur) {
            $this->addConsultantFilter($qb, $consultant->getId());
        }
        if (null !== $follow) {
            $this->addSupportFilter($qb, Support::SUIVI_CLIENT, $follow);
        }

        return $qb;
    }

    /**
     * @param \Datetime   $begin
     * @param \Datetime   $end
     * @param type        $follow
     * @param Utilisateur $consultant
     *
     * @return mixed
     */
    public function getCountRdvOppo(\Datetime $begin, \Datetime $end, $follow = null, $consultant = null)
    {
        $qb = $this->getRdvOppoQb($begin, $end, $follow, $consultant);
        $qb->select('count(rdv.id)')->groupBy('rdv.id');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \Datetime   $begin
     * @param \Datetime   $end
     * @param type        $follow
     * @param Utilisateur $consultant
     *
     * @return mixed
     */
    public function getOppoList(\Datetime $begin, \Datetime $end, $follow = null, $consultant = null)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('rdv.id, client.prenom, client.nom, SUM(enc.montant) as nb, count(enc) as nb_enc')
            ->from('KGCRdvBundle:Encaissement', 'enc', null)
            ->innerJoin('enc.consultation', 'rdv')
            ->innerJoin('rdv.client', 'client')
            ->addGroupBy('rdv.id')
            ->addGroupBy('enc.montant')
            ->orderBy('nb', 'ASC')
            ->andWhere('enc.etat = :cancelled AND enc.date_oppo >= :begin AND enc.date_oppo < :end ')
            ->setParameter('cancelled', Encaissement::CANCELLED)
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
        ;

        if ($consultant instanceof Utilisateur) {
            $this->addConsultantFilter($qb, $consultant->getId());
        }
        if (null !== $follow) {
            $this->addSupportFilter($qb, Support::SUIVI_CLIENT, $follow);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \Datetime   $begin
     * @param \Datetime   $end
     * @param type        $follow
     * @param Utilisateur $consultant
     *
     * @return mixed
     */
    public function getRdvOppoList(\Datetime $begin, \Datetime $end, $follow = null, $consultant = null)
    {
        $subqb = $this->getRdvOppoQb($begin, $end, $follow, $consultant)->select('DISTINCT IDENTITY(enc.consultation)');
        $ids = $subqb->getQuery()->getArrayResult();

        $qb = $this->createQueryBuilder('rdv')
            ->where('rdv.id IN (:ids)')
            ->setParameter('ids', $ids);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     * @param null $consultant
     * @return array
     */
    public function getConsultantProducts(\DateTime $begin, \DateTime $end, $consultant = null)
    {
        $pqb = $this->_em->createQueryBuilder()
            ->select('pro.label, count(vpr.id) as nb, sum(vpr.quantite) AS qt, sum(vpr.montant)/100 as mt')
            ->from('KGCRdvBundle:VentesProduits', 'vpr')
            ->innerJoin('vpr.produit', 'pro')
            ->innerJoin('vpr.tarification', 'tar')
            ->innerJoin('tar.rdv', 'rdv')
            ->groupBy('pro.id')
            ->orderBy('pro.label');

        $this->addRefDateIntervalFilter($pqb, $begin, $end);

        $fqb = $this->_em->createQueryBuilder()
            ->select("concat('Forfait ', nf.label) AS label, count(frf.id) AS qt, sum(frf.prix)/100 as mt")
            ->from('KGCRdvBundle:Tarification', 'tar')
            ->innerJoin('tar.rdv', 'rdv')
            ->innerJoin('tar.forfait_vendu', 'frf')
            ->innerJoin('frf.nom', 'nf')
            ->groupBy('nf.id')
            ->orderBy('frf.tps_total');

        $this->addRefDateIntervalFilter($fqb, $begin, $end);

        if ($consultant instanceof Utilisateur) {
            $this->addConsultantFilter($pqb, $consultant->getId());
            $this->addConsultantFilter($fqb, $consultant->getId());
        }

        return array_merge($pqb->getQuery()->getResult(), $fqb->getQuery()->getResult());
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     * @return array
     */
    public function getAdminCaTpeSecu(\Datetime $begin, \Datetime $end)
    {
        $qb = $this->createQueryBuilder('rdv')
            ->select('count(rdv.id) as nb, DATE(historique.date) as date, tpe.libelle as tpe_name')
            ->innerJoin('rdv.tpe', 'tpe')
            ->addGroupBy('date')
            ->addGroupBy('tpe')
        ;

        $this->addSecureDateIntervalFilter($qb, $begin, $end);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \DateTime $begin
     * @param \DateTime $end
     * @return array
     */
    public function getAdminCaTpe(\DateTime $begin, \DateTime $end)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('rdv.id, SUM(enc.montant) as nb, DATE(enc.date) as date, tpe.libelle as tpe_name, count(enc.montant) as nb_enc, client.prenom, client.nom')
            ->addSelect('DATE(enc.date_oppo)')
            ->from('KGCRdvBundle:Encaissement', 'enc', null)
            ->innerJoin('enc.tpe', 'tpe')
            ->innerJoin('enc.consultation', 'rdv')
            ->innerJoin('rdv.client', 'client')
            ->addGroupBy('date')
            ->addGroupBy('tpe')
            ->addGroupBy('enc.montant')
            ->addGroupBy('rdv.id')
            ->orderBy('nb', 'ASC')
        ;

        $this->addEncDoneAndNotOpposedYet($qb, $begin, $end);
        $this->addEncDateIntervalFilter($qb, $begin, $end);

        return $qb->getQuery()->getResult();
    }

    public function getAdminCaTelecollecte(\Datetime $begin, \Datetime $end)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('tel.id AS id, DATE(tel.date) AS date, tpe.libelle as tpe_name, tel.amountOne AS tel1,  tel.amountTwo AS tel2,  tel.amountThree AS tel3, tel.total AS total')
            ->from('KGCRdvBundle:Telecollecte', 'tel', null)
            ->innerJoin('tel.tpe', 'tpe')
            ->andWhere('tel.date >= DATE(:begin_enc) AND tel.date < DATE(:end_enc)')
            ->setParameter('begin_enc', $begin)
            ->setParameter('end_enc', $end)
            ->orderBy('date', 'ASC')
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     * @return array
     */
    public function getAdminCaOppo(\Datetime $begin, \Datetime $end, $status = Encaissement::CANCELLED)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('SUM(enc.montant) as nb, DATE(enc.date_oppo) as date, count(enc.montant) as nb_enc')
            ->from('KGCRdvBundle:Encaissement', 'enc', null)
            ->andWhere('enc.etat = :cancelled AND enc.date_oppo >= :begin AND enc.date_oppo < :end ')
            ->setParameter('cancelled', $status)
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->addGroupBy('date')
            ->orderBy('nb', 'ASC')
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     * @return array
     */
    public function getAdminCaRefund(\Datetime $begin, \Datetime $end)
    {
        return $this->getAdminCaOppo($begin, $end, Encaissement::REFUNDED);
    }

    /**
     * @param \DateTime $begin
     * @param \DateTime $end
     * @return array
     */
    public function getAdminCaPayment(\DateTime $begin, \DateTime $end)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('rdv.id, SUM(enc.montant) as nb, DATE(enc.date) as date, payment.libelle as payment_name, count(enc.montant) as nb_enc, client.prenom, client.nom')
            ->from('KGCRdvBundle:Encaissement', 'enc', null)
            ->innerJoin('enc.moyenPaiement', 'payment')
            ->innerJoin('enc.consultation', 'rdv')
            ->innerJoin('rdv.client', 'client')
            ->leftJoin('enc.tpe', 'tpe')
            ->andWhere('tpe.id IS NULL')
            ->addGroupBy('date')
            ->addGroupBy('payment')
            ->addGroupBy('rdv.id')
            ->addGroupBy('enc.montant')
            ->orderBy('nb', 'ASC')
        ;

        $this->addEncDoneAndNotOpposedYet($qb, $begin, $end);
        $this->addEncDateIntervalFilter($qb, $begin, $end);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \DateTime $begin
     * @param \DateTime $end
     * @param \DateTime $beginRdv
     * @param \DateTime $endRdv
     * @return array
     */
    public function getAdminOppoUnpaid(\DateTime $begin, \DateTime $end, \DateTime $beginRdv, \DateTime $endRdv, $etat = Encaissement::CANCELLED)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('SUM(enc.montant)/100 as amount, YEAR(historique.date) as year, MONTH(historique.date) as month')
            ->from('KGCRdvBundle:Encaissement', 'enc', null)
            ->innerJoin('enc.consultation', 'rdv')
            ->addGroupBy('year')
            ->addGroupBy('month')
        ;

        $qb->andWhere('enc.etat = :etat_cancelled')
            ->setParameter('etat_cancelled', $etat)
        ;

        $qb->andWhere('enc.date_oppo >= DATE(:date_oppo_begin) AND enc.date_oppo < DATE(:date_oppo_end)');
        $qb->setParameter('date_oppo_begin', $begin);
        $qb->setParameter('date_oppo_end', $end);

        $this->addRefDateIntervalFilter($qb, $beginRdv, $endRdv);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \DateTime $begin
     * @param \DateTime $end
     * @param \DateTime $beginRdv
     * @param \DateTime $endRdv
     * @return array
     */
    public function getAdminRefundUnpaid(\DateTime $begin, \DateTime $end, \DateTime $beginRdv, \DateTime $endRdv)
    {
        return $this->getAdminOppoUnpaid($begin, $end, $beginRdv, $endRdv, Encaissement::REFUNDED);
    }

    public function getAdminRecupUnpaid(\Datetime $begin, \Datetime $end, \Datetime $beginRdv, \Datetime $endRdv)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('SUM(enc.montant)/100 as amount, YEAR(historique.date) as year, MONTH(historique.date) as month')
            ->from('KGCRdvBundle:Encaissement', 'enc', null)
            ->innerJoin('enc.consultation', 'rdv')
            ->addGroupBy('year')
            ->addGroupBy('month')
        ;

        $this->addRefDateIntervalFilter($qb, $beginRdv, $endRdv);
        $this->addEncDateIntervalFilter($qb, $begin, $end);
        $this->addEncDoneAndNotOpposedYet($qb, $beginRdv, $endRdv);
        $this->addRefDateCompEncDate($qb, '!=');

        return $qb->getQuery()->getResult();
    }

    public function getUnpaidStat(\Datetime $begin, \Datetime $end, \Datetime $beginRdv, \Datetime $endRdv)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('SUM(enc.montant)/100 as amount, YEAR(historique.date) as year, MONTH(historique.date) as month')
            ->from('KGCRdvBundle:Encaissement', 'enc', null)
            ->innerJoin('enc.consultation', 'rdv')
            ->addGroupBy('year')
            ->addGroupBy('month')
        ;

        $this->addRefDateIntervalFilter($qb, $beginRdv, $endRdv, StatisticColumnType::DATE_TYPE_CONSULTATION);
        $this->addEncDateIntervalFilter($qb, $begin, $end);
        $this->addEncDoneAndNotOpposedYet($qb, $beginRdv, $endRdv);
        $this->addRefDateCompEncDate($qb, '!=');

        return $qb->getQuery()->getResult();
    }

    public function getAdminBillingUnpaid(\Datetime $beginRdv, \Datetime $endRdv)
    {
        $qb = $this->createQueryBuilder('rdv')
            ->select('sum(t.montant_total)/100 as amount,  YEAR(historique.date) as year, MONTH(historique.date) as month')
            ->innerJoin('rdv.tarification', 't')
            ->addGroupBy('year')
            ->addGroupBy('month')
        ;

        $this->addRefDateIntervalFilter($qb, $beginRdv, $endRdv);

        return $qb->getQuery()->getResult();
    }

    public function getAdminPaidUnpaid(\Datetime $beginRdv, \Datetime $endRdv, $sameDayFilter = false)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('SUM(enc.montant)/100 as amount, YEAR(historique.date) as year, MONTH(historique.date) as month')
            ->from('KGCRdvBundle:Encaissement', 'enc', null)
            ->innerJoin('enc.consultation', 'rdv')
            ->addGroupBy('year')
            ->addGroupBy('month')
        ;

        $this->addEncDoneAndNotOpposedThisMonth($qb);
        $this->addRefDateIntervalFilter($qb, $beginRdv, $endRdv);
        $this->addEncDateIntervalFilter($qb, $beginRdv, $endRdv);

        if ($sameDayFilter) {
            $this->addRefDateCompEncDate($qb, '=');
        }

        return $qb->getQuery()->getResult();
    }

    public function getAdminTotalPhoning(\Datetime $begin, \Datetime $end)
    {
        $qb = $this->createQueryBuilder('rdv')
            ->select('count(rdv.id) as nb, proprio.username as name')
        ;

        $this->addPhonisteGroup($qb);
        $this->addSupportFilter($qb, Support::SUIVI_CLIENT);
        $this->addContactDateIntervalFilter($qb, $begin, $end);
        $this->removeCancelledAndFNA($qb);

        return $qb->getQuery()->getResult();
    }

    public function getAdmin10MinPhoning(\Datetime $begin, \Datetime $end)
    {
        $qb = $this->createQueryBuilder('rdv')
            ->select('count(rdv.id) as nb, proprio.username as name')
        ;

        $this->addValidatedFilter($qb);
        $this->addPhonisteGroup($qb);
        $this->addRefDateIntervalFilter($qb, $begin, $end);
        $this->addSupportFilter($qb, Support::SUIVI_CLIENT);
        $this->removeCancelledAndFNA($qb);

        return $qb->getQuery()->getResult();
    }

    public function getAdminCAPhoning(\Datetime $begin, \Datetime $end)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('SUM(enc.montant)/100 as amount, proprio.username as name')
            ->from('KGCRdvBundle:Encaissement', 'enc', null)
            ->innerJoin('enc.consultation', 'rdv');

        $this->addEncDoneAndNotOpposedYet($qb, $begin, $end);
        $this->addValidatedFilter($qb);
        $this->addPhonisteGroup($qb);
        $this->addRefDateIntervalFilter($qb, $begin, $end);
        $this->addSupportFilter($qb, Support::SUIVI_CLIENT);
        $this->removeCancelledAndFNA($qb);

        return $qb->getQuery()->getResult();
    }

    public function getAdminCASecuPhoning(\Datetime $begin, \Datetime $end)
    {
        $qb = $this->createQueryBuilder('rdv')
            ->select('count(rdv.id) as nb, proprio.username as name')
        ;

        $this->addSecureDateIntervalFilter($qb, $begin, $end);
        $this->addValidatedFilter($qb);
        $this->addPhonisteGroup($qb);
        $this->addSupportFilter($qb, Support::SUIVI_CLIENT);
        $this->removeCancelledAndFNA($qb);

        return $qb->getQuery()->getResult();
    }

    public function getAdminCAOppoPhoning(\Datetime $begin, \Datetime $end)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('SUM(enc.montant)/100 as amount, proprio.username as name')
            ->from('KGCRdvBundle:Encaissement', 'enc', null)
            ->innerJoin('enc.consultation', 'rdv')
            ->andWhere('enc.etat = :cancelled AND enc.date_oppo >= :begin AND enc.date_oppo < :end ')
                ->setParameter('cancelled', Encaissement::CANCELLED)
                ->setParameter('begin', $begin)
                ->setParameter('end', $end)
        ;

        $this->addRefDateIntervalFilter($qb, $begin, $end);
        $this->addSupportFilter($qb, Support::SUIVI_CLIENT);
        $this->removeCancelledAndFNA($qb);
        $this->addPhonisteGroup($qb);

        return $qb->getQuery()->getResult();
    }

    public function getAdminBonusPhoning(\Datetime $begin, \Datetime $end)
    {
        $qb = $this->createQueryBuilder('rdv')->select('count(rdv.id) as nb, DATE(rdv.dateContact) as date, proprio.username as name');
        $qb->addGroupBy('date');
        $qb->addOrderBy('proprio.username');
        $qb->addOrderBy('date', 'ASC');

        $this->addContactDateIntervalFilter($qb, $begin, $end);
        $this->addPhonisteGroup($qb);
        $this->removeCancelledAndFNA($qb);
        $this->addSupportFilter($qb, Support::SUIVI_CLIENT);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \Datetime   $begin
     * @param \Datetime   $end
     * @param Utilisateur $user
     * @param string      $type
     */
    public function getUserJournalCount(\Datetime $begin, \Datetime $end, Utilisateur $user, $type = null)
    {
        $qb = $this->_em->createQueryBuilder()
        ->select('count(jrn.id)')
        ->from('KGCUserBundle:Journal', 'jrn')
        ->where('jrn.date >= :jrn_begin AND jrn.date < :jrn_end')
        ->setParameter('jrn_begin', $begin)
        ->setParameter('jrn_end', $end)
        ->innerJoin('jrn.utilisateur', 'uti')
        ->andWhere('uti.id = :user_id')
        ->setParameter('user_id', $user->getId());

        if ($type !== null) {
            $qb->andWhere('jrn.type = :type_code')
               ->setParameter('type_code', $type);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getAdminJournalPhoning(\Datetime $begin, \Datetime $end)
    {
        $qb = $this->_em->createQueryBuilder()
        ->select('u.username as name')
        ->addSelect('(SELECT count(jrn1.id)
            FROM KGCUserBundle:Journal jrn1
            WHERE jrn1.date >= :jrn_begin
                AND jrn1.date < :jrn_end
                AND jrn1.type = :jrn_type_abs
                AND jrn1.utilisateur = u.id
            ) AS abs_count')->setParameter('jrn_type_abs', Journal::ABSENCE)
        ->addSelect('(SELECT count(jrn2.id)
            FROM KGCUserBundle:Journal jrn2
            WHERE jrn2.date >= :jrn_begin
                AND jrn2.date < :jrn_end
                AND jrn2.type = :jrn_type_late
                AND jrn2.utilisateur = u.id
            ) AS late_count')->setParameter('jrn_type_late', Journal::LATENESS)
        ->from('KGCUserBundle:Utilisateur', 'u', null)
        ->innerJoin('u.mainProfil', 'profil')
        ->andWhere('u.actif = 1')
        ->andWhere('profil.role = :profil_name')
            ->setParameter('profil_name', Profil::PHONISTE)
        ;

        $qb->setParameter('jrn_begin', $begin);
        $qb->setParameter('jrn_end', $end);

        return $qb->getQuery()->getResult();
    }

    public function getCountRecapSent(\Datetime $begin, \Datetime $end, Utilisateur $user, $consultant = null)
    {
        $qb = $this->_em->createQueryBuilder()
                ->select('count(distinct h.rdv)')
                ->from('KGCClientBundle:Historique', 'h')
                ->innerJoin('h.options', 'o')
                ->where('o.code = :option')
                ->setParameter('option', Option::SENDING_BILAN_SENT)
                ->andwhere('h.consultant = :user_id')
                ->setParameter('user_id', $user->getId())
                ->andWhere('h.updatedAt >= :begin and h.updatedAt < :end')
                ->setParameter('begin', $begin)
                ->setParameter('end', $end);

        if ($consultant instanceof Utilisateur) {
            $qb->innerJoin('h.rdv', 'rdv');
            $this->addConsultantFilter($qb, $consultant->getId());
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param QueryBuilder $qb
     * @param \Datetime    $begin
     * @param \Datetime    $end
     *
     * @return QueryBuilder
     */
    protected function addConsultationDateIntervalFilter(QueryBuilder $qb, \Datetime $begin, \Datetime $end)
    {
        $qb->andWhere('rdv.dateConsultation >= :begin_consult AND rdv.dateConsultation < :end_consult');
        $qb->setParameter('begin_consult', $begin);
        $qb->setParameter('end_consult', $end);

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @param $filter
     * @param bool $groupBy
     * @return QueryBuilder
     */
    protected function addGFilter(QueryBuilder $qb, $filter, $groupBy=true) {

        if ($filter['statScope'] == 'follow') {
            $this->addSupportFilter($qb, Support::SUIVI_CLIENT, self::SUPPORT_INCLUDE);
        } else if ($filter['statScope'] == 'consult') {
            $this->addSupportFilter($qb, Support::SUIVI_CLIENT, self::SUPPORT_EXCLUDE);
        }


        if($groupBy) {
            $qb->leftJoin('rdv.website', 'rdv_website');
            $qb->leftJoin('rdv.source', 'rdv_source');
            $qb->leftJoin('rdv.form_url', 'rdv_url');
            $qb->leftJoin('rdv.codepromo', 'rdv_codepromo');
            $qb->leftJoin('rdv.support', 'rdv_support');
            $qb->leftJoin('rdv.consultant', 'rdv_consultant');
        }

        if(count($filter['phonists']) > 0) {
            if($groupBy) {
                $this->addPhonisteGroup($qb);
                $qb->addSelect("proprio.id  phonist_id")
                    ->addSelect("proprio.username as phonist_label");
            }

            if(!in_array('all', $filter['phonists'])) {
                $qb->andWhere('rdv.proprio IN (:phonist_ids)');
                $qb->setParameter('phonist_ids', $filter['phonists']);
            }
        }
        if(isset($filter['proprios']) && count($filter['proprios']) > 0) {
            if($groupBy) {
                $this->addProprioGroup($qb, !(count($filter['phonists']) > 0 && $groupBy));
                $qb->addSelect("proprio.id  proprio_id")
                    ->addSelect("proprio.username as proprio_label");
            }

            if (in_array('all', $filter['proprios'])) {
                $er = $this->_em->getRepository('KGCUserBundle:Utilisateur');
                $proprios = [];
                foreach($er->findByActif(1) as $c) {
                    $proprios[] = $c->getId();
                }
                $qb->andWhere('rdv.proprio IN (:proprio_ids)');
                $qb->setParameter('proprio_ids', $proprios);
            }
            else if(!in_array('all', $filter['proprios'])) {
                $qb->andWhere('rdv.proprio IN (:proprio_ids)');
                $qb->setParameter('proprio_ids', $filter['proprios']);
            }
        }
        if(isset($filter['reflex_affiliates']) && count($filter['reflex_affiliates']) > 0) {
            $qb->innerJoin('rdv.prospect', 'landing');
            if($groupBy) {
                $qb->addGroupBy('landing.reflexAffilateId')
                    ->addSelect("CASE WHEN landing.reflexAffilateId IS NULL THEN 'NULL' ELSE landing.reflexAffilateId END as reflex_affiliate_id")
                    ->addSelect("CASE WHEN landing.reflexAffilateId IS NULL THEN 'Aucun' ELSE landing.reflexAffilateId END as reflex_affiliate_label")
                    ->addOrderBy('reflex_affiliate_id', 'DESC');

            }
            if (in_array('all', $filter['reflex_affiliates'])) {
                $landing = $this->_em->getRepository('KGCSharedBundle:LandingUser');
                $reflexIds = [];
                foreach($landing->getReflexAffiliate() as $aAffiliate) {
                    $reflexIds[] = $aAffiliate['reflexAffilateId'];
                }
                $qb->andWhere('landing.reflexAffilateId IN (:reflex_affiliates)');
                $qb->setParameter('reflex_affiliates', $reflexIds);
            }
            else if(!in_array('all', $filter['reflex_affiliates'])) {
                $qb->andWhere('landing.reflexAffilateId IN (:reflex_affiliates)');
                $qb->setParameter('reflex_affiliates', $filter['reflex_affiliates']);
            }
        }
        if(isset($filter['reflex_sources']) && count($filter['reflex_sources']) > 0) {
            if(!(isset($filter['reflex_affiliates']) && count($filter['reflex_affiliates']) > 0)) {
                $qb->innerJoin('rdv.prospect', 'landing');
            }
            if($groupBy) {
                $qb->addGroupBy('landing.reflexSource')
                    ->addSelect("CASE WHEN landing.reflexSource IS NULL THEN 'NULL' ELSE landing.reflexSource END as reflex_source_id")
                    ->addSelect("CASE WHEN landing.reflexSource IS NULL THEN 'Aucun' ELSE landing.reflexSource END as reflex_source_label");
            }
            if (in_array('all', $filter['reflex_sources'])) {
                $landing = $this->_em->getRepository('KGCSharedBundle:LandingUser');
                $sourceIds = [];
                foreach($landing->getReflexSource() as $aSource) {
                    $sourceIds[] = $aSource['reflexSource'];
                }
                $qb->andWhere('landing.reflexSource IN (:reflex_sources)');
                $qb->setParameter('reflex_sources', $sourceIds);
            }
            else if(!in_array('all', $filter['reflex_sources'])) {
                $qb->andWhere('landing.reflexSource IN (:reflex_sources)');
                $qb->setParameter('reflex_sources', $filter['reflex_sources']);
            }
        }
        if(count($filter['consultants']) > 0) {
            if($groupBy) {
                $qb->addGroupBy('rdv.consultant')
                    ->addSelect("CASE WHEN rdv.consultant IS NULL THEN 'NULL' ELSE rdv_consultant.id END as consultant_id")
                    ->addSelect("CASE WHEN rdv.consultant IS NULL THEN 'Aucun' ELSE rdv_consultant.username END as consultant_label");
            }

            if(in_array('NULL', $filter['consultants'])) {
                $qb->andWhere('rdv.consultant IS NULL');
            }
            else if(!in_array('all', $filter['consultants'])) {
                $qb->andWhere('rdv.consultant IN (:consultant_ids)');
                $qb->setParameter('consultant_ids', $filter['consultants']);
            }
        }
        if(count($filter['websites']) > 0) {
            if($groupBy) {
                $qb->addGroupBy('rdv.website')
                    ->addSelect("CASE WHEN rdv.website IS NULL THEN 'NULL' ELSE rdv_website.id END as website_id")
                    ->addSelect("CASE WHEN rdv.website IS NULL THEN 'Aucun' ELSE rdv_website.libelle END as website_label");
            }

            if(in_array(WebsiteType::KEY_NULL, $filter['websites'])) {
                $qb->andWhere('rdv.website IS NULL');
            }
            else if(!in_array(WebsiteType::KEY_ALL, $filter['websites'])) {
                $qb->andWhere('rdv.website IN (:website_ids)');
                $qb->setParameter('website_ids', $filter['websites']);
            }
        }
        if(count($filter['sources']) > 0) {
            if($groupBy) {
                $qb->addGroupBy('rdv.source')
                    ->addSelect("CASE WHEN rdv.source IS NULL THEN 'NULL' ELSE rdv_source.id END as source_id")
                    ->addSelect("CASE WHEN rdv.source IS NULL THEN 'Aucune' ELSE rdv_source.label END as source_label");
            }

            if(in_array(SourceType::KEY_NULL, $filter['sources'])) {
                $qb->andWhere('rdv.source IS NULL');
            }
            else if(!in_array(SourceType::KEY_ALL, $filter['sources'])) {
                $qb->andWhere('rdv.source IN (:source_ids)');
                $qb->setParameter('source_ids', $filter['sources']);
            }
            else if(in_array(SourceType::KEY_ALL, $filter['sources']) && !empty($filter['role']) && $filter['role'] == 'affiliate') {
                $qb->andWhere('rdv_source.affiliateAllowed = 1');
            }
        }
        if(count($filter['urls']) > 0) {
            if($groupBy) {
                $qb->addGroupBy('rdv.form_url')
                    ->addSelect("CASE WHEN rdv.form_url IS NULL THEN 'NULL' ELSE rdv_url.id END as url_id")
                    ->addSelect("CASE WHEN rdv.form_url IS NULL THEN 'Aucune' ELSE rdv_url.label END as url_label");
            }

            if(in_array(UrlType::KEY_NULL, $filter['urls'])) {
                $qb->andWhere('rdv.form_url IS NULL');
            }
            else if(!in_array(UrlType::KEY_ALL, $filter['urls'])) {
                $qb->andWhere('rdv.form_url IN (:url_ids)');
                $qb->setParameter('url_ids', $filter['urls']);
            }

        }
        if(count($filter['codesPromo']) > 0) {
            if($groupBy) {
                $qb->addGroupBy('rdv.codepromo')
                    ->addSelect("CASE WHEN rdv.codepromo IS NULL THEN 'NULL' ELSE rdv_codepromo.id END as codepromo_id")
                    ->addSelect("CASE WHEN rdv.codepromo IS NULL THEN 'Aucun' ELSE rdv_codepromo.code END as codepromo_label");
            }

            if(in_array(CodePromoType::KEY_NULL, $filter['codesPromo'])) {
                $qb->andWhere('rdv.codepromo IS NULL');
            }
            else if(!in_array(CodePromoType::KEY_ALL, $filter['codesPromo'])) {
                $qb->andWhere('rdv.codepromo IN (:codepromo_ids)');
                $qb->setParameter('codepromo_ids', $filter['codesPromo']);
            }

        }
        if(count($filter['supports']) > 0) {
            if($groupBy) {
                $qb->addGroupBy('rdv.support')
                    ->addSelect("CASE WHEN rdv.support IS NULL THEN 'NULL' ELSE rdv_support.id END as support_id")
                    ->addSelect("CASE WHEN rdv.support IS NULL THEN 'Aucun' ELSE rdv_support.libelle END as support_label");
            }

            if(in_array(SupportType::KEY_NULL, $filter['supports'])) {
                $qb->andWhere('rdv.support IS NULL');
            }
            else if(!in_array(SupportType::KEY_ALL, $filter['supports'])) {
                $qb->andWhere('rdv.support IN (:support_ids)');
                $qb->setParameter('support_ids', $filter['supports']);
            }
        }

        return $qb;
    }

    /**
     * @param $filter
     * @return array
     */
    public function getAdminMMNbSpecific($filter) {
        $qb = $this->createQueryBuilder('rdv')->select('count(DISTINCT rdv.id) as rdv_mm')
            ->where('enc.etat = :done OR (enc.etat = :cancelled AND enc.date_oppo > :limit_oppo )')
            ->andWhere('cla.idcode IS NULL OR cla.idcode != :cla_dixmin')
            ->innerJoin('rdv.encaissements', 'enc')
            ->leftJoin('rdv.classement', 'cla')
            ->setParameter('limit_oppo', $filter['end'])
            ->setParameter('done', Encaissement::DONE)
            ->setParameter('cancelled', Encaissement::CANCELLED)
            ->setParameter('cla_dixmin', Dossier::DIXMIN);

        $this->addEncRecupFilter($qb, false, true);
        $this->addRefDateIntervalFilter($qb, $filter['begin'], $filter['end']);
        $this->addGFilter($qb, array_merge($filter, ['statScope' => 'consult']));

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param $filter
     * @param $recup
     * @return array
     */
    public function getVoyantTurnoverSpecific($filter, $recup, $withMM=false)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('sum(enc.montant / 100) as rdv_consult_' . ($recup ? 'recup_mp' : 'enc_mm'))
            ->from('KGCRdvBundle:Encaissement', 'enc', null)
            ->andWhere('enc.psychic_asso = 1')
            ->andWhere('cla.idcode IS NULL OR cla.idcode != :cla_dixmin')
            ->innerJoin('enc.consultation', 'rdv')
            ->leftJoin('rdv.classement', 'cla')
            ->setParameter('cla_dixmin', Dossier::DIXMIN)
        ;

        $this->addEncDoneAndNotOpposedYet($qb, $filter['begin'], $filter['end']);
        $this->addEncDateIntervalFilter($qb, $filter['begin'], $filter['end']);
        $this->addEncRecupFilter($qb, $recup, $withMM);

        $this->addGFilter($qb, array_merge($filter, ['statScope' => 'consult']));
        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param $filter
     * @return array
     */
    public function getAdminRecupMpFNANbSpecific($filter) {
        $qb = $this->getRecupQB($filter['begin'], $filter['end'])
            ->addSelect('count(DISTINCT rdv.id) as rdv_recup_mp_nb ')
            ->andWhere('cla.idcode IS NULL OR cla.idcode != :cla_dixmin')
            ->leftJoin('rdv.classement', 'cla')
            ->setParameter('cla_dixmin', Dossier::DIXMIN);

        $this->addGFilter($qb, array_merge($filter, ['statScope' => 'consult']));
        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param $filter
     * @return array
     */
    public function getAdminBillingSecuSpecific($filter) {
        $qb = $this->createQueryBuilder('rdv')
            ->select('count(rdv.id) as ca_enc_secu');
        $this->addGFilter($qb, $filter);
        $this->addSecureDateIntervalFilter($qb, $filter['begin'], $filter['end']);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param $filter
     * @return array
     */
    public function getAdminSpecificBillingRea($filter)
    {
        $qb = $this->createQueryBuilder('rdv')
            ->select('sum(t.montant_total / 100) as billing_rea, COUNT(rdv.id) as billing_count')
            ->innerJoin('rdv.tarification', 't')
            ->where('rdv.etat IS NOT NULL AND etat.idcode != :e_cancelled_code AND rdv.miserelation IS NOT NULL AND (cla.idcode IS NULL OR cla.idcode != :cla_dixmin)')

            ->leftJoin('rdv.etat', 'etat')
            ->leftJoin('rdv.classement', 'cla')

            ->setParameter('cla_dixmin', Dossier::DIXMIN)
            ->setParameter('e_cancelled_code', Etat::CANCELLED)
        ;
        $this->addRefDateIntervalFilter($qb, $filter['begin'], $filter['end'], false, (isset($filter['dateType'])) ? $filter['dateType'] : StatisticColumnType::DATE_TYPE_HISTORIQUE);
        $this->addGFilter($qb, $filter);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param $filter
     * @return array
     */
    public function getAdminSpecificRealisedCA($filter)
    {
        $qb = $this->createQueryBuilder('rdv')
            ->select('sum(t.montant_total / 100) as ca_realised')
            ->innerJoin('rdv.tarification', 't')
        ;


        $this->addRefDateIntervalFilter($qb, $filter['begin'], $filter['end'], false, (isset($filter['dateType'])) ? $filter['dateType'] : StatisticColumnType::DATE_TYPE_HISTORIQUE);
        $this->addGFilter($qb, $filter);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param $filter
     * @return array
     */
    public function getAdminBillingSpecific($filter) {

        $beginningOfTheMounth = new \Datetime($filter['begin']->format('Y-m-01 00:00'));
        $qb = $this->_em->createQueryBuilder()
            ->addSelect('
                SUM(CASE WHEN
                    enc.date >= DATE(:period_begin) AND enc.date < DATE(:period_end)
                    THEN enc.montant / 100 ELSE 0 END) AS ca_enc,
                SUM(CASE WHEN
                    enc.etat = :etat_oppo
                    AND enc.date_oppo IS NOT NULL
                    AND enc.date_oppo >= DATE(:period_begin) AND enc.date_oppo < DATE(:period_end)
                    THEN enc.montant / 100 ELSE 0 END) AS ca_enc_oppo,
                SUM(CASE WHEN
                    enc.etat = :etat_refund
                    AND enc.date_oppo IS NOT NULL
                    AND enc.date_oppo >= DATE(:period_begin) AND enc.date_oppo < DATE(:period_end)
                    THEN enc.montant / 100 ELSE 0 END) AS ca_enc_refund,
                SUM(CASE WHEN
                    enc.date >= DATE(:period_begin) AND enc.date < DATE(:period_end)
                    AND rdv.dateConsultation >= DATE(:mounth_begin) AND rdv.dateConsultation < DATE(:period_end)
                    AND enc.date_oppo IS NOT NULL
                    AND enc.date_oppo >= DATE(:period_begin) AND enc.date_oppo < DATE(:period_end)
                    THEN enc.montant / 100 ELSE 0 END) AS ca_enc_oppo_mm,
                SUM(CASE WHEN
                    enc.date >= DATE(:period_begin) AND enc.date < DATE(:period_end)
                    AND rdv.dateConsultation >= DATE(:mounth_begin) AND rdv.dateConsultation < DATE(:period_end)
                    THEN enc.montant / 100 ELSE 0 END) AS ca_enc_mm,
                SUM(CASE WHEN
                    enc.date >= DATE(:period_begin) AND enc.date < DATE(:period_end)
                    AND rdv.dateConsultation >= DATE(:mounth_begin) AND rdv.dateConsultation < DATE(:period_end)
                    AND SUBSTRING(rdv.dateConsultation, 1, 10) != SUBSTRING(enc.date, 1, 10)
                    THEN enc.montant / 100 ELSE 0 END) AS ca_recup_mm,
                SUM(CASE WHEN
                    enc.date >= DATE(:period_begin) AND enc.date < DATE(:period_end)
                    AND rdv.dateConsultation >= DATE(:mounth_begin) AND rdv.dateConsultation < DATE(:period_end)
                    AND SUBSTRING(rdv.dateConsultation, 1, 10) = SUBSTRING(enc.date, 1, 10)
                    THEN enc.montant / 100 ELSE 0 END) AS ca_enc_jm,
                SUM(CASE WHEN
                    enc.date >= DATE(:period_begin) AND enc.date < DATE(:period_end)
                    AND rdv.dateConsultation < DATE(:mounth_begin)
                    THEN enc.montant / 100 ELSE 0 END) AS ca_recup_mp')

            ->from('KGCRdvBundle:Encaissement', 'enc')

            ->leftJoin('enc.consultation', 'rdv')

            ->setParameter('etat_oppo', Encaissement::CANCELLED)
            ->setParameter('etat_refund', Encaissement::REFUNDED)
            ->setParameter("period_begin", $filter['begin'])
            ->setParameter("period_end", $filter['end'])
            ->setParameter("mounth_begin", $beginningOfTheMounth)
        ;

        $this->addEncDone($qb);
        $this->addGFilter($qb, $filter);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param $filter
     * @return array
     */
    public function getAdminConsultSpecific($filter) {
        $qb = $this->createQueryBuilder('rdv')
            ->select('SUM(1) AS rdv_total,
            SUM(CASE WHEN rdv.etat IS NULL OR rdv.classement IS NULL OR etat.idcode = :e_cancelled_code THEN 1 ELSE 0 END) AS rdv_cancelled,
            SUM(CASE WHEN etat.idcode = :e_cancelled_code AND cla.idcode = :cla_5x10 THEN 1 ELSE 0 END) AS rdv_510_min,
            SUM(CASE WHEN etat.idcode = :e_cancelled_code AND cla.idcode = :cla_cbbelge THEN 1 ELSE 0 END) AS rdv_cb_belge,
            SUM(CASE WHEN etat.idcode = :e_cancelled_code AND cla.idcode = :cla_cbvide THEN 1 ELSE 0 END) AS rdv_cb_vide,
            SUM(CASE WHEN etat.idcode = :e_cancelled_code AND cla.idcode = :cla_fna THEN 1 ELSE 0 END) AS rdv_fna,
            SUM(CASE WHEN etat.idcode = :e_cancelled_code AND cla.idcode = :cla_nrp THEN 1 ELSE 0 END) AS rdv_nrp,
            SUM(CASE WHEN etat.idcode = :e_cancelled_code AND cla.idcode = :cla_nvp THEN 1 ELSE 0 END) AS rdv_nvp,
            SUM(CASE WHEN etat.idcode = :e_cancelled_code AND cla.idcode = :cla_refus_1 THEN 1 ELSE 0 END) AS rdv_refused_secu,
            SUM(CASE WHEN rdv.etat IS NOT NULL AND etat.idcode != :e_cancelled_code AND rdv.miserelation IS NULL THEN 1 ELSE 0 END) AS rdv_waitorreport,
            SUM(CASE WHEN rdv.etat IS NOT NULL AND etat.idcode != :e_cancelled_code AND rdv.miserelation IS NOT NULL THEN 1 ELSE 0 END) AS rdv_treated,
            SUM(CASE WHEN rdv.etat IS NOT NULL AND etat.idcode != :e_cancelled_code AND rdv.miserelation IS NOT NULL AND cla.idcode = :cla_dixmin THEN 1 ELSE 0 END AS rdv_tenMinutes,
            SUM(CASE WHEN rdv.etat IS NOT NULL AND etat.idcode != :e_cancelled_code AND rdv.miserelation IS NOT NULL AND (cla.idcode IS NULL OR cla.idcode != :cla_dixmin) THEN 1 ELSE 0 END) AS rdv_overTenMinutes,
            SUM(CASE WHEN rdv.etat IS NOT NULL AND etat.idcode != :e_cancelled_code AND rdv.miserelation IS NOT NULL AND (cla.idcode IS NULL OR cla.idcode != :cla_dixmin)
                AND (0 = tarif.montant_total OR 0 != (SELECT COALESCE(SUM(rdv_enc.montant), 0) FROM KGCRdvBundle:Encaissement rdv_enc
                WHERE rdv_enc.consultation = rdv AND rdv_enc.etat = :etat_done)) THEN 1 ELSE 0 END) AS rdv_validated,
            SUM(CASE WHEN rdv.etat IS NOT NULL AND etat.idcode != :e_cancelled_code AND rdv.miserelation IS NOT NULL AND (cla.idcode IS NULL OR cla.idcode != :cla_dixmin)
                AND 0 != tarif.montant_total AND 0 = (SELECT COALESCE(SUM(rdv_enc2.montant), 0) FROM KGCRdvBundle:Encaissement rdv_enc2
                WHERE rdv_enc2.consultation = rdv AND rdv_enc2.etat = :etat_done) THEN 1 ELSE 0 END) AS rdv_unpaid')

            ->leftJoin('rdv.etat', 'etat')
            ->leftJoin('rdv.classement', 'cla')
            ->leftJoin('rdv.tarification', 'tarif')

            ->setParameter('e_cancelled_code', Etat::CANCELLED)
            ->setParameter('etat_done', Encaissement::DONE)
            ->setParameter('cla_5x10', "5FOIS10MIN")
            ->setParameter('cla_cbbelge', Dossier::CB_BELGE)
            ->setParameter('cla_cbvide', Dossier::CB_VIDE)
            ->setParameter('cla_fna', Dossier::EN_FNA)
            ->setParameter('cla_nrp', Dossier::NRP)
            ->setParameter('cla_nvp', Dossier::NVP)
            ->setParameter('cla_refus_1', Dossier::REFUS_2E)
            ->setParameter('cla_dixmin', Dossier::DIXMIN);

        $this->addContactDateIntervalFilter($qb, $filter['begin'], $filter['end'], (isset($filter['dateType'])) ? $filter['dateType'] : StatisticColumnType::DATE_TYPE_HISTORIQUE);
        $this->addGFilter($qb, $filter);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param $filter
     * @return array
     */
    public function getAdminSpecificCaRealDetails($filter)
    {
        $qb = $this->createQueryBuilder('rdv')
            ->select('distinct(rdv.id) as id, client.prenom, client.nom, tarif.montant_total as nb, 1 as nb_enc')
            ->innerJoin('rdv.client', 'client')
            ->leftJoin('rdv.tarification', 'tarif')
            ->orderBy('nb', 'ASC');

        $this->addRefDateIntervalFilter($qb, $filter['begin'], $filter['end'], false, (isset($filter['dateType'])) ? $filter['dateType'] : StatisticColumnType::DATE_TYPE_HISTORIQUE);
        $this->addGFilter($qb, $filter, false);
        return $qb->getQuery()->getResult();
    }

    /**
     * @param $filter
     * @return array
     */
    public function getAdminSpecificCaUnpaidDetails($filter)
    {
        $subQuery = $this->_em->createQueryBuilder()
            ->select('(tarif.montant_total - COALESCE(SUM(enc.montant), 0))')
            ->from('KGCRdvBundle:Encaissement', 'enc', null)
            ->where('enc.consultation = rdv')
        ;

        $this->addEncDateIntervalFilter($subQuery, $filter['begin'], $filter['end']);
        $this->addEncDoneAndNotOpposedYet($subQuery, $filter['begin'], $filter['end']);

        $qb = $this->createQueryBuilder('rdv')
            ->select('rdv.id, client.prenom, client.nom,   (' . $subQuery->getDQL() . ') as nb, 1 as nb_enc')
            ->innerJoin('rdv.client', 'client')
            ->innerJoin('rdv.tarification', 'tarif')
            ->orderBy('nb', 'ASC')
            ->setParameter('begin_enc', $filter['begin'])
            ->setParameter('end_enc', $filter['end'])
            ->setParameter('etat_done', Encaissement::DONE)
            ->setParameter('date_end', $filter['begin'])
            ->setParameter('date_begin', $filter['end'])
        ;

        $this->addContactDateIntervalFilter($qb, $filter['begin'], $filter['end'], (isset($filter['dateType'])) ? $filter['dateType'] : StatisticColumnType::DATE_TYPE_HISTORIQUE);
        $this->addGFilter($qb, $filter, false);
        return $qb->getQuery()->getResult();
    }

    /**
     * @param $filter
     * @return array
     */
    public function getAdminSpecificCaRecupMPDetails($filter)
    {
        $beginningOfTheMounth = new \Datetime($filter['begin']->format('Y-m-01 00:00'));
        $qb = $this->_em->createQueryBuilder()
            ->select('rdv.id, client.prenom, client.nom, SUM(enc.montant) as nb, count(enc) as nb_enc')
            ->where('rdv.dateConsultation < DATE(:mounth_begin)')
            ->from('KGCRdvBundle:Encaissement', 'enc', null)
            ->innerJoin('enc.consultation', 'rdv')
            ->innerJoin('rdv.client', 'client')
            ->addGroupBy('rdv.id')
            ->addGroupBy('enc.montant')
            ->orderBy('nb', 'ASC')
            ->setParameter('mounth_begin', $beginningOfTheMounth);

        $this->addEncDateIntervalFilter($qb, $filter['begin'], $filter['end']);
        $this->addEncDone($qb);
        $this->addGFilter($qb, $filter, false);
        return $qb->getQuery()->getResult();
    }

    /**
     * @param $filter
     * @return array
     */
    public function getAdminSpecificCaEncMMDetails($filter)
    {
        $beginningOfTheMounth = new \Datetime($filter['begin']->format('Y-m-01 00:00'));
        $qb = $this->_em->createQueryBuilder()
            ->select('rdv.id, client.prenom, client.nom, SUM(enc.montant) as nb, count(enc) as nb_enc')
            ->where('rdv.dateConsultation >= DATE(:mounth_begin) AND rdv.dateConsultation < DATE(:bill_end)')
            ->from('KGCRdvBundle:Encaissement', 'enc', null)
            ->innerJoin('enc.consultation', 'rdv')
            ->innerJoin('rdv.client', 'client')
            ->addGroupBy('rdv.id')
            ->addGroupBy('enc.montant')
            ->orderBy('nb', 'ASC')
            ->setParameter('bill_end', $filter['end'])
            ->setParameter('mounth_begin', $beginningOfTheMounth);

        $this->addEncDone($qb);
        $this->addEncDateIntervalFilter($qb, $filter['begin'], $filter['end']);
        $this->addGFilter($qb, $filter, false);
        return $qb->getQuery()->getResult();
    }

    /**
     * @param $filter
     * @return array
     */
    public function getAdminSpecificCaEncJMDetails($filter)
    {
        $beginningOfTheMounth = new \Datetime($filter['begin']->format('Y-m-01 00:00'));
        $qb = $this->_em->createQueryBuilder()
            ->select('rdv.id, client.prenom, client.nom, SUM(enc.montant) as nb, count(enc) as nb_enc')
            ->where('rdv.dateConsultation >= DATE(:mounth_begin) AND rdv.dateConsultation < DATE(:bill_end)')
            ->andWhere('SUBSTRING(rdv.dateConsultation, 1, 10) = SUBSTRING(enc.date, 1, 10) ')
            ->from('KGCRdvBundle:Encaissement', 'enc', null)
            ->innerJoin('enc.consultation', 'rdv')
            ->innerJoin('rdv.client', 'client')
            ->addGroupBy('rdv.id')
            ->addGroupBy('enc.montant')
            ->orderBy('nb', 'ASC')
            ->setParameter('bill_end', $filter['end'])
            ->setParameter('mounth_begin', $beginningOfTheMounth);

        $this->addEncDone($qb);
        $this->addEncDateIntervalFilter($qb, $filter['begin'], $filter['end']);
        $this->addGFilter($qb, $filter, false);
        return $qb->getQuery()->getResult();
    }

    /**
     * @param $filter
     * @return array
     */
    public function getAdminSpecificCaRecupMMDetails($filter)
    {
        $beginningOfTheMounth = new \Datetime($filter['begin']->format('Y-m-01 00:00'));
        $qb = $this->_em->createQueryBuilder()
            ->select('rdv.id, client.prenom, client.nom, SUM(enc.montant) as nb, count(enc) as nb_enc')
            ->where('rdv.dateConsultation >= DATE(:mounth_begin) AND rdv.dateConsultation < DATE(:bill_end)')
            ->andWhere('SUBSTRING(rdv.dateConsultation, 1, 10) != SUBSTRING(enc.date, 1, 10) ')
            ->from('KGCRdvBundle:Encaissement', 'enc', null)
            ->innerJoin('enc.consultation', 'rdv')
            ->innerJoin('rdv.client', 'client')
            ->addGroupBy('rdv.id')
            ->addGroupBy('enc.montant')
            ->orderBy('nb', 'ASC')
            ->setParameter('bill_end', $filter['end'])
            ->setParameter('mounth_begin', $beginningOfTheMounth);

        $this->addEncDone($qb);
        $this->addEncDateIntervalFilter($qb, $filter['begin'], $filter['end']);
        $this->addGFilter($qb, $filter, false);
        return $qb->getQuery()->getResult();
    }

    /**
     * @param $filter
     * @return array
     */
    public function getAdminSpecificCaEncTotalDetails($filter)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('rdv.id, client.prenom, client.nom, SUM(enc.montant) as nb, count(enc) as nb_enc')
            ->from('KGCRdvBundle:Encaissement', 'enc', null)
            ->innerJoin('enc.consultation', 'rdv')
            ->innerJoin('rdv.client', 'client')
            ->addGroupBy('rdv.id')
            ->addGroupBy('enc.montant')
            ->orderBy('nb', 'ASC');

        $this->addEncDone($qb);
        $this->addEncDateIntervalFilter($qb, $filter['begin'], $filter['end']);
        $this->addGFilter($qb, $filter, false);
        return $qb->getQuery()->getResult();
    }

    /**
     * @param $filter
     * @return array
     */
    public function getAdminSpecificCaEncOppoDetails($filter, $status = Encaissement::CANCELLED)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('rdv.id, client.prenom, client.nom, SUM(enc.montant) as nb, count(enc) as nb_enc')
            ->where('enc.etat = :etat')
            ->andWhere('enc.date_oppo IS NOT NULL')
            ->andWhere('enc.date_oppo >= DATE(:period_begin) AND enc.date_oppo < DATE(:period_end)')
            ->from('KGCRdvBundle:Encaissement', 'enc', null)
            ->innerJoin('enc.consultation', 'rdv')
            ->innerJoin('rdv.client', 'client')
            ->addGroupBy('rdv.id')
            ->addGroupBy('enc.montant')
            ->orderBy('nb', 'ASC')
            ->setParameter('etat', $status)
            ->setParameter('period_begin', $filter['begin'])
            ->setParameter('period_end', $filter['end']);;

        $this->addEncDone($qb);
        $this->addGFilter($qb, $filter, false);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param $filter
     * @return array
     */
    public function getAdminSpecificCaEncRefundDetails($filter)
    {
        return $this->getAdminSpecificCaEncOppoDetails($filter, Encaissement::REFUNDED);
    }

    /**
     * @param $filter
     * @return array
     */
    public function getAdminSpecificSecuDetails($filter) {
        $qb = $this->createQueryBuilder('rdv');
        $this->addSecureDateIntervalFilter($qb, $filter['begin'], $filter['end']);
        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param $filter
     * @return array
     */
    public function getAdminSpecificTotalConsultDetails($filter)
    {
        $qb = $this->createQueryBuilder('rdv');
        $this->addContactDateIntervalFilter($qb, $filter['begin'], $filter['end']);
        $this->addGFilter($qb, $filter, false);
        return $qb->getQuery()->getResult();
    }

    /**
     * @param $filter
     * @param null $cla
     * @return array
     */
    public function getAdminSpecificCancelledConsultDetails($filter, $cla=null)
    {
        $qb = $this->createQueryBuilder('rdv')
            ->andWhere('rdv.etat IS NULL OR rdv.classement IS NULL OR etat.idcode = :e_cancelled_code')
            ->leftJoin('rdv.etat', 'etat')
            ->setParameter('e_cancelled_code', Etat::CANCELLED);

        if($cla) {
            $qb->andWhere('cla.idcode = :cla_code')
                ->setParameter('cla_code', $cla)
                ->leftJoin('rdv.classement', 'cla');
        }

        $this->addContactDateIntervalFilter($qb, $filter['begin'], $filter['end']);
        $this->addGFilter($qb, $filter, false);
        return $qb->getQuery()->getResult();
    }

    /**
     * @param $filter
     * @return array
     */
    public function getAdminSpecificWaitOrReportConsultDetails($filter)
    {
        $qb = $this->createQueryBuilder('rdv')
            ->andWhere('etat.idcode IS NOT NULL')
            ->andWhere('etat.idcode != :e_cancelled_code')
            ->andWhere('rdv.miserelation IS NULL')
            ->setParameter('e_cancelled_code', Etat::CANCELLED)
            ->leftJoin('rdv.etat', 'etat');

        $this->addContactDateIntervalFilter($qb, $filter['begin'], $filter['end']);
        $this->addGFilter($qb, $filter, false);
        return $qb->getQuery()->getResult();
    }

    /**
     * @param $filter
     * @return array
     */
    public function getAdminSpecificTreatedConsultDetails($filter, $dateConsultation = false)
    {
        $qb = $this->createQueryBuilder('rdv')
            ->andWhere('etat.idcode IS NOT NULL')
            ->andWhere('etat.idcode != :e_cancelled_code')
            ->andWhere('rdv.miserelation IS NOT NULL')
            ->setParameter('e_cancelled_code', Etat::CANCELLED)
            ->leftJoin('rdv.etat', 'etat');

        $this->addContactDateIntervalFilter($qb, $filter['begin'], $filter['end'], (isset($filter['dateType'])) ? $filter['dateType'] : StatisticColumnType::DATE_TYPE_HISTORIQUE);
        $this->addGFilter($qb, $filter, false);
        return $qb->getQuery()->getResult();
    }

    /**
     * @param $filter
     * @return array
     */
    public function getAdminSpecificTenMinutesConsultDetails($filter)
    {
        $qb = $this->createQueryBuilder('rdv')
            ->andWhere('etat.idcode IS NOT NULL')
            ->andWhere('etat.idcode != :e_cancelled_code')
            ->andWhere('cla.idcode = :cla_dixmin')
            ->andWhere('rdv.miserelation IS NOT NULL')

            ->setParameter('e_cancelled_code', Etat::CANCELLED)
            ->setParameter('cla_dixmin', Dossier::DIXMIN)

            ->leftJoin('rdv.classement', 'cla')
            ->leftJoin('rdv.etat', 'etat')
            ->leftJoin('rdv.tarification', 'tarif');

        $this->addContactDateIntervalFilter($qb, $filter['begin'], $filter['end']);
        $this->addGFilter($qb, $filter, false);
        return $qb->getQuery()->getResult();
    }

    /**
     * @param $filter
     * @return array
     */
    public function getAdminSpecificOverTenMinutesConsultDetails($filter)
    {
        $qb = $this->createQueryBuilder('rdv')
            ->andWhere('etat.idcode IS NOT NULL')
            ->andWhere('etat.idcode != :e_cancelled_code')
            ->andWhere('cla.idcode IS NULL OR cla.idcode != :cla_dixmin')
            ->andWhere('rdv.miserelation IS NOT NULL')
            ->setParameter('e_cancelled_code', Etat::CANCELLED)
            ->setParameter('cla_dixmin', Dossier::DIXMIN)
            ->leftJoin('rdv.classement', 'cla')
            ->leftJoin('rdv.etat', 'etat')
            ->leftJoin('rdv.tarification', 'tarif');

        $this->addContactDateIntervalFilter($qb, $filter['begin'], $filter['end']);
        $this->addGFilter($qb, $filter, false);
        return $qb->getQuery()->getResult();
    }

    /**
     * @param $filter
     * @return array
     */
    public function getAdminSpecificValidatedConsultDetails($filter)
    {
        $qb = $this->createQueryBuilder('rdv')
            ->where('etat.idcode IS NOT NULL')
            ->andWhere('etat.idcode != :e_cancelled_code')
            ->andWhere('cla.idcode IS NULL OR cla.idcode != :cla_dixmin')
            ->andWhere('rdv.miserelation IS NOT NULL')
            ->andWhere('0 = tarif.montant_total OR 0 != (
                SELECT COALESCE(SUM(rdv_enc.montant), 0)
                FROM KGCRdvBundle:Encaissement rdv_enc
                WHERE rdv_enc.consultation = rdv AND rdv_enc.etat = :etat_done)')
            ->setParameter('e_cancelled_code', Etat::CANCELLED)
            ->setParameter('etat_done', Encaissement::DONE)
            ->setParameter('cla_dixmin', Dossier::DIXMIN)
            ->leftJoin('rdv.classement', 'cla')
            ->leftJoin('rdv.etat', 'etat')
            ->leftJoin('rdv.tarification', 'tarif');

        $this->addContactDateIntervalFilter($qb, $filter['begin'], $filter['end']);
        $this->addGFilter($qb, $filter, false);
        return $qb->getQuery()->getResult();
    }

    /**
     * @param $filter
     * @return array
     */
    public function getAdminSpecificUnpaidConsultDetails($filter)
    {
        $qb = $this->createQueryBuilder('rdv')
            ->andWhere('etat.idcode IS NOT NULL')
            ->andWhere('etat.idcode != :e_cancelled_code')
            ->andWhere('cla.idcode IS NULL OR cla.idcode != :cla_dixmin')
            ->andWhere('rdv.miserelation IS NOT NULL')
            ->andWhere('0 != tarif.montant_total')
            ->andWhere('0 = (
                SELECT COALESCE(SUM(rdv_enc.montant), 0)
                FROM KGCRdvBundle:Encaissement rdv_enc
                WHERE rdv_enc.consultation = rdv AND rdv_enc.etat = :etat_done)')
            ->setParameter('e_cancelled_code', Etat::CANCELLED)
            ->setParameter('etat_done', Encaissement::DONE)
            ->setParameter('cla_dixmin', Dossier::DIXMIN)
            ->leftJoin('rdv.etat', 'etat')
            ->leftJoin('rdv.classement', 'cla')
            ->leftJoin('rdv.tarification', 'tarif');

        $this->addContactDateIntervalFilter($qb, $filter['begin'], $filter['end']);
        $this->addGFilter($qb, $filter, false);

        return $qb->getQuery()->getResult();
    }


    protected function getAdminGeneralBillingQB(\Datetime $begin, \Datetime $end)
    {
        $qb = $this->createQueryBuilder('rdv')
            ->select(' sum(t.montant_total) / 100 as amount')
            ->innerJoin('rdv.tarification', 't')
        ;

        $this->addRefDateIntervalFilter($qb, $begin, $end);

        return $qb;
    }

    protected function getAdminGeneralPaidQB(\Datetime $begin, \Datetime $end, $isMonth = false)
    {
        $newBegin = clone $begin;

        $qb = $this->_em->createQueryBuilder()
            ->select('SUM(enc.montant)/100 as amount')
            ->from('KGCRdvBundle:Encaissement', 'enc', null)
            ->innerJoin('enc.consultation', 'rdv')
        ;

        $this->addEncDoneAndNotOpposedYet($qb, $begin, $end);
        $this->addEncDateIntervalFilter($qb, $begin, $end);

        if ($isMonth) {
            $this->addRefDateIntervalFilter($qb, $newBegin, $end);
        }

        return $qb;
    }

    protected function getAdminGeneralRecupQB(\Datetime $begin, \Datetime $end, $isMonth = false)
    {
        $newBegin = clone $begin;

        $qb = $this->_em->createQueryBuilder()
            ->select('SUM(enc.montant)/100 as amount')
            ->from('KGCRdvBundle:Encaissement', 'enc', null)
            ->innerJoin('enc.consultation', 'rdv')
        ;

        $this->addEncDateIntervalFilter($qb, $begin, $end);
        $this->addEncDoneAndNotOpposedYet($qb, $begin, $end);
        $this->addRefDateCompEncDate($qb, '!=');

        if (!$isMonth) {
            $newBegin->sub(new \DateInterval('P10Y'));
        }

        $this->addRefDateIntervalFilter($qb, $newBegin, $end);

        return $qb;
    }

    public function getAdminGeneralBilling(\Datetime $begin, \Datetime $end)
    {
        return $this->getAdminGeneralBillingQB($begin, $end)->getQuery()->getSingleScalarResult();
    }

    public function getAdminGeneralPaid(\Datetime $begin, \Datetime $end, $isMonth = false)
    {
        return $this->getAdminGeneralPaidQB($begin, $end, $isMonth)->getQuery()->getSingleScalarResult();
    }

    public function getAdminGeneralRecup(\Datetime $begin, \Datetime $end, $isMonth = false)
    {
        return $this->getAdminGeneralRecupQB($begin, $end, $isMonth)->getQuery()->getSingleScalarResult();
    }

    protected function addGroupGeneralStat(QueryBuilder $qb, $follow = null, $groupBy = null)
    {
        $qb->addSelect('count(rdv.id) as nb, website.libelle as website_label, support.libelle as support_label')
            ->leftJoin('rdv.website', 'website')
        ;

        if ('both' === $groupBy || 'support' === $groupBy) {
            $qb->addGroupBy('support.id');
        }
        if ('both' === $groupBy || 'website' === $groupBy) {
            $qb->addGroupBy('website.id');
        }

        if (null !== $follow) {
            $this->addSupportFilter($qb, Support::SUIVI_CLIENT, $follow);
        } else {
            $qb->innerJoin('rdv.support', 'support');
        }

        return $qb;
    }

    public function getAdminGeneralBillingFilter(\Datetime $begin, \Datetime $end, $follow = null, $groupBy = null)
    {
        $qb = $this->getAdminGeneralBillingQB($begin, $end);
        $this->addGroupGeneralStat($qb, $follow, $groupBy);

        return $qb->getQuery()->getResult();
    }

    public function getAdminGeneralPaidFilter(\Datetime $begin, \Datetime $end, $isMonth = false, $follow = null, $groupBy = null)
    {
        $qb = $this->getAdminGeneralPaidQB($begin, $end, $isMonth);
        $this->addGroupGeneralStat($qb, $follow, $groupBy);

        return $qb->getQuery()->getResult();
    }

    public function getAdminGeneralRecupFilter(\Datetime $begin, \Datetime $end, $isMonth = false, $follow = null, $groupBy = null)
    {
        $qb = $this->getAdminGeneralRecupQB($begin, $end, $isMonth);
        $this->addGroupGeneralStat($qb, $follow, $groupBy);

        return $qb->getQuery()->getResult();
    }

    public function getAdminGeneralSecure(\Datetime $begin, \Datetime $end, $isMonth = false)
    {
        $qb = $this->createQueryBuilder('rdv')->select('count(rdv) as nb');
        $this->addSecureDateIntervalFilter($qb, $begin, $end, 'secu');

        if ($isMonth) {
            $this->addRefDateIntervalFilter($qb, $begin, $end);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
