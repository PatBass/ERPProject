<?php
// src/KGC/RdvBundle/Repository/RDVRepository.php

namespace KGC\RdvBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\ClientBundle\Entity\Historique;
use KGC\RdvBundle\Entity\ActionSuivi;
use KGC\RdvBundle\Entity\Dossier;
use KGC\RdvBundle\Entity\Encaissement;
use KGC\RdvBundle\Entity\Etat;
use KGC\RdvBundle\Entity\RDV;
use KGC\RdvBundle\Entity\Support;
use KGC\UserBundle\Entity\Utilisateur;

/**
 * Class RDVRepository.
 */
class RDVRepository extends EntityRepository
{
    const BY_CB = 'cb';
    const BY_CLIENT = 'client';

    /**
     * Return base query builder to get a single RDV or collection.
     *
     * @return QueryBuilder
     */
    protected function createRdvQB($alias = null)
    {
        $alias = $alias ?: 'rdv';

        $queryBuilder = $this->createQueryBuilder($alias)
            ->select([$alias, 'tar', 'cli', 'cla', 'allumage', 'sup'])
            ->innerJoin($alias . '.support', 'sup')
            ->innerJoin($alias . '.client', 'cli')
            ->innerJoin($alias . '.classement', 'cla')
            ->leftJoin($alias . '.tarification', 'tar')
            ->leftJoin($alias . '.allumage', 'allumage');

        return $queryBuilder;
    }

    public function findAllByUser($id)
    {
        $queryBuilder = $this->createQueryBuilder('rdv')
            ->innerJoin('rdv.client', 'client')
            ->andWhere('client.id = :client_id')->setParameter('client_id', $id);

        return $queryBuilder->getQuery()->getResult();
    }

