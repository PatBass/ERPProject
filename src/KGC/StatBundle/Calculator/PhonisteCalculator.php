<?php
// src/KGC/StatBundle/Calculator/PhonisteCalculator.php

namespace KGC\StatBundle\Calculator;

use JMS\DiExtraBundle\Annotation as DI;
use KGC\StatBundle\Entity\BonusParameter;
use KGC\StatBundle\Entity\PhonisteParameter;
use KGC\StatBundle\Ruler\PhonisteRuler;
use KGC\UserBundle\Entity\Utilisateur;

/**
 * Class PhonisteCalculator.
 *
 * @DI\Service("kgc.stat.calculator.phoniste", parent="kgc.stat.calculator")
 */
class PhonisteCalculator extends Calculator
{
    /**
     * @var PhonisteParameter
     */
    protected $phonisteParams;
    
    /**
     * @var PhonisteRuler
     */
    protected $phonisteRuler;

    /**
     * @param PhonisteRuler $phonisteRuler
     *
     * @DI\InjectParams({
     *      "phonisteRuler" = @DI\Inject("kgc.stat.ruler.phoning"),
     * })
     */
    public function setPhonisteRuler(PhonisteRuler $phonisteRuler)
    {
        $this->phonisteRuler = $phonisteRuler;
    }

    /**
     * Return Phoning params from database.
     *
     * @return PhonisteParameter
     */
    protected function getPhonisteParams()
    {
        return $this->phonisteRuler->getPhonisteParams();
    }

    /**
     * @throws \Exception
     */
    protected function getUserOrError()
    {
        if (null === ($currentUser = $this->getUser())) {
            throw new \Exception('No user found !!!');
        }

        return $currentUser;
    }

    /**
     * @param $currentUser
     * @param array $data
     *
     * @return array|int
     */
    protected function getPhonisteDateIntervalRdv($currentUser, array $data)
    {
        list($begin, $end) = $this->getTodayDateInterval();
        $done = $this->getStatRepository()->getPhonisteDateIntervalRdv($currentUser->getId(), $begin, $end, true);
        $data['done'] = $done;

        list($begin, $end) = $this->getCurrentMonthDateInterval();
        $doneMonth = $this->getStatRepository()->getPhonisteDateIntervalRdv($currentUser->getId(), $begin, $end);
        $total = 0;
        $totalBonus = 0;
        foreach ($doneMonth as $item) {
            $nb = (int) $item['nb'];
            $total += $nb;
            $totalBonus += $this->getCurrentBonus($this->getPhonisteParams(), $nb);
        }

        $data['done_month'] = $total;
        $data['bonus_month'] = $totalBonus;

        return $data;
    }
    
    /**
     * @param $getObjective
     * @param array             $data
     * @param PhonisteParameter $params
     * @param $done
     *
     * @return array
     */
    protected function addObjectiveIfNeeded($getObjective, array $data, PhonisteParameter $params, $done)
    {
        if ($getObjective) {
            $data['objective'] = $params->getMaxObjectivePerDay();
            $data['rate'] = ($done / $params->getMaxObjectivePerDay()) * 100;
            $data['before_bonus'] = max($params->getMaxObjectivePerDay() - $done, 0);
            $data['first'] = [
                'rate' => round(max($done, 0) / $params->getFirstThreshold() * 100, 2),
                'before_bonus' => max($params->getFirstThreshold() - $done, 0),
            ];
            $data['second'] = [
                'rate' => round(max(($done - $params->getFirstThreshold()), 0) / ($params->getSecondThreshold() - $params->getFirstThreshold()) * 100, 2),
                'before_bonus' => max($params->getSecondThreshold() - $done, 0),
            ];
            $data['third'] = [
                'rate' => round(max(($done - $params->getSecondThreshold()), 0) / ($params->getThirdThreshold() - $params->getSecondThreshold()) * 100, 2),
                'before_bonus' => max($params->getThirdThreshold() - $done, 0),
            ];
        }

        return $data;
    }

    /**
     * @param $getValidated
     * @param array $data
     * @param $currentUser
     *
     * @return array
     */
    protected function addValidatedIfNeeded($getValidated, array $data, $currentUser)
    {
        if ($getValidated) {
            list($begin, $end) = $this->getTodayDateInterval();
            $data['validated'] = (int) $this
                ->getStatRepository()
                ->getPhonisteDateIntervalValidatedRdv($currentUser->getId(), $begin, $end)
            ;

            list($begin, $end) = $this->getCurrentMonthDateInterval();
            $data['validated_month'] = (int) $this
                ->getStatRepository()
                ->getPhonisteDateIntervalValidatedRdv($currentUser->getId(), $begin, $end)
            ;
        }

        return $data;
    }
    
