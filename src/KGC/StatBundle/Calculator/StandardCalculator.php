<?php

namespace KGC\StatBundle\Calculator;

use JMS\DiExtraBundle\Annotation as DI;
use KGC\RdvBundle\Entity\Support;
use KGC\StatBundle\Decorator\DecoratorInterface;
use KGC\StatBundle\Form\SortingColumnType;
use KGC\StatBundle\Form\StatisticColumnType;
use KGC\StatBundle\Form\StatScopeType;
use KGC\StatBundle\Repository\StatRepository;

/**
 * Class StandardCalculator.
 *
 * @DI\Service("kgc.stat.calculator.standard", parent="kgc.stat.calculator.specific")
 */
class StandardCalculator extends SpecificCalculator
{
    /**
     * @var DecoratorInterface
     */
    private $csvDecorator;

    /**
     * @param DecoratorInterface $csvDecorator
     *
     * @DI\InjectParams({
     *      "csvDecorator" = @DI\Inject("kgc.stat.decorator.csv"),
     * })
     */
    public function setCsvcDecorator(DecoratorInterface $csvDecorator)
    {
        $this->csvDecorator = $csvDecorator;
    }

    /**
     * @param $getStats
     * @param array     $data
     * @param \Datetime $date
     *
     * @return array
     */
    protected function getStatsIfNeeded($getStats, array $data, \Datetime $date)
    {
        $repo = $this->getStatRepository();

        if ($getStats) {
            /* ### DAY STATS ### */
            list($begin, $end) = $this->getDayIntervalFromDate($date);

            /* ## START OPPO ## */
            $data['stats']['oppo_day'] = $repo->getStandardDateIntervalOppo($begin, $end);
            /* ## END OPPO ## */

            /* ## START REFUND ## */
            $data['stats']['refund_day'] = $repo->getStandardDateIntervalRefund($begin, $end);
            /* ## END REFUND ## */

            /* ## START CA ENCAISSE ## */
            //Sum of securization in given interval
            $data['stats']['turnover_day_secure'] = $repo->getStandardDateIntervalTurnoverSecure($begin, $end);
            // Somme des encaissement DONE, pour l'intervalle donné, et des sécurisations
            // Include cancelled, but not yet opposed
            // Substracte opposed this day
            $data['stats']['turnover_day'] =
                $repo->getStandardDateIntervalTurnover($begin, $end)
                 + $data['stats']['turnover_day_secure']
                 - $data['stats']['oppo_day']
                 - $data['stats']['refund_day']
                 ;
            /* ## END CA ENCAISSE ## */

            /* ## START dont RECUP ## */
            // Somme des encaissements en récup
            $data['stats']['turnover_day_recup'] = $repo->getStandardDateIntervalTurnover($begin, $end, true);
            /* ## END dont RECUP ## */

            /* ## START CA IMPAYÉ  ## */
            // Somme des tarifications des consultations de l'intervalle donné,
            $sumBillingDay = $repo->getStandardDateIntervalBilling($begin, $end);
            // la somme de ce qui a déjà été encaissé dans l'intervalle donné.
            $turnoverDoneDay = $repo->getStandardDateIntervalTurnover($begin, $end, false);
            // valeur des Recup du Mois même
            $recupMMDay = $repo->getStandardDateIntervalTurnover($begin, $end, null, false, true);
            // Diff between two previous values => unpaid consultations
            $data['stats']['turnover_day_fna'] = max($sumBillingDay - $turnoverDoneDay - $recupMMDay, 0);
            /* ## END CA IMPAYÉ  ## */

            /* ## START Moyenne ## */
            /* # Start Excluding support # */
            //Sum of billing for the day
            $sumBillingDay = $repo->getStandardDateIntervalBilling($begin, $end, StatRepository::SUPPORT_EXCLUDE);
            //Number of billing for the day (consultation with payment)
            $countBillingDay = $repo->getStandardDateIntervalDoneNotFree($begin, $end, StatRepository::SUPPORT_EXCLUDE);
            //if there is at least one billing, calculate the average value
            $data['stats']['mean_day'] = $countBillingDay ? ($sumBillingDay / $countBillingDay) : 0;
            $firstDate = new \Datetime($date->format('Y-m-d '.self::DAY_TIME_REFERENCE));
            $lastDate = clone $firstDate;
            $config_spe = [
                'begin' =>  $firstDate,
                'end' => $lastDate,
                'websites' => [],
                'sources' => [],
                'urls' => [],
                'codesPromo' => [],
                'supports' => [],
                'phonists' => [],
                'consultants' => [],
                'ca' => StatisticColumnType::$CA_CHOICES,
                'rdv' => StatisticColumnType::$RDV_LIGHTED_CHOICES,
                'dateType' => StatisticColumnType::DATE_TYPE_CONSULTATION,
                'statScope' => StatScopeType::KEY_CONSULTATION,
                'sorting_column' => StatisticColumnType::CODE_RDV_TOTAL,
                'sorting_dir' => SortingColumnType::KEY_DESC,
            ];
            $config_spe_follow = $config_spe;
            $config_spe_follow['statScope'] =  StatScopeType::KEY_FOLLOW;
            $result_spe = $this->calculateSpecific($config_spe + ['specific_full' => 1]);
            $result_spe_follow = $this->calculateSpecific($config_spe_follow + ['specific_full' => 1]);
            $data['stats']['mean_day_specific'] = isset($result_spe[StatisticColumnType::CODE_CA_AVERAGE_REAL]) ? $result_spe[StatisticColumnType::CODE_CA_AVERAGE_REAL]['value'] : $data['stats']['mean_day'];
            /* # End Excluding support # */
            /* # Start Including support # */
            //Sum of billing for the day
            $sumBillingDay = $repo->getStandardDateIntervalBilling($begin, $end, StatRepository::SUPPORT_INCLUDE);
            //Number of billing for the day (consultation with payment)
            $countBillingDay = $repo->getStandardDateIntervalDoneNotFree($begin, $end, StatRepository::SUPPORT_INCLUDE);
            //if there is at least one billing, calculate the average value
            $data['stats']['mean_day_follow'] = $countBillingDay ? ($sumBillingDay / $countBillingDay) : 0;
            $data['stats']['mean_day_follow_specific'] = isset($result_spe_follow[StatisticColumnType::CODE_CA_AVERAGE_REAL]) ? $result_spe_follow[StatisticColumnType::CODE_CA_AVERAGE_REAL]['value'] : $data['stats']['mean_day_follow'];
            /* # End Including support # */
            /* ## END Moyenne (Including support) ## */

            /* ## START Total traité ## */
            //All consultation for the day
            $data['stats']['taken_day'] = $repo->getStandardDateIntervalTakenCount($begin, $end, StatRepository::SUPPORT_EXCLUDE);
            $data['stats']['taken_day_specific'] = isset($result_spe[StatisticColumnType::CODE_RDV_TREATED]) ? $result_spe[StatisticColumnType::CODE_RDV_TREATED]['value'] : $data['stats']['taken_day'];
            //All support consultation for the day
            $data['stats']['taken_day_follow'] = $repo->getStandardDateIntervalTakenCount($begin, $end, StatRepository::SUPPORT_INCLUDE);
            $data['stats']['taken_day_follow_specific'] = isset($result_spe_follow[StatisticColumnType::CODE_RDV_TREATED]) ? $result_spe_follow[StatisticColumnType::CODE_RDV_TREATED]['value'] : $data['stats']['taken_day_follow'];
            /* ## END Total traité ## */

            /* ## START % 10 Minutes ## */
            $tenDay = $repo->getStandardDateInterval10MIN($begin, $end, StatRepository::SUPPORT_EXCLUDE);
            $countDay = $repo->getStandardDateIntervalDone($begin, $end, StatRepository::SUPPORT_EXCLUDE);
            $data['stats']['ten_day'] = $countDay ? ($tenDay * 100 / $countDay) : 0;
            $data['stats']['ten_day_specific'] = isset($result_spe[StatisticColumnType::CODE_RDV_TEN_MINUTES]) ? $result_spe[StatisticColumnType::CODE_RDV_TEN_MINUTES]['ratio'] : $data['stats']['ten_day'];

            $tenDay = $repo->getStandardDateInterval10MIN($begin, $end, StatRepository::SUPPORT_INCLUDE);
            $countDay = $repo->getStandardDateIntervalDone($begin, $end, StatRepository::SUPPORT_INCLUDE);
            $data['stats']['ten_day_follow'] = $countDay ? ($tenDay * 100 / $countDay) : 0;
            $data['stats']['ten_day_follow_specific'] = isset($result_spe_follow[StatisticColumnType::CODE_RDV_TEN_MINUTES]) ? $result_spe_follow[StatisticColumnType::CODE_RDV_TEN_MINUTES]['ratio'] : $data['stats']['ten_day_follow'];
            /* ## END % 10 Minutes ## */

            /* ## START En cours && En attente ## */
            list($begin, $end) = $this->getTodayDateInterval();

            $data['stats']['count_processing'] = $repo->getStandardDateIntervalProcessing($begin, $end, StatRepository::SUPPORT_EXCLUDE);
            $data['stats']['count_processing_follow'] = $repo->getStandardDateIntervalProcessing($begin, $end, StatRepository::SUPPORT_INCLUDE);

            $data['stats']['count_pending'] = $repo->getStandardDateIntervalPending($begin, $end, StatRepository::SUPPORT_EXCLUDE);
            $data['stats']['count_pending_follow'] = $repo->getStandardDateIntervalPending($begin, $end, StatRepository::SUPPORT_INCLUDE);
            /* ## END En cours && En attente ## */

            /* ### MONTH STATS ### */
            list($begin, $end) = $this->getMonthIntervalFromDate($date);

            //opposition for the current month
            $data['stats']['oppo_month'] = $repo->getStandardDateIntervalOppo($begin, $end);

            //refund for the current month
            $data['stats']['refund_month'] = $repo->getStandardDateIntervalRefund($begin, $end);

            //securisation for the current month
            $data['stats']['turnover_month_secure'] = $repo->getStandardDateIntervalTurnoverSecure($begin, $end);

            //turnover for the month (turnover + secure - opposition)
            $data['stats']['turnover_month'] =
                $repo->getStandardDateIntervalTurnover($begin, $end) +
                $data['stats']['turnover_month_secure'] -
                $data['stats']['oppo_month'] -
                $data['stats']['refund_month']
            ;

            $data['stats']['turnover_month_recup'] = $repo->getStandardDateIntervalTurnover($begin, $end, true);

            // Somme des tarifications des consultations de l'intervalle donné
            $sumBillingMonth = $repo->getStandardDateIntervalBilling($begin, $end);
            //la somme de ce qui a déjà été encaissé dans l'intervalle donné
            $turnoverDoneMonth = $repo->getStandardDateIntervalTurnover($begin, $end, false);
            // valeur des Recup du Mois même
            $recupMMDay = $repo->getStandardDateIntervalTurnover($begin, $end, null, false, true);
            $data['stats']['turnover_month_fna'] = max($sumBillingMonth - $turnoverDoneMonth - $recupMMDay, 0);
        }

        return $data;
    }