    public function findRdvWebsite($id)
    {
        $queryBuilder = $this->createQueryBuilder('rdv')
            ->select(['rdv', 'website', 'enc', 'payment', 't'])
            ->andWhere('rdv.id = :id')->setParameter('id', $id)
            ->leftJoin('rdv.website', 'website')
            ->leftJoin('rdv.encaissements', 'enc')
            ->leftJoin('enc.moyenPaiement', 'payment')
            ->leftJoin('rdv.tarification', 't');

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function findOneById($id)
    {
        $queryBuilder = $this->createRdvQB()
            ->addSelect(['eti', 'website', 'cb', 'histo', 'actions', 'groupe'])
            ->andWhere('rdv.id = :id')->setParameter('id', $id)
            ->leftJoin('rdv.etiquettes', 'eti')
            ->leftJoin('rdv.website', 'website')
            ->leftJoin('rdv.cartebancaires', 'cb')
            ->leftJoin('rdv.historique', 'histo')
            ->leftJoin('histo.actions', 'actions')
            ->leftJoin('actions.groupe', 'groupe');

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param $str_search
     *
     * @return array
     */
    protected function SearchByCb($str_search)
    {
        $queryBuilder = $this->createRdvQB()
            ->addSelect(['cb'])
            ->innerJoin('rdv.cartebancaires', 'cb')
            ->where('cb.numero LIKE :recherche')
            ->setParameter('recherche', $str_search)
            ->orderBy('cli.nom')
            ->addOrderBy('cli.prenom')
            ->addOrderBy('rdv.dateConsultation', 'DESC');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Recherche par nom/prénom.
     *
     * @param $str_search
     *
     * @return array
     */
    public function SearchByClient($str_search, Utilisateur $user = null)
    {
        $str_search = '%' . $str_search . '%';
        $queryBuilder = $this->createRdvQB()
            ->setParameter('recherche', $str_search);
        if (strpos($str_search, '@')) {
            $queryBuilder->where('cli.mail LIKE :recherche');
        } else {
            $queryBuilder->where('cli.nom LIKE :recherche OR cli.prenom LIKE :recherche OR rdv.numtel1 LIKE :recherche OR rdv.numtel2 LIKE :recherche');
        }

        $queryBuilder->orderBy('cli.nom')
            ->addOrderBy('cli.prenom')
            ->addOrderBy('rdv.dateConsultation', 'DESC');

        $this->addConsultantCriteria($queryBuilder, $user);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param $str_search
     * @param string $type
     *
     * @return array|EntityRepository
     */
    public function Search($str_search, $type, Utilisateur $user = null)
    {
        return self::BY_CB === $type
            ? $this->SearchByCb($str_search)
            : $this->SearchByClient($str_search, $user);
    }

    /**
     * retourne les consultations planifiées à l'horaire $h.
     *
     * @param \DateTime $h
     *
     * @return array
     */
    public function getPlanned(\DateTime $h)
    {
        $queryBuilder = $this->createRdvQB()
            ->leftJoin('rdv.etat', 'eta')
            ->where('eta.idcode != :cancelled')
            ->andWhere('rdv.dateConsultation = :horaire')
            ->setParameters(['cancelled' => Etat::CANCELLED, 'horaire' => $h])
            ->orderBy('rdv.dateContact');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param QueryBuilder $qb
     * @param Utilisateur $user
     *
     * @return QueryBuilder
     */
    protected function addConsultantCriteria(QueryBuilder $qb, Utilisateur $user = null)
    {
        if (null !== $user) {
            return $qb->innerJoin('rdv.consultant', 'c')
                ->andWhere('c.id = :consultant_id')
                ->setParameter('consultant_id', $user->getId());
        }
    }

    /**
     * @param \DateTime $debut
     * @param \DateTime $fin
     * @param Utilisateur $user
     *
     * @return array
     */
    public function getPlannedBetween(\DateTime $debut, \DateTime $fin, Utilisateur $user = null, $cb = false)
    {
        $queryBuilder = $this->createRdvQB()
            ->addSelect(['eta'])
            ->innerJoin('rdv.etat', 'eta')
            ->where('eta.idcode != :cancelled')
            ->andWhere('rdv.dateConsultation >= :debut AND rdv.dateConsultation < :fin')
            ->setParameters(['cancelled' => Etat::CANCELLED, 'debut' => $debut, 'fin' => $fin])
            ->orderBy('rdv.dateContact');

        if($cb) {
            $queryBuilder
                ->innerJoin('rdv.cartebancaires', 'cb');
        }

        $this->addConsultantCriteria($queryBuilder, $user);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * retourne les consultations non prises en charges alors que la date de consultation à l'heure h est dépassée.
     *
     * @param \DateTime $h
     * @param Utilisateur $user
     *
     * @return array
     */
    public function getBefore(\DateTime $h, Utilisateur $user = null)
    {
        $queryBuilder = $this->createRdvQB()
            ->addSelect(['eta'])
            ->innerJoin('rdv.etat', 'eta')
            ->where('eta.idcode != :cancelled')
            ->andWhere('rdv.consultation IS NULL')
            ->andWhere('rdv.dateConsultation < :heureh')
            ->setParameters(['cancelled' => Etat::CANCELLED, 'heureh' => $h])
            ->orderBy('rdv.dateContact', 'DESC');

        $this->addConsultantCriteria($queryBuilder, $user);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Retourne le prise en charge actuelle du voyant passé en paramètre.
     *
     * @param Utilisateur $user
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getPEC(Utilisateur $user)
    {
        $queryBuilder = $this->createRdvQB()
            ->andWhere('rdv.consultation IS NULL')
            ->andWhere('rdv.miserelation = 1')
            ->andWhere('rdv.priseencharge IS NOT NULL')
            ->andWhere('rdv.consultant = :voyant')
            ->setParameter('voyant', $user)
            ->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * Retourne les consultations dans un état précis.
     *
     * @param $state
     *
     * @return array
     */
    public function getByState($state, $limit = null, Utilisateur $user = null)
    {
        $qb = $this->createRdvQB()
            ->innerJoin('rdv.etat', 'e')
            ->andWhere('e.idcode = :etat')
            ->setParameter('etat', $state)
            ->addOrderBy('rdv.dateConsultation', 'DESC');

        $this->addConsultantCriteria($qb, $user);

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Retourne les consultations du service J-1 dans un type précis et une periode donnée.
     *
     * @param string $type
     * @param array $period
     *
     * @return array
     */
    public function getJ1Canceled($idClassement,$period, $limit = null, Utilisateur $user = null)
    {
        $query = $this->getJ1CanceledQuery($idClassement,$period, $limit,$user);
        return $query->getResult();
    }

    /**
     * Retourne les consultations du service J-1 dans un type précis et une periode donnée.
     *
     * @param string $type
     * @param array $period
     *
     * @return array
     */
    public function getJ110($idClassement,$period, $limit = null, Utilisateur $user = null)
    {
        $query = $this->getJ110Query($idClassement,$period, $limit,$user);
        return $query->getResult();
    }

    /**
     * Retourne les consultations du service J-1 dans un type précis et une periode donnée.
     *
     * @param string $type
     * @param array $period
     *
     * @return array
     */
    public function getJ1CanceledQuery($idClassement,$period, $limit = null, Utilisateur $user = null)
    {
        $qb = $this->createRdvQB()
            ->select('rdv')
            ->innerJoin('rdv.etat', 'e')
            ->innerJoin('rdv.classement', 'c')
            ->andWhere('c.id = :idClassement')
            ->andWhere('e.idcode = :etat')
            ->andWhere('rdv.dateConsultation >= :begin')
            ->andWhere('rdv.dateConsultation <= :end')
            ->setParameter('etat', Etat::CANCELLED)
            ->setParameter('idClassement', $idClassement)
            ->setParameter('begin', $period['begin'])
            ->setParameter('end', $period['end'])
            ->addOrderBy('rdv.dateConsultation', 'DESC');

        $this->addConsultantCriteria($qb, $user);

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery();
    }

    /**
     * Retourne les consultations du service J-1 dans un type précis et une periode donnée.
     *
     * @param string $type
     * @param array $period
     *
     * @return array
     */
    public function getJ110Query($idClassement,$period, $limit = null, Utilisateur $user = null)
    {
        $qb = $this->createRdvQB()
            ->select('rdv')
            ->innerJoin('rdv.classement', 'c')
            ->andWhere('c.id = :idClassement')
            ->andWhere('rdv.dateConsultation >= :begin')
            ->andWhere('rdv.dateConsultation <= :end')
            ->andWhere('sup.idcode IS NULL OR sup.idcode != :support')
            ->setParameter('idClassement', $idClassement)
            ->setParameter('begin', $period['begin'])
            ->setParameter('end', $period['end'])
            ->setParameter('support', Support::SUIVI_CLIENT)
            ->addOrderBy('rdv.dateConsultation', 'DESC');

        $this->addConsultantCriteria($qb, $user);

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }
        return $qb->getQuery();
    }

    public function getEffectuees($limit)
    {
        $qb = $this->createRdvQB()
            ->andWhere('rdv.consultation = 1')
            ->addOrderBy('rdv.dateConsultation', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function getEffectueesByClient(Client $client, $limit)
    {
        $qb = $this->createRdvQB()
            ->andWhere('rdv.consultation = 1')
            ->andWhere('rdv.client = :client')
            ->setParameter(':client', $client)
            ->addOrderBy('rdv.dateConsultation', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function getTodayReminders(Utilisateur $user = null, $begin = null, $end = null)
    {
        $ajd = $begin instanceof \DateTime ? $begin : new \DateTime('00:00');
        $dem = $end instanceof \DateTime ? $end : new \DateTime('tomorrow 00:00');

        $qb = $this->createQueryBuilder('rdv')
            ->select('rdv.id')
            ->leftJoin('rdv.notesVoyant', 'h')
            ->where('h.type = :reminder')
            ->andWhere('h.datetime >= :debut AND h.datetime < :fin')
            ->setParameters(['reminder' => Historique::TYPE_REMINDER, 'debut' => $ajd, 'fin' => $dem])
            ->orderBy('h.datetime');
        $this->addConsultantCriteria($qb, $user);

        $result = $qb->getQuery()->getResult();

        if (count($result) > 0) {
            $rdv_ids = array_map(function ($r) {
                return $r['id'];
            }, $result);

            $qb2 = $this->createRdvQB()
                ->addSelect(['h'])
                ->leftJoin('rdv.notesVoyant', 'h')
                ->add('where', $qb->expr()->in('rdv.id', $rdv_ids));

            return $qb2->getQuery()->getResult();
        }

        return [];
    }

    public function getSecurisations()
    {
        $qb = $this->createRdvQB()
            ->innerJoin('rdv.etat', 'e')
            ->where('e.idcode = :etat')
            ->andWhere('rdv.securisation = :secu')
            ->setParameters(['etat' => Etat::ADDED, 'secu' => RDV::SECU_PENDING])
            ->addOrderBy('rdv.dateConsultation', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function getUnpaidByClassementCount($classement, $dateinterval = null, $periodField = null, $tags = null)
    {
        $conditions = [
            'cla.cla_id = :cla_id'
        ];

        $havings = [];

        $parameters = [
            'cla_id' => $classement
        ];

        switch ($periodField) {
            case 'last_enc':
                $selectFields = 'MAX(enc.enc_date) AS last_date';
                $join = ($dateinterval['begin'] !== null ? 'INNER' : 'LEFT') . ' JOIN encaissements enc ON enc.enc_consultation = rdv.rdv_id AND enc.enc_etat != :planned';
                $parameters['planned'] = 'STATE_PLANNED';

                if ($dateinterval['begin'] !== null) {
                    $havings[] = 'last_date >= :date_begin';
                    $parameters['date_begin'] = $dateinterval['begin']->format('c');
                }

                if ($dateinterval['end'] !== null) {
                    $havings[] = 'last_date < :date_end';
                    $parameters['date_end'] = $dateinterval['end']->format('c');
                }
                break;
            case 'next_enc':
                $selectFields = 'MIN(enc.enc_date) AS planned_date';
                $join = ($dateinterval['begin'] !== null ? 'INNER' : 'LEFT') . ' JOIN encaissements enc ON enc.enc_consultation = rdv.rdv_id AND enc.enc_etat = :planned';
                $parameters['planned'] = 'STATE_PLANNED';

                if ($dateinterval['begin'] !== null) {
                    $havings[] = 'planned_date >= :date_begin';
                    $parameters['date_begin'] = $dateinterval['begin']->format('c');
                }

                if ($dateinterval['end'] !== null) {
                    $havings[] = 'planned_date < :date_end';
                    $parameters['date_end'] = $dateinterval['end']->format('c');
                }
                break;
            default: // case 'rdv'
                $selectFields = 'rdv_date_consultation';
                $join = '';

                if ($dateinterval['begin'] !== null) {
                    $havings[] = 'rdv_date_consultation >= :date_begin';
                    $parameters['date_begin'] = $dateinterval['begin']->format('c');
                }

                if ($dateinterval['end'] !== null) {
                    $havings[] = 'rdv_date_consultation < :date_end';
                    $parameters['date_end'] = $dateinterval['end']->format('c');
                }
                break;
        }

        $sql = "SELECT $selectFields FROM consultation rdv
        INNER JOIN classement cla ON cla.cla_id = rdv.rdv_classement $join";

        if (!empty($tags)) {
            $sql .= ' INNER JOIN consultation_etiquette eti ON eti.consultation_id';
            $conditions[] = 'eti.etiquette_id IN (:ids)';
            $parameters['ids'] = implode(', ', $tags);
        }

        $sql .= ' WHERE ' . implode(' AND ', $conditions) . ' GROUP BY rdv.rdv_id';
        if ($havings) {
            $sql .= ' HAVING ' . implode(' AND ', $havings);
        }

        $stmt = $this->getEntityManager()->getConnection()->prepare("SELECT COUNT(*) FROM ($sql) sub");
        $stmt->execute($parameters);

        return $stmt->fetchColumn();
    }

    public function getUnpaidByClassement($classement, $interval = null, $dateinterval = null, $periodField = null, $tags = null)
    {
        $qb = $this->createRdvQB()
            ->addSelect(['eti'])
            ->innerJoin('rdv.etat', 'e')
            ->leftJoin('rdv.etiquettes', 'eti')
            ->addGroupBy('rdv.id');
        if(is_array($classement)) {
            $qb->andWhere('cla.id IN (:cla_id)')->setParameter('cla_id', $classement);
        } else {
            $qb->andWhere('cla.id = :cla_id')->setParameter('cla_id', $classement);
        }
        if ($interval !== null) {
            $qb->setFirstResult($interval['start']);
            $qb->setMaxResults($interval['nb']);
        }

        switch ($periodField) {
            case 'last_enc':
                $joinMethod = $dateinterval['begin'] !== null ? 'innerJoin' : 'leftJoin';

                $qb
                    ->addSelect('MAX(enc.date) AS HIDDEN max_date')
                    ->$joinMethod('rdv.encaissements', 'enc', 'WITH', 'enc.etat != :planned')
                    ->setParameter('planned', Encaissement::PLANNED)
                    ->addOrderBy('max_date', 'DESC');

                if ($dateinterval['begin'] !== null) {
                    $qb
                        ->andHaving('max_date >= :date_begin')
                        ->setParameter('date_begin', $dateinterval['begin']);
                }
                if ($dateinterval['end'] !== null) {
                    $qb
                        ->andHaving('max_date < :date_end')
                        ->setParameter('planned', Encaissement::PLANNED)
                        ->setParameter('date_end', $dateinterval['end']);
                }
                break;
            case 'next_enc':
                $joinMethod = $dateinterval['begin'] !== null ? 'innerJoin' : 'leftJoin';

                $qb
                    ->addSelect('MIN(enc.date) AS HIDDEN min_date')
                    ->$joinMethod('rdv.encaissements', 'enc', 'WITH', 'enc.etat = :planned')
                    ->setParameter('planned', Encaissement::PLANNED)
                    ->addOrderBy('min_date', 'DESC');

                if ($dateinterval['begin'] !== null) {
                    $qb
                        ->andHaving('min_date >= :date_begin')
                        ->setParameter('date_begin', $dateinterval['begin']);
                }
                if ($dateinterval['end'] !== null) {
                    $qb
                        ->andHaving('min_date < :date_end')
                        ->setParameter('date_end', $dateinterval['end']);
                }
                break;
            default: // case 'rdv'
                $qb->addOrderBy('rdv.dateConsultation', 'DESC');

                if ($dateinterval['begin'] !== null) {
                    $qb->andWhere('DATE(rdv.dateConsultation) >= :date_begin')
                        ->setParameter('date_begin', $dateinterval['begin']);
                }
                if ($dateinterval['end'] !== null) {
                    $qb->andWhere('DATE(rdv.dateConsultation) < :date_end')
                        ->setParameter('date_end', $dateinterval['end']);
                }
                break;
        }

        if (!empty($tags)) {
            $qb->andWhere('eti.id IN (:ids)')
                ->setParameter('ids', $tags);
        }

        return $qb->getQuery()->getResult();
    }

    public function getUnpaidCARemainingByClassement($classement, $dateinterval = null, $periodField = null, $tags = null)
    {
        $qb = $this->createQueryBuilder('rdv')
            ->select(['(tar.montant_total - SUM(enc.montant))/100 as ca'])
            ->innerJoin('rdv.classement', 'cla')
            ->innerJoin('rdv.tarification', 'tar')
            ->innerJoin('rdv.encaissements', 'enc')
            ->andWhere('cla.id = :cla_id')->setParameter('cla_id', $classement)
            ->addGroupBy('rdv.id');
        switch ($periodField) {
            default: // case 'rdv'
                $qb->addOrderBy('rdv.dateConsultation', 'DESC');
                if ($dateinterval['begin'] !== null && $dateinterval['end'] !== null) {
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
                        ->setParameter('date_end', $dateinterval['end'])
                        ->setParameter('date_begin', $dateinterval['begin']);
                }
                if ($dateinterval['begin'] !== null) {
                    $qb->andWhere('DATE(rdv.dateConsultation) >= :date_begin')
                        ->setParameter('date_begin', $dateinterval['begin']);
                }
                if ($dateinterval['end'] !== null) {
                    $qb->andWhere('DATE(rdv.dateConsultation) < :date_end')
                        ->setParameter('date_end', $dateinterval['end']);
                }
                break;
        }

        if (!empty($tags)) {
            $qb->andWhere('eti.id IN (:ids)')
                ->setParameter('ids', $tags);
        }
        $ca = 0;
        foreach ($qb->getQuery()->getArrayResult() as $array) {
            $ca += $array['ca'];
        }

        return $ca;
    }

    public function searchByIntervalle(\Datetime $begin, \DateTime $end, Utilisateur $user = null)
    {
        $queryBuilder = $this->createRdvQB()
            ->addSelect(['eta'])
            ->innerJoin('rdv.etat', 'eta')
            ->where('eta.idcode != :cancelled')
            ->andWhere('rdv.dateConsultation >= :debut AND rdv.dateConsultation < :fin')
            ->setParameters(['cancelled' => Etat::CANCELLED, 'debut' => $begin, 'fin' => $end])
            ->orderBy('rdv.dateConsultation', 'ASC');

        $this->addConsultantCriteria($queryBuilder, $user);

        return $queryBuilder->getQuery()->getResult();
    }

    public function getYesterdayDone(Utilisateur $user = null, $filter = null, $begin = null, $end = null)
    {
        $hier = $begin instanceof \Datetime ? $begin : new \DateTime('yesterday');
        $ajd = $end instanceof \DateTime ? $end : new \DateTime('00:00');

        $qb = $this->createRdvQB()
            ->addSelect(['eta'])
            ->leftJoin('rdv.etat', 'eta')
            ->where('rdv.dateConsultation >= :debut AND rdv.dateConsultation < :fin')
            ->andWhere('rdv.priseencharge = 1')
            ->setParameters(['debut' => $hier, 'fin' => $ajd])
            ->orderBy('rdv.dateConsultation')
            ->addOrderBy('rdv.dateContact');
        $this->addConsultantCriteria($qb, $user);
        switch ($filter) {
            case 'nrp':
                $qb->andWhere('eta.idcode = :annule')
                    ->setParameter('annule', Etat::CANCELLED)
                    ->andWhere('cla.idcode = :nrp')
                    ->setParameter('nrp', Dossier::NRP);
                break;
            case 'lt10min':
                $qb->andWhere('tar.temps <= 10')
                    ->andWhere('eta.idcode != :annule')
                    ->setParameter('annule', Etat::CANCELLED);
                break;
            case '10-30min':
                $qb->andWhere('tar.temps > 10 AND tar.temps <= 30');
                break;
            case 'gt30min':
                $qb->andWhere('tar.temps > 30');
                break;
        }

        return $qb->getQuery()->getResult();
    }

    public function getMissingIdastros()
    {
        $queryBuilder = $this->createRdvQB()
            ->addSelect(['ida'])
            ->leftJoin('rdv.idAstro', 'ida')
            ->where('rdv.idAstro IS NULL OR ( ida.valeur < 1000 AND ida.valeur != 1 ) OR (rdv.website = 1 AND ida.valeur > 2000000) OR (rdv.website = 2 AND ida.valeur < 2000000 AND ida.valeur >= 3000000) OR (rdv.website = 3 AND ida.valeur < 3000000 AND ida.valeur >= 4000000) OR (rdv.website = 4 AND ida.valeur < 4000000 AND ida.valeur >= 5000000) OR (rdv.website = 5 AND ida.valeur < 5000000 AND ida.valeur >= 6000000) OR (rdv.website = 6 AND ida.valeur < 6000000 AND ida.valeur >= 7000000) OR (rdv.website = 7 AND ida.valeur < 7000000 AND ida.valeur >= 8000000) OR (rdv.website = 8 AND ida.valeur < 8000000 AND ida.valeur >= 9000000) OR (rdv.website = 9 AND ida.valeur < 9000000 AND ida.valeur >= 10000000) OR (rdv.website = 11 AND ida.valeur < 10000000 AND ida.valeur >= 11000000) OR (rdv.website = 12 AND ida.valeur < 11000000 AND ida.valeur >= 12000000)')
            ->andwhere('rdv.consultation = 1') // consultation faite
            ->andwhere('rdv.dateConsultation >= :limite')
            ->setParameters(['limite' => '2015-01-01'])
            ->orderBy('rdv.dateConsultation', 'DESC')
            ->addOrderBy('cli.nom')
            ->addOrderBy('cli.prenom');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param $alias
     *
     * @return QueryBuilder
     */
    protected function createQueryBuilderElastic($alias)
    {
        $queryBuilder = $this->createRdvQB($alias)
            ->addSelect(['vo', 'cons', 'tcode'])
            ->innerJoin($alias . '.website', 'web')->addSelect('web')
            ->innerJoin($alias . '.proprio', 'pro')->addSelect('pro')
            ->innerJoin($alias . '.adresse', 'ad')->addSelect('ad')
            ->leftJoin($alias . '.idAstro', 'ida')->addSelect('ida')
            ->leftJoin($alias . '.tpe', 'tpe')->addSelect('tpe')
            ->leftJoin($alias . '.codepromo', 'cp')->addSelect('cp')
            ->leftJoin($alias . '.voyant', 'vo')
            ->leftJoin($alias . '.consultant', 'cons')
            ->leftJoin($alias . '.etiquettes', 'eti')->addSelect('eti')
            ->leftJoin('tar.code', 'tcode')

            //->innerJoin($alias.'.cartebancaires', 'cb')->addSelect('cb')
            //->leftJoin('cli.historique', 'cli_histo')->addSelect('cli_histo')
        ;

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

    /**
     * Called after elastic returns documents to have a single SQL query.
     *
     * @param $entityAlias
     *
     * @return QueryBuilder
     */
    public function createQueryBuilderSearch($entityAlias)
    {
        return $this->createQueryBuilderElastic($entityAlias);
    }

    protected function addRefDateIntervalFilter(QueryBuilder $qb, \Datetime $begin, \Datetime $end, $beforeInterval = false)
    {
        $subQuery = $this->_em->createQueryBuilder()
            ->select('max(h.date)')
            ->from('KGCRdvBundle:SuiviRdv', 'h', null)
            ->innerJoin('h.mainaction', 'ma')
            ->andWhere('ma.idcode = :take_code')
            ->andWhere('h.rdv = rdv.id')
            ->getDQL();

        $qb->innerJoin('rdv.historique', 'historique');
        $qb->innerJoin('historique.mainaction', 'mainaction');

        $qb->andWhere('historique.date >= :begin_done AND historique.date < :end_done');
        $qb->setParameter('end_done', $end);
        $qb->setParameter('begin_done', $begin);

        $qb->andWhere('mainaction.idcode = :take_code');
        $qb->andWhere('historique.date = (' . $subQuery . ')');
        $qb->setParameter('take_code', ActionSuivi::TAKE_CONSULT);

        return $qb;
    }

    public function getGclidExportInfoQB(\Datetime $begin, \Datetime $end)
    {
        $qb = $this->createQueryBuilder('rdv')
            ->addSelect('t')
            ->innerJoin('rdv.tarification', 't')
            ->andWhere('rdv.gclid IS NOT NULL')
            ->orderBy('rdv.dateConsultation', 'DESC')
            ->andWhere('t.montant_total > 0')
            ->andWhere('rdv.dateConsultation >= :begin_done AND rdv.dateConsultation < :end_done')
            ->setParameter('end_done', $end)
            ->setParameter('begin_done', $begin);

        return $qb;
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return array
     */
    public function getGclidExportInfo(\Datetime $begin, \Datetime $end)
    {
        $qb = $this->getGclidExportInfoQB($begin, $end);

        return $qb->getQuery()->getResult();
    }

    public function getUnbalanced()
    {
        $qb = $this->createRdvQB()
            ->andWhere('rdv.balance = 0')
            ->andWhere('cla.idcode != :abandon AND cla.idcode != :litige')
            ->setParameters(['abandon' => Dossier::ABANDON, 'litige' => Dossier::LITIGE])
            ->addOrderBy('rdv.dateConsultation', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Client $client
     *
     * @return RDV
     */
    public function findLastClientRdv(Client $client)
    {
        return $this->createQueryBuilder('rdv')
            ->where('rdv.client = :client')->setParameter('client', $client)
            ->addOrderBy('rdv.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();
    }
}