    /**
     * @param PhonisteParameter $params
     * @param null              $done
     *
     * @return float|int
     *
     * @throws \Exception
     */
    public function getCurrentBonus(PhonisteParameter $params = null, $done = null)
    {
        $params = $params ?: $this->getPhonisteParams();

        if (null === $done) {
            $currentUser = $this->getUserOrError();
            $data = [];
            $data = $this->getPhonisteDateIntervalRdv($currentUser, $data);
            $done = $data['done'];
        }

        $result = $this->phonisteRuler->getCurrentBonus($params, $done);

        return end($result);
    }
        
    /**
     * @param $currentUser
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return array
     */
    protected function getPhonisteQuantityBonus($currentUser, \Datetime $begin, \Datetime $end)
    {
        $quantityBonus = $this->phonisteRuler->getBonusParameters(BonusParameter::PHONISTE_QUANTITY);
        
        $total = $this->getStatRepository()->getPhonisteDateIntervalRdv($currentUser->getId(), $begin, $end, true);
        $bonus = $total * $quantityBonus->getAmount();
        
        return [ $total, $bonus ];
    }
        
    /**
     * @param Utilisateur $currentUser
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return array
     */
    protected function getPhonisteQualityBonus(Utilisateur $currentUser, \Datetime $begin, \Datetime $end)
    {
        $qualityBonus = $this->phonisteRuler->getBonusParameters(BonusParameter::PHONISTE_QUALITY);
        
        $req = $this->getStatRepository()->getPhonisteDateIntervalValidatedRdv($currentUser->getId(), $begin, $end);
        $count = $req;
        $bonus = $count * $qualityBonus->getAmount();
        
        return [ $count, $bonus ];
    }
    
    /**
     * @param Utilisateur $currentUser
     * @param \Datetime $w_begin
     * @param \Datetime $w_end
     * @param \Datetime $countLimit
     * 
     * @return array
     */
    protected function getPhonisteHebdoBonus(Utilisateur $currentUser, \Datetime $w_begin, \Datetime $w_end, \DateTime $countLimit)
    {
        $end = $countLimit->format('Ymd') < $w_end->format('Ymd') ? $countLimit : $w_end;
        $nbRdv = $this->getStatRepository()->getPhonisteDateIntervalRdv($currentUser->getId(), $w_begin, $end, true);
        $hebdoBonus = $this->phonisteRuler->getBonusParameters(BonusParameter::PHONISTE_HEBDO, $w_begin, $w_end, $currentUser);
        $amount = 0;
        if($hebdoBonus && ($nbRdv >= $hebdoBonus->getObjective())){
            $amount = $hebdoBonus->getAmount();
        }
        
        return [ $nbRdv, $amount, $hebdoBonus ];
    }
    
    /**
     * @param $currentUser
     * @param array $data
     *
     * @return array
     */
    protected function getPhonisteBonus(Utilisateur $currentUser, array $data, \Datetime $date)
    {
        $totalBonus = 0;
        // Intervalles de dates
        $sql_date = clone $date;
        $sql_date = $sql_date->add(new \DateInterval('P01D'));
        list($d_begin, $d_end) = $this->getDayIntervalFromDate($date);
        list($w_begin, $w_end) = $this->getSQLWeekInterval($date);
        list($m_begin, $m_end, $true_m_end) = $this->getMonthIntervalFromDate($date, true);
        
        // Prime Quantité - Jour
        list($d_quantity_count, $d_quantity_amount) = $this->getPhonisteQuantityBonus($currentUser, $d_begin, $d_end);
        $data['day_quantity_count'] = $d_quantity_count;
        $data['day_quantity_amount'] = $d_quantity_amount;
        
        // Prime Quantité - Mois
        list($m_quantity_count, $m_quantity_amount) = $this->getPhonisteQuantityBonus($currentUser, $m_begin, $m_end);
        $data['month_quantity_count'] = $m_quantity_count;
        $data['month_quantity_amount'] = $m_quantity_amount;
        
        // Prime Qualité - Jour
        list($d_quality_count, $d_quality_amount) = $this->getPhonisteQualityBonus($currentUser, $d_begin, $d_end);
        $data['day_quality_count'] = $d_quality_count;
        $data['day_quality_amount'] = $d_quality_amount;
        
        // Prime Qualité - Mois
        list($m_quality_count, $m_quality_amount) = $this->getPhonisteQualityBonus($currentUser, $m_begin, $m_end);
        $data['month_quality_count'] = $m_quality_count;
        $data['month_quality_amount'] = $m_quality_amount;
        
        // Prime Hebdo - Semaine
        list($w_hebdo_count, $w_hebdo_amount) = $this->getPhonisteHebdoBonus($currentUser, $w_begin, $w_end, $sql_date);
        $data['week_hebdo_count'] = $w_hebdo_count;
        $data['week_hebdo_amount'] = $w_hebdo_amount;
        
        // Prime Hebdo - Mois
        $weeks = $this->getWeekIntervalOfTheMonth($date);
        $m_hebdo_count = $m_hebdo_amount = 0;
        foreach($weeks as $weekInterval){
            $week_begin = $weekInterval[0];
            $week_end = $weekInterval[1];
            if(($week_end->format('Ymd') - 1) <= $true_m_end->format('Ymd')){
                // on compte la prime hebdo pour le mois en cours quand le dimanche fais partie du mois
                $bonus = $this->getPhonisteHebdoBonus($currentUser, $week_begin, $week_end, $sql_date);
                $m_hebdo_count += $bonus[0];
                $m_hebdo_amount += $bonus[1];
            }
        }
        $data['month_hebdo_count'] = $m_hebdo_count;
        $data['month_hebdo_amount'] = $m_hebdo_amount;
        
        // Prime Challenge
        $m_challenge_amount = 0;
        $challengeBonus = $this->phonisteRuler->getBonusParameters(BonusParameter::PHONISTE_CHALLENGE, $m_begin, $m_end, $currentUser);
        if(isset($challengeBonus) && $date >= $challengeBonus->getDate()){
            $m_challenge_amount = $challengeBonus->getAmount();
        }
        $data['challenge_bonus'] = $m_challenge_amount;
        
        // Pénalité
        $m_penalty_amount = 0;
        $penalty = $this->phonisteRuler->getBonusParameters(BonusParameter::PHONISTE_PENALTY, $m_begin, $m_end, $currentUser);
        if(isset($penalty) && $date >= $penalty->getDate()){
            $m_penalty_amount = $penalty->getAmount();
        }
        $data['penalty'] = $m_penalty_amount;
        
        // Total Primes du mois
        $totalBonus += $m_quantity_amount;
        $totalBonus += $m_quality_amount;
        $totalBonus += $m_hebdo_amount;
        $totalBonus += $m_challenge_amount;
        $totalBonus -= $m_penalty_amount;
        $data['total_bonus'] = $totalBonus;
        
        return $data;
    }
    