    /**
     * @param $getSupport
     * @param array     $data
     * @param \Datetime $date
     *
     * @return array
     */
    protected function getSupportIfNeeded($getSupport, array $data, \Datetime $date)
    {
        if ($getSupport) {
            list($begin, $end) = $this->getDayIntervalFromDate($date);

            $countDay = $this
                ->getStatRepository()
                ->getStandardDateIntervalSupport($begin, $end);

            list($begin, $end) = $this->getMonthIntervalFromDate($date);

            $countMonth = $this
                ->getStatRepository()
                ->getStandardDateIntervalSupport($begin, $end);

            $formatted = [];
            foreach ($countDay as $d) {
                if (!array_key_exists($d['name'], $formatted)) {
                    $formatted[$d['name']] = [];
                    $formatted[$d['name']]['support_id'] = $d['support_id'];
                }
                $formatted[$d['name']]['count_day'] = $d['nb'];
            }

            foreach ($countMonth as $m) {
                if (!array_key_exists($m['name'], $formatted)) {
                    $formatted[$m['name']] = [];
                    $formatted[$m['name']]['support_id'] = $m['support_id'];
                }
                $formatted[$m['name']]['count_month'] = $m['nb'];
            }

            $data['support'] = $formatted;
        }


        return $data;
    }

