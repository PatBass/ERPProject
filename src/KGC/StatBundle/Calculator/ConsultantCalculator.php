<?php
// src/KGC/StatBundle/Calculator/ConsultantCalculator.php

namespace KGC\StatBundle\Calculator;

use JMS\DiExtraBundle\Annotation as DI;
use KGC\StatBundle\Entity\BonusParameter;
use KGC\StatBundle\Form\StatisticColumnType;
use KGC\StatBundle\Repository\StatRepository;
use KGC\UserBundle\Entity\Journal;
use KGC\UserBundle\Entity\SalaryParameter;
use KGC\UserBundle\Entity\Utilisateur;

/**
 * Class ConsultantCalculator.
 *
 * @DI\Service("kgc.stat.calculator.consultant", parent="kgc.stat.calculator")
 */
class ConsultantCalculator extends Calculator
{
    protected function getStatsIfNeeded(array $data, \DateTime $begin, \DateTime $end, $consultant = null, $dateType = StatisticColumnType::DATE_TYPE_HISTORIQUE)
    {
        $repo = $this->getStatRepository();

        if ($data['stats'] === true) {
            $data['stats'] = array();

            // CONS CA JM
            $data['stats']['rdv']['turnover_day'] = $repo->getConsultantTurnover($begin, $end, false, $consultant, StatRepository::SUPPORT_EXCLUDE);
            // CONS CA FNA
            $sumBillingDay = $repo->getStandardDateIntervalBilling($begin, $end, StatRepository::SUPPORT_EXCLUDE, $consultant, null, $dateType);
            $turnoverDoneDay = $data['stats']['rdv']['turnover_day'];
            $data['stats']['rdv']['turnover_day_fna'] = max($sumBillingDay - $turnoverDoneDay, 0);
            // CONS CA RECUP
            $data['stats']['rdv']['turnover_recup'] = $repo->getConsultantTurnover($begin, $end, true, $consultant, StatRepository::SUPPORT_EXCLUDE);
            // CONS NB JM
            $data['stats']['rdv']['count_day_valid'] = $repo->getConsultantRdvCount($begin, $end, StatRepository::SUPPORT_EXCLUDE, $consultant, StatRepository::FILTER_VALID, null, $dateType);
            // CONS NB FNA
            $data['stats']['rdv']['count_day'] = $repo->getConsultantRdvCount($begin, $end, StatRepository::SUPPORT_EXCLUDE, $consultant, StatRepository::FILTER_DONE, null, $dateType);
            $data['stats']['rdv']['count_day_fna'] = $data['stats']['rdv']['count_day'] - $data['stats']['rdv']['count_day_valid'];
            // CONS NB RECUP
            $data['stats']['rdv']['count_recup'] = $repo->getConsultantRecupCount($begin, $end, StatRepository::SUPPORT_EXCLUDE, $consultant);

            // SUIVIS CA JM
            $data['stats']['follow']['turnover_day'] = $repo->getConsultantTurnover($begin, $end, false, $consultant, StatRepository::SUPPORT_INCLUDE);
            // SUIVIS CA IMP
            $sumBillingDayFollow = $repo->getStandardDateIntervalBilling($begin, $end, StatRepository::SUPPORT_INCLUDE, $consultant, null, $dateType);
            $turnoverDoneDayFollow = $data['stats']['follow']['turnover_day'];
            $data['stats']['follow']['turnover_day_fna'] = max($sumBillingDayFollow - $turnoverDoneDayFollow, 0);
            // SUIVIS CA RECUP
            $data['stats']['follow']['turnover_recup'] = $repo->getConsultantTurnover($begin, $end, true, $consultant, StatRepository::SUPPORT_INCLUDE);
            // SUIVIS NB JM
            $data['stats']['follow']['count_day_valid'] = $repo->getConsultantRdvCount($begin, $end, StatRepository::SUPPORT_INCLUDE, $consultant, StatRepository::FILTER_VALID, null, $dateType);
            // SUIVIS NB FNA
            $data['stats']['follow']['count_day'] = $repo->getConsultantRdvCount($begin, $end, StatRepository::SUPPORT_INCLUDE, $consultant, StatRepository::FILTER_DONE, null, $dateType);
            $data['stats']['follow']['count_day_fna'] = $data['stats']['follow']['count_day'] - $data['stats']['follow']['count_day_valid'];
            // SUIVIS NB RECUP
            $data['stats']['follow']['count_recup'] = $repo->getConsultantRecupCount($begin, $end, StatRepository::SUPPORT_INCLUDE, $consultant);
        }
        if ($data['averages'] === true) {
            $data['averages'] = array();
            // MOYENNE REALISEE
            $ca_real = $data['stats']['rdv']['turnover_day'] + $data['stats']['rdv']['turnover_day_fna'];
            $cn_real = $data['stats']['rdv']['count_day_valid'] + $data['stats']['rdv']['count_day_fna'];
            $data['averages']['real'] = $cn_real > 0 ? $ca_real / $cn_real : 0;
            // MOYENNE PAIE
            $ca_rdv = $data['stats']['rdv']['turnover_day'] + $data['stats']['rdv']['turnover_recup'];
            $cn_rdv = $data['stats']['rdv']['count_day_valid'] + $data['stats']['rdv']['count_recup'];
            $data['averages']['paie'] = $cn_rdv > 0 ? $ca_rdv / $cn_rdv : 0;
            $data['averages']['paie_bonus'] = $this->getMoyennePaieBonus($data['averages']['paie']);
            // % D'APRÈS MOYENNE PAIE
            $data['averages']['paie_percent'] = null;
            if ($consultant !== null && $consultant->getSalaryStatus() !== null) {
                $s = $consultant->getSalaryStatus();
                if ($s === SalaryParameter::NATURE_AE) {
                    $data['averages']['paie_percent'] = $this->salaryManager->getPercentage($s, SalaryParameter::TYPE_CONSULTATION, $data['averages']['paie']);
                } elseif ($s === SalaryParameter::NATURE_EMPLOYEE) {
                    $data['averages']['paie_percent'] = $this->salaryManager->getPercentage($s, SalaryParameter::TYPE_CONSULTATION, $ca_real, $data['averages']['paie']);
                }
            }
            // MOYENNE RAPPEL
            $ca_follow = $data['stats']['follow']['turnover_day'] + $data['stats']['follow']['turnover_day_fna'];
            $cn_follow = $data['stats']['follow']['count_day_valid'] + $data['stats']['follow']['count_day_fna'];
            $data['averages']['follow'] = $cn_follow > 0 ? $ca_follow / $cn_follow : 0;
            // MOYENNE REALISEE + RAPPEL
            $data['averages']['real_follow'] = ($cn_real + $cn_follow) > 0 ? ($ca_real + $ca_follow) / ($cn_real + $cn_follow) : 0;
        }
        if ($data['counts'] === true) {
            $data['counts'] = array();
            // RDV TRAITES
            $data['counts']['rdv']['taken'] = $repo->getConsultantRdvCount($begin, $end, StatRepository::SUPPORT_EXCLUDE, $consultant);
            // RDV ANNULES
            $data['counts']['rdv']['cancelled'] = $repo->getConsultantRdvCount($begin, $end, StatRepository::SUPPORT_EXCLUDE, $consultant, StatRepository::FILTER_CANCELLED);
            // RDV 10MIN
            $data['counts']['rdv']['tenmin'] = $repo->getConsultantRdvCount($begin, $end, StatRepository::SUPPORT_EXCLUDE, $consultant, StatRepository::FILTER_10MIN);
            // RDV EN COURS
            $data['counts']['rdv']['processing'] = $repo->getStandardDateIntervalProcessing($begin, $end, StatRepository::SUPPORT_EXCLUDE, $consultant);
            // RDV FAITS
            $data['counts']['rdv']['done'] = $data['counts']['rdv']['taken'] - $data['counts']['rdv']['cancelled'] - $data['counts']['rdv']['tenmin'] - $data['counts']['rdv']['processing'];
            // Même résultat avec (plus lent)
            // $data['counts']['rdv']['done'] = $repo->getConsultantRdvCount($begin, $end, StatRepository::SUPPORT_EXCLUDE, $consultant, StatRepository::FILTER_DONE);
            // RDV % DE 10MIN
            $data['counts']['rdv']['tenmin_percent'] = $data['counts']['rdv']['done'] ? ($data['counts']['rdv']['tenmin'] * 100 / ($data['counts']['rdv']['done'] + $data['counts']['rdv']['tenmin'])) : 0;
            $data['counts']['rdv']['tenmin_bonus'] = $this->get10MinBonus($data['counts']['rdv']['tenmin_percent']);
            
            // SUIVIS TRAITES
            $data['counts']['follow']['taken'] = $repo->getConsultantRdvCount($begin, $end, StatRepository::SUPPORT_INCLUDE, $consultant);
            // SUIVIS ANNULES
            $data['counts']['follow']['cancelled'] = $repo->getConsultantRdvCount($begin, $end, StatRepository::SUPPORT_INCLUDE, $consultant, StatRepository::FILTER_CANCELLED);
            // SUIVIS FAITS
            $data['counts']['follow']['done'] = $data['counts']['rdv']['taken'] - $data['counts']['rdv']['cancelled'] - $data['counts']['rdv']['tenmin'];
            // SUIVIS 10MIN
            $data['counts']['follow']['tenmin'] = $repo->getConsultantRdvCount($begin, $end, StatRepository::SUPPORT_INCLUDE, $consultant, StatRepository::FILTER_10MIN);
            // SUIVIS EN COURS
            $data['counts']['follow']['processing'] = $repo->getStandardDateIntervalProcessing($begin, $end, StatRepository::SUPPORT_INCLUDE, $consultant);
            // SUIVIS FAITS
            $data['counts']['follow']['done'] = $data['counts']['follow']['taken'] - $data['counts']['follow']['cancelled'] - $data['counts']['follow']['tenmin'] - $data['counts']['follow']['processing'];
            // SUIVIS % 10MIN
            $data['counts']['follow']['tenmin_percent'] = $data['counts']['follow']['done'] ? ($data['counts']['follow']['tenmin'] * 100 / $data['counts']['follow']['done']) : 0;

            // BONUS/MALUS
            $data['counts']['tenmin_bonus'] = ($consultant !== null && $consultant->getSalaryStatus() !== null)
                ? $this->salaryManager->getPercentage($consultant->getSalaryStatus(), SalaryParameter::TYPE_BONUS, $data['counts']['rdv']['tenmin_percent'])
                : null;
        }
        if ($data['oppos'] === true) {
            $data['oppos'] = array();
            // OPPOS MONTANT
            $data['oppos']['rdv_amount'] = $repo->getStandardDateIntervalOppo($begin, $end, StatRepository::SUPPORT_EXCLUDE, $consultant);
            $data['oppos']['follow_amount'] = $repo->getStandardDateIntervalOppo($begin, $end, StatRepository::SUPPORT_INCLUDE, $consultant);
            // OPPOS COUNT RDV
            $cn_rdv_oppos = $repo->getCountRdvOppo($begin, $end, StatRepository::SUPPORT_EXCLUDE, $consultant);
            $data['oppos']['rdv_count'] = count($cn_rdv_oppos);
            $cn_rdv_oppos_enc = 0;
            foreach ($cn_rdv_oppos as $oppo) {
                $cn_rdv_oppos_enc += $oppo[1];
            }
            $data['oppos']['rdv_enc_count'] = $cn_rdv_oppos_enc;
            // OPPOS COUNT SUIVIS
            $cn_follow_oppos = $repo->getCountRdvOppo($begin, $end, StatRepository::SUPPORT_INCLUDE, $consultant);
            $data['oppos']['follow_count'] = count($cn_follow_oppos);
            $cn_follow_oppos_enc = 0;
            foreach ($cn_follow_oppos as $oppo) {
                $cn_follow_oppos_enc += $oppo[1];
            }
            $data['oppos']['follow_enc_count'] = $cn_follow_oppos_enc;
            // OPPOS FRAIS
            $data['oppos']['costs'] = ($cn_rdv_oppos_enc + $cn_follow_oppos_enc) * 25;
        }
        if ($data['products'] === true) {
            $data['products'] = array();
            // PRODUITS VENDUS
            $data['products'] = $repo->getConsultantProducts($begin, $end, $consultant);
        }
        if ($data['ca_stats'] === true) {
            $data['ca_stats'] = array();
            // TOTAL CA REALISE
            $data['ca_stats']['rdv']['real'] = $data['stats']['rdv']['turnover_day'] + $data['stats']['rdv']['turnover_day_fna'];
            $data['ca_stats']['follow']['real'] = $data['stats']['follow']['turnover_day'] + $data['stats']['follow']['turnover_day_fna'];
            $data['ca_stats']['total']['real'] = $data['ca_stats']['rdv']['real'] + $data['ca_stats']['follow']['real'];
            // TOTAL CA ENCAISSE
            $data['ca_stats']['rdv']['enc'] = $data['stats']['rdv']['turnover_day'] + $data['stats']['rdv']['turnover_recup'];
            $data['ca_stats']['follow']['enc'] = $data['stats']['follow']['turnover_day'] + $data['stats']['follow']['turnover_recup'];
            $data['ca_stats']['total']['enc'] = $data['ca_stats']['rdv']['enc'] + $data['ca_stats']['follow']['enc'];
            // TOTAL OPPOS
            $data['ca_stats']['rdv']['oppo'] = $repo->getStandardDateIntervalOppo($begin, $end, StatRepository::SUPPORT_EXCLUDE, $consultant);
            $data['ca_stats']['follow']['oppo'] = $repo->getStandardDateIntervalOppo($begin, $end, StatRepository::SUPPORT_INCLUDE, $consultant);
            $data['ca_stats']['total']['oppo'] = $data['ca_stats']['rdv']['oppo'] + $data['ca_stats']['follow']['oppo'];
            // TOTAL REFUND
            $data['ca_stats']['rdv']['refund'] = $repo->getStandardDateIntervalRefund($begin, $end, StatRepository::SUPPORT_EXCLUDE, $consultant);
            $data['ca_stats']['follow']['refund'] = $repo->getStandardDateIntervalRefund($begin, $end, StatRepository::SUPPORT_INCLUDE, $consultant);
            $data['ca_stats']['total']['refund'] = $data['ca_stats']['rdv']['refund'] + $data['ca_stats']['follow']['refund'];
            // TOTAL NET
            $data['ca_stats']['rdv']['net'] = $data['ca_stats']['rdv']['enc'] - $data['ca_stats']['rdv']['oppo'] - $data['ca_stats']['rdv']['refund'];
            $data['ca_stats']['follow']['net'] = $data['ca_stats']['follow']['enc'] - $data['ca_stats']['follow']['oppo'] - $data['ca_stats']['follow']['refund'];
            $data['ca_stats']['total']['net'] = $data['ca_stats']['rdv']['net'] + $data['ca_stats']['follow']['net'];
        }
        if ($data['salary'] === true) {
            $data['salary'] = array();
            if ($consultant instanceof Utilisateur && $consultant->getSalaryStatus() !== null) {
                // PAIE S/ CONSULT
                $rdv_ca = $data['ca_stats']['rdv']['net'] / 1.2;
                $rdv_pct = $data['averages']['paie_percent'] + $data['counts']['tenmin_bonus'];
                $data['salary']['consult'] = $rdv_ca * ($rdv_pct / 100);
                // PAIE S/ RAPPEL
                $follow_ca = ($data['ca_stats']['follow']['enc'] - $data['ca_stats']['follow']['oppo'] - $data['ca_stats']['follow']['refund']) / 1.2;
                $s = $consultant->getSalaryStatus();
                $follow_pct = $this->salaryManager->getPercentage($s, SalaryParameter::TYPE_FOLLOW, $data['ca_stats']['follow']['net'], $data['counts']['follow']['done']);
                $data['salary']['follow'] = $follow_ca * ($follow_pct / 100);
                // SALAIRE TOTAL
                $data['salary']['total'] = $data['salary']['consult'] + $data['salary']['follow'];
                // ABSENCES
                $data['salary']['absences'] = $repo->getUserJournalCount($begin, $end, $consultant, Journal::ABSENCE);
                // RETARDS
                $data['salary']['lateness'] = $repo->getUserJournalCount($begin, $end, $consultant, Journal::LATENESS);
            }
        }
        if ($data['bonus'] === true) {
            $data['bonus'] = array();
            if ($consultant instanceof Utilisateur && $consultant->getSalaryStatus() == null) {
                $total = 0;
                // PRIME MOYENNE PAIE
                $moyennePaie = $data['averages']['paie'];
                $tenMinPercent = $data['counts']['rdv']['tenmin_percent'];
                $moyennePaieBonus = $this->getMoyennePaieBonus($moyennePaie, $tenMinPercent);
                $data['averages']['paie_bonus'] = $moyennePaieBonus;
                $coef = isset($moyennePaieBonus) ? $moyennePaieBonus->getAmount() / 100 : 0;
                $data['bonus']['paie'] = $data['ca_stats']['rdv']['enc'] * $coef * 10;
                $total += $data['bonus']['paie'];
                // PRIME MOYENNE REA
                $data['bonus']['rea'] = $this->getReaBonus($begin, $end, $consultant);
                $total += $data['bonus']['rea'];
                // PRIME SUIVI
                $data['bonus']['suivi'] = $this->getSuiviBonus($data['ca_stats']['follow']['enc']);
                $total += $data['bonus']['suivi'];
                // PÉNALITÉ
                $repo = $this->em->getRepository('KGCStatBundle:BonusParameter');
                $penalty = $repo->getBonus(BonusParameter::PSYCHIC_PENALTY, $begin, $end, $consultant);
                $data['bonus']['penalty'] = isset($penalty) ? -$penalty->getAmount() : 0;
                $total += $data['bonus']['penalty'];
                // TOTAL
                $data['bonus']['total'] = $total;
            }
        }

        return $data;
    }