    /**
     * @param boolean $needed
     * @param $currentUser
     * @param array $data
     *
     * @return array
     */
    protected function addCircleObjectivesDataIfNeeded($needed, Utilisateur $currentUser, array $data)
    {
        if($needed){
            list($d_begin, $d_end) = $this->getTodayDateInterval();
            list($w_begin, $w_end) = $this->getSQLWeekInterval();

            list($d_quantity_count, $d_quantity_amount) = $this->getPhonisteQuantityBonus($currentUser, $d_begin, $d_end);
            $data['rdv_added'] = $d_quantity_count;
            $data['quantity_bonus'] = $d_quantity_amount;

            list($d_quality_count, $d_quality_amount) = $this->getPhonisteQualityBonus($currentUser, $d_begin, $d_end);
            $data['rdv_validated'] = $d_quality_count;
            $data['quality_bonus'] = $d_quality_amount;

            $hebdoBonusData = $this->getPhonisteHebdoBonus($currentUser, $w_begin, $w_end, $d_end);
            $data['week_rdv_added'] = $hebdoBonusData[0];
            $hebdoBonus = $hebdoBonusData[2];
            $data['hebdo_objective'] = isset($hebdoBonus) ? $hebdoBonus->getObjective() : 0;
            $data['hebdo_amount'] = isset($hebdoBonus) ? $hebdoBonus->getAmount() : 0;
        }
        
        return $data;
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
        $params = $this->getPhonisteParams();

        $data = [
            'date' => new \DateTime(),
            'params' => $params,
            'done' => null,
            'validated' => null,
            'done_month' => null,
            'validated_month' => null,
        ];

        $data['date'] = isset($config['date']) ? $config['date'] : $data['date'];
        
        $getCount = array_key_exists('get_count', $config) && $config['get_count'];
        $getObjective = array_key_exists('get_objective', $config) && $config['get_objective'];
        $getValidated = array_key_exists('get_validated', $config) && $config['get_validated'];
        $getBonus = array_key_exists('get_bonus', $config) && $config['get_bonus'];
        $getCircleObjectivesData = array_key_exists('get_circle_objective_data', $config) && $config['get_circle_objective_data'];

        $currentUser = $this->getUserOrError();

        if ($getCount) {
            if($getBonus){
                $data = $this->getPhonisteBonus($currentUser, $data, $data['date']);
            } else {
                $data = $done = $this->getPhonisteDateIntervalRdv($currentUser, $data);
            }
            $data = $this->addObjectiveIfNeeded($getObjective, $data, $params, $data['done']);
            $data = $this->addValidatedIfNeeded($getValidated, $data, $currentUser);
            $data = $this->addCircleObjectivesDataIfNeeded($getCircleObjectivesData, $currentUser, $data);
        }

        return $data;
    }
}