    /**
     * @param $getPhoning
     * @param array     $data
     * @param \Datetime $date
     *
     * @return array
     */
    protected function getPhoningIfNeeded($getPhoning, array $data, \Datetime $date)
    {
        if ($getPhoning) {
            list($begin, $end) = $this->getDayIntervalFromDate($date);
            $countDay = $this->getStatRepository()->getStandardDateIntervalPhoning($begin, $end);

            list($begin, $end) = $this->getMonthIntervalFromDate($date);
            $countMonth = $this->getStatRepository()->getStandardDateIntervalPhoning($begin, $end);

            $formatted = [];
            foreach ($countDay as $d) {
                if (!array_key_exists($d['name'], $formatted)) {
                    $formatted[$d['name']] = [];
                    $formatted[$d['name']]['proprio_id'] = $d['proprio_id'];
                }
                $formatted[$d['name']]['count_day'] = $d['nb'];
            }

            foreach ($countMonth as $m) {
                if (!array_key_exists($m['name'], $formatted)) {
                    $formatted[$m['name']] = [];
                    $formatted[$m['name']]['proprio_id'] = $m['proprio_id'];
                }
                $formatted[$m['name']]['count_month'] = $m['nb'];
            }

            $data['phoning'] = $formatted;
        }

        return $data;
    }