    protected function details(array $data, \DateTime $begin, \DateTime $end, $config)
    {
        $repo = $this->getStatRepository();
        $title = "";

        // COUNTS
        if($config['details'] === 'count_rdv_taken') {
            $data = $repo->getConsultantRdvList($begin, $end, StatRepository::SUPPORT_EXCLUDE, $config['consultant']);
            $title = 'traitées';
        }
        elseif($config['details'] === 'count_follow_taken') {
            $data = $repo->getConsultantRdvList($begin, $end, StatRepository::SUPPORT_INCLUDE, $config['consultant']);
            $title = 'de suivi traitées';
        }
        elseif($config['details'] === 'count_rdv_cancelled') {
            $data = $repo->getConsultantRdvList($begin, $end, StatRepository::SUPPORT_EXCLUDE, $config['consultant'], StatRepository::FILTER_CANCELLED);
            $title = 'annulées';
        }
        elseif($config['details'] === 'count_follow_cancelled') {
            $data = $repo->getConsultantRdvList($begin, $end, StatRepository::SUPPORT_INCLUDE, $config['consultant'], StatRepository::FILTER_CANCELLED);
            $title = 'de suivi annulées';
        }
        elseif($config['details'] === 'count_rdv_tenmin') {
            $data = $repo->getConsultantRdvList($begin, $end, StatRepository::SUPPORT_EXCLUDE, $config['consultant'], StatRepository::FILTER_10MIN);
            $title = '10 minutes';
        }
        elseif($config['details'] === 'count_follow_tenmin') {
            $data = $repo->getConsultantRdvList($begin, $end, StatRepository::SUPPORT_INCLUDE, $config['consultant'], StatRepository::FILTER_10MIN);
            $title = 'de suivi 10 minutes';
        }
        elseif($config['details'] === 'count_rdv_processing') {
            $data = $repo->getStandardDateIntervalProcessing($begin, $end, StatRepository::SUPPORT_EXCLUDE, $config['consultant']);
            $title = 'en cours';
        }
        elseif($config['details'] === 'count_follow_processing') {
            $data = $repo->getStandardDateIntervalProcessing($begin, $end, StatRepository::SUPPORT_INCLUDE, $config['consultant']);
            $title = 'de suivi en cours';
        }
        elseif($config['details'] === 'count_rdv_done') {
            $data = $repo->getConsultantRdvList($begin, $end, StatRepository::SUPPORT_EXCLUDE, $config['consultant'], StatRepository::FILTER_DONE);
            $title = 'validées';
        }
        elseif($config['details'] === 'count_follow_done') {
            $data = $repo->getConsultantRdvList($begin, $end, StatRepository::SUPPORT_INCLUDE, $config['consultant'], StatRepository::FILTER_DONE);
            $title = 'de suivi validées';
        }

        // OPPOS
        elseif($config['details'] === 'oppos_rdv_count') {
            $data = $repo->getRdvOppoList($begin, $end, StatRepository::SUPPORT_EXCLUDE, $config['consultant']);
            $title = 'en opposition';
        }
        elseif($config['details'] === 'oppos_follow_count') {
            $data = $repo->getRdvOppoList($begin, $end, StatRepository::SUPPORT_INCLUDE, $config['consultant']);
            $title = 'de suivi en opposition';
        }
        elseif($config['details'] === 'oppos_rdv_amount') {
            $data = $this->buildCaDetails(
                $repo->getOppoList($begin, $end, StatRepository::SUPPORT_EXCLUDE, $config['consultant']));
            $title = 'opposé';
        }
        elseif($config['details'] === 'oppos_follow_amount') {
            $data = $this->buildCaDetails(
                $repo->getOppoList($begin, $end, StatRepository::SUPPORT_INCLUDE, $config['consultant']));
            $title = 'opposé des consultations de suivi';
        }

        // CA TABLE
        elseif($config['details'] === 'stat_rdv_valid') {
            $data = $repo->getConsultantRdvList($begin, $end, StatRepository::SUPPORT_EXCLUDE, $config['consultant'], StatRepository::FILTER_VALID);
            $title = 'encaissées';
        }
        elseif($config['details'] === 'stat_follow_valid') {
            $data = $repo->getConsultantRdvList($begin, $end, StatRepository::SUPPORT_INCLUDE, $config['consultant'], StatRepository::FILTER_VALID);
            $title = 'de suivi encaissées';
        }
        elseif($config['details'] === 'stat_rdv_fna') {
            $data = $repo->getConsultantRdvFnaList($begin, $end, StatRepository::SUPPORT_EXCLUDE, $config['consultant']);
            $title = 'impayées';
        }
        elseif($config['details'] === 'stat_follow_fna') {
            $data = $repo->getConsultantRdvFnaList($begin, $end, StatRepository::SUPPORT_INCLUDE, $config['consultant']);
            $title = 'de suivi impayées';
        }
        elseif($config['details'] === 'stat_rdv_recup') {
            $data = $repo->getConsultantRecupList($begin, $end, StatRepository::SUPPORT_EXCLUDE, $config['consultant']);
            $title = 'récupérées';
        }
        elseif($config['details'] === 'stat_follow_recup') {
            $data = $repo->getConsultantRecupList($begin, $end, StatRepository::SUPPORT_INCLUDE, $config['consultant']);
            $title = 'de suivi récupérées';
        }

        elseif($config['details'] === 'stat_turnover_rdv') {
            $data = $this->buildCaDetails(
                $repo->getConsultantTurnoverList($begin, $end, false, $config['consultant'], StatRepository::SUPPORT_EXCLUDE));
            $title = 'encaissé';
        }
        elseif($config['details'] === 'stat_turnover_follow') {
            $data = $this->buildCaDetails(
                $repo->getConsultantTurnoverList($begin, $end, false, $config['consultant'], StatRepository::SUPPORT_INCLUDE));
            $title = 'encaissé des consultations de suivi';
        }
        elseif($config['details'] === 'stat_turnover_rdv_fna') {
            $data =  $this->buildCaDetails(
                $repo->getConsultantFnaList($begin, $end, StatRepository::SUPPORT_EXCLUDE, $config['consultant']));
            $title = 'impayé';
        }
        elseif($config['details'] === 'stat_turnover_follow_fna') {
            $data = $this->buildCaDetails(
                $repo->getConsultantFnaList($begin, $end, StatRepository::SUPPORT_INCLUDE, $config['consultant']));
            $title = 'impayé des consultations de suivi';
        }
        elseif($config['details'] === 'stat_turnover_rdv_recup') {
            $data = $this->buildCaDetails(
                $repo->getConsultantTurnoverList($begin, $end, true, $config['consultant'], StatRepository::SUPPORT_EXCLUDE));
            $title = 'récupéré';
        }
        elseif($config['details'] === 'stat_turnover_follow_recup') {
            $data = $this->buildCaDetails(
                $repo->getConsultantTurnoverList($begin, $end, true, $config['consultant'], StatRepository::SUPPORT_EXCLUDE));
            $title = 'récupéré des consultations de suivi';
        }


        return ['details' => $data, 'title' => $title];
    }


