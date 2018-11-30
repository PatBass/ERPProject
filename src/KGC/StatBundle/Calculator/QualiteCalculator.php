<?php

// src/KGC/StatBundle/Calculator/QualiteCalculator.php


namespace KGC\StatBundle\Calculator;

use JMS\DiExtraBundle\Annotation as DI;
use KGC\StatBundle\Repository\StatRepository;
use KGC\UserBundle\Entity\Utilisateur;

/**
 * Class QualiteCalculator.
 *
 * @DI\Service("kgc.stat.calculator.qualite", parent="kgc.stat.calculator")
 */
class QualiteCalculator extends Calculator
{
    /**
     * @param array $config
     *
     * @return array
     */
    public function calculate(array $config = [])
    {
        $repo = $this->getStatRepository();
        $data = array();

        $needed_stats = $config['stats'];
        $date = isset($config['date']) ? $config['date'] : new \DateTime();
        $quality_user = isset($config['user']) && $config['user'] instanceof Utilisateur ? $config['user'] : null;
        $consultant = isset($config['consultant']) && $config['consultant'] instanceof Utilisateur ? $config['consultant'] : null;

        if (isset($quality_user) && $quality_user->isQualite()) {
            if (array_key_exists('general', $needed_stats) && $needed_stats['general']) {
                // #### JOUR ####
                list($begin, $end) = $this->getDayIntervalFromDate($date);
                // nb de suivis ajoutés
                $data['stats_qualite']['day_taken'] = $repo->getStandardDateIntervalTakenCount($begin, $end, StatRepository::SUPPORT_INCLUDE, $quality_user);
                // nb de suivis effectués
                $data['stats_qualite']['day_done'] = $repo->getConsultantRdvCount($begin, $end, StatRepository::SUPPORT_INCLUDE, $consultant, StatRepository::FILTER_DONE, $quality_user);
                // CA réalisé
                $data['stats_qualite']['day_ca_real'] = $repo->getStandardDateIntervalBilling($begin, $end, StatRepository::SUPPORT_INCLUDE, $consultant, $quality_user);
                // CA encaissé
                $data['stats_qualite']['day_ca_enc'] = $repo->getConsultantTurnover($begin, $end, null, $consultant, StatRepository::SUPPORT_INCLUDE, $quality_user);
                // CA encaissé - récups
                $data['stats_qualite']['day_ca_recup'] = $repo->getConsultantTurnover($begin, $end, true, $consultant, StatRepository::SUPPORT_INCLUDE, $quality_user);
                // nb bilans envoyés
                $data['stats_qualite']['day_recaps'] = $repo->getCountRecapSent($begin, $end, $quality_user, $consultant);

                // #### MOIS ####
                list($begin, $end) = $this->getMonthIntervalFromDate($date);
                // nb de suivis ajoutés
                $data['stats_qualite']['month_taken'] = $repo->getStandardDateIntervalTakenCount($begin, $end, StatRepository::SUPPORT_INCLUDE, $quality_user);
                // nb de suivis effectués
                $data['stats_qualite']['month_done'] = $repo->getConsultantRdvCount($begin, $end, StatRepository::SUPPORT_INCLUDE, $consultant, StatRepository::FILTER_DONE, $quality_user);
                // CA réalisé
                $data['stats_qualite']['month_ca_real'] = $repo->getStandardDateIntervalBilling($begin, $end, StatRepository::SUPPORT_INCLUDE, $consultant, $quality_user);
                // CA encaissé
                $data['stats_qualite']['month_ca_enc'] = $repo->getConsultantTurnover($begin, $end, null, $consultant, StatRepository::SUPPORT_INCLUDE, $quality_user);
                // CA encaissé - récups
                $data['stats_qualite']['month_ca_recup'] = $repo->getConsultantTurnover($begin, $end, true, $consultant, StatRepository::SUPPORT_INCLUDE, $quality_user);
                // nb bilans envoyés
                $data['stats_qualite']['month_recaps'] = $repo->getCountRecapSent($begin, $end, $quality_user, $consultant);
            }

            if (array_key_exists('detail_taken', $needed_stats) && $needed_stats['detail_taken']) {
                if (isset($config['periode']) && $config['periode'] === 'month') {
                    list($begin, $end) = $this->getMonthIntervalFromDate($date);
                } else {
                    list($begin, $end) = $this->getDayIntervalFromDate($date);
                }
                // nb de suivis ajoutés
                $data['details'] = $repo->getStandardDateIntervalTaken($begin, $end, StatRepository::SUPPORT_INCLUDE, $quality_user);
            }

            if (array_key_exists('detail_done', $needed_stats) && $needed_stats['detail_done']) {
                if (isset($config['periode']) && $config['periode'] === 'month') {
                    list($begin, $end) = $this->getMonthIntervalFromDate($date);
                } else {
                    list($begin, $end) = $this->getDayIntervalFromDate($date);
                }
                // nb de suivis ajoutés
                $data['details'] = $repo->getConsultantRdv($begin, $end, StatRepository::SUPPORT_INCLUDE, $consultant, StatRepository::FILTER_DONE, $quality_user);
            }
        }

        return $data;
    }
}