    /**
     * @param $getUsers
     * @param array $data
     *
     * @return array
     */
    protected function getUsersIfNeeded($getUsers, array $data)
    {
        if ($getUsers) {
            $usersStatus = $this->getStatRepository()->getStandardUsersStatus();

            $data['users'] = ['connected' => [], 'disconnected' => []];
            foreach ($usersStatus as $info) {
                $user = $info[0];
                $processingCount = $info['processing_count'];
                $now = new \Datetime();
                $lastActive = $user->getLastActiveTime();
                $lastActive = $lastActive ?: new \Datetime('yesterday');

                $lastActive->add(new \DateInterval('P0DT0H2M10S'));
                $available = 0 == $processingCount;
                $config_spe = [
                    'begin' =>  new \DateTime(),
                    'end' => new \DateTime(),
                    'websites' => [],
                    'sources' => [],
                    'urls' => [],
                    'codesPromo' => [],
                    'supports' => [],
                    'phonists' => [],
                    'consultants' => [$user->getId()],
                    'ca' => StatisticColumnType::$CA_CHOICES,
                    'rdv' => StatisticColumnType::$RDV_LIGHTED_CHOICES,
                    'statScope' => StatScopeType::KEY_CONSULTATION,
                    'dateType' => StatisticColumnType::DATE_TYPE_CONSULTATION,
                    'sorting_column' => StatisticColumnType::CODE_RDV_TOTAL,
                    'sorting_dir' => SortingColumnType::KEY_DESC,
                ];
                if ($lastActive > $now) {
                    $result_spe = $this->calculateSpecific($config_spe + ['specific_full' => 1]);
                    $data['users']['connected'][] = [
                        'username' => $user->getUsername(),
                        'tmc' => $this->getUtilisateurRepository()->getTMC($user),
                        'moyenne' => isset($result_spe['ca_average_realised']) ? round($result_spe['ca_average_realised']['value'], 2) : 0,
                        'available' => $available,
                    ];
                } else {
                    $result_spe = $this->calculateSpecific($config_spe + ['specific_full' => 1]);
                    $data['users']['disconnected'][] = [
                        'username' => $user->getUsername(),
                        'tmc' => $this->getUtilisateurRepository()->getTMC($user),
                        'moyenne' => isset($result_spe['ca_average_realised']) ? round($result_spe['ca_average_realised']['value'], 2) : 0,
                        'available' => $available,
                    ];
                }
            }
        }

        return $data;
    }

    public function calculateSpecific(array $config = []){
        $result = parent::calculate($config);
        $return = [];
        if(isset($result['lines']) && isset($result['lines'][0]) && isset($result['lines'][0]['values'])){
            foreach ($result['lines'][0]['values'] as $value){
                $return[$value['colCode']] = $value;
            }
        }
        return $return;
    }