    protected function buildCaDetails($dataToDecorate) {
        $result = [];
        foreach ($dataToDecorate as $v) {
            $result[] = ['rdv' => $v['id'], 'nb' => $v['nb_enc'], 'amount' => $v['nb'], 'user' => $v['prenom'].' '.$v['nom']];
        }
        return $result;
    }
    
    /**
     * @param float $moyennePaie
     * 
     * @return float
     */
    protected function getMoyennePaieBonus($moyennePaie, $tenMinPercent = null)
    {
        $repo = $this->em->getRepository('KGCStatBundle:BonusParameter');
        $moyennePaieBonusList = $repo->getBonus(BonusParameter::PSYCHIC_MOYENNEPAIE);
        $moyennePaieBonus = null;
        
        foreach($moyennePaieBonusList as $bonus){
            if($moyennePaie >= $bonus->getObjective()){
                if(isset($tenMinPercent)){
                    if($tenMinPercent >= $bonus->getSecObjective()){
                        $moyennePaieBonus = $bonus;
                    }
                } else {
                    $moyennePaieBonus = $bonus;
                }
            }
        }
        
        return $moyennePaieBonus;
    }
    
    /**
     * @param float $caEncSuivi
     * 
     * @return float
     */
    protected function getSuiviBonus($caEncSuivi)
    {
        $repo = $this->em->getRepository('KGCStatBundle:BonusParameter');
        $suiviBonusList = $repo->getBonus(BonusParameter::PSYCHIC_SUIVI);
        $amount = 0;
        
        foreach($suiviBonusList as $bonus){
            if($caEncSuivi >= $bonus->getObjective()){
                $amount = $bonus->getAmount();
            }
        }
        
        return $amount;
    }
    