    /**
     * @param array $config
     *
     * @return array
     */
    public function calculate(array $config = [])
    {
        $data = [
            'date' => new \DateTime(),
            'stats' => null,
            'support' => null,
            'phoning' => null,
            'users' => null,
        ];

        $data['date'] = isset($config['date']) ? $config['date'] : $data['date'];

        $getStats = array_key_exists('get_stats', $config) && $config['get_stats'];
        $getSupport = array_key_exists('get_support', $config) && $config['get_support'];
        $getPhoning = array_key_exists('get_phoning', $config) && $config['get_phoning'];
        $getUsers = array_key_exists('get_users', $config) && $config['get_users'];

        $data = $this->getStatsIfNeeded($getStats, $data, $data['date']);
        $data = $this->getSupportIfNeeded($getSupport, $data, $data['date']);
        $data = $this->getPhoningIfNeeded($getPhoning, $data, $data['date']);
        $data = $this->getUsersIfNeeded($getUsers, $data);

        return $data;
    }

    /**
     * @param array $config
     *
     * @return array
     */
    public function details(array $params = [])
    {
        $repo = $this->getStatRepository();

        $data = [
            'date' => new \DateTime(),
            'stats' => null,
        ];

        $date = isset($params['date']) ? $params['date'] : new \DateTime();
        // $date = new \Datetime('2016-04-04');
        $data['date'] = $date;

        switch ($params['periode']) {
            case 'today':
                list($begin, $end) = $this->getDayIntervalFromDate(new \DateTime());
                break;

            case 'day':
                list($begin, $end) = $this->getDayIntervalFromDate($date);
                break;

            case 'month':
                list($begin, $end) = $this->getMonthIntervalFromDate($date);
                break;

            default:
                break;
        }


        switch ($params['type']) {
            case 'turnover':
                $data['list'] = $repo->getStandardDateIntervalTurnoverList($begin, $end);
                break;

            case 'turnover_recup':
                $data['list'] = $repo->getStandardDateIntervalTurnoverList($begin, $end, true);
                break;

            case 'turnover_fna':
                $data['list'] = $repo->getStandardDateIntervalTurnoverFnaList($begin, $end, true);
                break;

            case 'oppo':
                $data['list'] = $repo->getStandardDateIntervalOppoList($begin, $end);
                break;

            case 'refund':
                $data['list'] = $repo->getStandardDateIntervalRefundList($begin, $end);
                break;

            case 'taken':
                $data['list'] = $repo->getStandardDateIntervalTakenList($begin, $end, StatRepository::SUPPORT_EXCLUDE);
                break;

            case 'taken_specific':
                $data['list'] = $repo->getStandardDateIntervalTakenSpecificList($begin, $end, StatRepository::SUPPORT_EXCLUDE);
                break;

            case 'taken_follow':
                $data['list'] = $repo->getStandardDateIntervalTakenList($begin, $end, StatRepository::SUPPORT_INCLUDE);
                break;

            case 'taken_follow_specific':
                $data['list'] = $repo->getStandardDateIntervalTakenSpecificList($begin, $end, StatRepository::SUPPORT_INCLUDE);
                break;

            case 'processing':
                $data['list'] = $repo->getStandardDateIntervalProcessingList($begin, $end, StatRepository::SUPPORT_EXCLUDE);
                break;

            case 'processing_follow':
                $data['list'] = $repo->getStandardDateIntervalProcessingList($begin, $end, StatRepository::SUPPORT_INCLUDE);
                break;

            case 'pending':
                $data['list'] = $repo->getStandardDateIntervalPendingList($begin, $end, StatRepository::SUPPORT_EXCLUDE);
                break;

            case 'pending_follow':
                $data['list'] = $repo->getStandardDateIntervalPendingList($begin, $end, StatRepository::SUPPORT_INCLUDE);
                break;

            case 'support_list':
                $data['list'] = $repo->getStandardDateIntervalSupportList($begin, $end, $params['support']);
                break;

            case 'phoning_list':
                $data['list'] = $repo->getStandardDateIntervalPhoningList($begin, $end, $params['proprio']);
                break;

            default:
                break;
        }

        if(isset($params['export']) && $params['export']) {
            $data['csv'] = $this->csvDecorator->decorate($data, ['standard_details' => 1]);
        }

        return $data;
    }
}