    /**
     * @param \DateTime $begin
     * @param \DateTime $end
     * @param Utilisateur $consultant
     * 
     * @return float
     */
    protected function getReaBonus(\DateTime $begin, \DateTime $end, Utilisateur $consultant)
    {
        $repo = $this->em->getRepository('KGCStatBundle:BonusParameter');
        $weeks = $this->getWeekIntervalfromDates($begin, $end);
        $amount = 0;
        foreach($weeks as $weekInterval){
            $week_begin = $weekInterval[0];
            $week_end = $weekInterval[1];
            if($week_end <= $end){
                $tenmin_percent = $this->calculTenminPercent($week_begin, $week_end, $consultant);
                if($tenmin_percent < 25){
                    $moyenneRea = $this->calculMoyenneRea($week_begin, $week_end, $consultant);
                    $bonusRea = $repo->getBonus(BonusParameter::PSYCHIC_HEBDO, $week_begin, $week_end, $consultant);
                    if(isset($bonusRea) && $moyenneRea >= $bonusRea->getObjective()){
                        $amount += $bonusRea->getAmount();
                    }
                }
            }
        }
        
        return $amount;
    }

    /**
     * @param float $tenMinPercent
     * 
     * @return float
     */
    protected function get10MinBonus($tenMinPercent)
    {
        $repo = $this->em->getRepository('KGCStatBundle:BonusParameter');
        $tenMinBonusList = $repo->getBonus(BonusParameter::PSYCHIC_10MIN);
        $tenMinBonus = null;
        
        foreach($tenMinBonusList as $bonus){
            if($tenMinPercent >= $bonus->getObjective()){
                $tenMinBonus = $bonus;
            }
        }
        
        return $tenMinBonus;
    }
    
    /**
     * @param \DateTime $begin
     * @param \DateTime $end
     * 
     * @return float
     */
    protected function calculMoyenneRea(\DateTime $begin, \DateTime $end, Utilisateur $consultant)
    {
        $repo = $this->getStatRepository();
        $ca_real = $repo->getStandardDateIntervalBilling($begin, $end, StatRepository::SUPPORT_EXCLUDE, $consultant, null, StatisticColumnType::DATE_TYPE_CONSULTATION);
        $cn_real = $repo->getConsultantRdvCount($begin, $end, StatRepository::SUPPORT_EXCLUDE, $consultant, StatRepository::FILTER_DONE, null, StatisticColumnType::DATE_TYPE_CONSULTATION);
        $average = $cn_real > 0 ? $ca_real / $cn_real : 0;
        
        return $average;
    }
    
    /**
     * @param \DateTime $begin
     * @param \DateTime $end
     * 
     * @return float
     */
    protected function calculTenminPercent(\DateTime $begin, \DateTime $end, Utilisateur $consultant)
    {
        $repo = $this->getStatRepository();
        
        $done = $repo->getConsultantRdvCount($begin, $end, StatRepository::SUPPORT_EXCLUDE, $consultant, StatRepository::FILTER_DONE);
        $tenmin =  $repo->getConsultantRdvCount($begin, $end, StatRepository::SUPPORT_EXCLUDE, $consultant, StatRepository::FILTER_10MIN);
        $tenmin_percent = $done ? ($tenmin * 100 / ($done + $tenmin)) : 0;
        
        return $tenmin_percent;
    }

    /**
     * @param array $config
     *
     * @return array
     *
     * @throws \Exception
     */
    public function calculate(array $config = [])
    {
        $data = [
            'stats' => array_key_exists('get_stats', $config) ? ($config['get_stats'] === true ?: $config['get_stats']) : false,
            'averages' => array_key_exists('get_averages', $config),
            'products' => array_key_exists('get_products', $config),
            'ca_stats' => array_key_exists('get_ca_stats', $config),
            'oppos' => array_key_exists('get_oppos', $config),
            'counts' => array_key_exists('get_counts', $config),
            'salary' => array_key_exists('get_salary', $config),
            'bonus' => array_key_exists('get_bonus', $config),
        ];

        $begin = new \DateTime($config['begin']->format('Y-m-d').' '.parent::DAY_TIME_REFERENCE);
        $end = new \DateTime($config['end']->format('Y-m-d').' '.parent::DAY_TIME_REFERENCE);
        $end->add(new \DateInterval('P1D'));

        if(array_key_exists('details', $config) && $config['details'] !== false) {
            $data = $this->details($data, $begin, $end, $config);
        }
        else {
            $data = $this->getStatsIfNeeded($data, $begin, $end, $config['consultant'], StatisticColumnType::DATE_TYPE_CONSULTATION);
        }

        return $data;
    }
}
