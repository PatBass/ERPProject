<?php

namespace KGC\StatBundle\Calculator;

use JMS\DiExtraBundle\Annotation as DI;
use KGC\ChatBundle\Entity\ChatPayment;
use KGC\StatBundle\Decorator\DecoratorInterface;
use KGC\StatBundle\Form\AboColumnType;
use KGC\StatBundle\Repository\StatRepository;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ChatCalculator.
 *
 * @DI\Service("kgc.stat.calculator.chat", parent="kgc.stat.calculator")
 */
class ChatCalculator extends Calculator
{

    /**
     * @var DecoratorInterface
     */
    private $csvDecorator;

    const ABO_SOUSCRIT = 0;
    const ABO_SOUSCRIT_MP = 6;
    const DESABO = 1;
    const DESACTIVATION = 2;
    const ABO_ACTIF = 3;
    const OPPOSITION = 4;
    const REMBOURSEMENT = 5;

    const LABELS = array(
        self::ABO_SOUSCRIT => self::LABEL_ABO_SOUSCRIT,
        self::ABO_SOUSCRIT_MP => self::LABEL_ABO_SOUSCRIT_MP,
        self::DESABO => self::LABEL_DESABO,
        self::DESACTIVATION => self::LABEL_DESACTIVATION,
        self::ABO_ACTIF => self::LABEL_ABO_ACTIF,
//        self::OPPOSITION => self::LABEL_OPPOSITION,
//        self::REMBOURSEMENT => self::LABEL_REMBOURSEMENT,
    );

    const LABEL_ABO_SOUSCRIT = 'Abonnement souscrit';
    const LABEL_ABO_SOUSCRIT_MP = 'Abonnement actif restant';
    const LABEL_DESABO = 'Désabonnement';
    const LABEL_DESACTIVATION = 'Désactivation auto';
    const LABEL_ABO_ACTIF = 'Abonnement actif';
    const LABEL_OPPOSITION = 'Opposition';
    const LABEL_REMBOURSEMENT = 'Remboursement';


    /**
     * @param DecoratorInterface $csvDecorator
     *
     * @DI\InjectParams({
     *      "csvDecorator" = @DI\Inject("kgc.stat.chat.decorator.csv"),
     * })
     */
    public function setCsvcDecorator(DecoratorInterface $csvDecorator)
    {
        $this->csvDecorator = $csvDecorator;
    }

    /**
     * @param array $config
     *
     * @return array
     */
    public function calculate(array $config = [])
    {
        $repo = $this->getStatRepository();
        $date = isset($config['date']) ? $config['date'] : new \DateTime();
        if(!empty($config['stat_abo'])) {
            $repo = $this->getEntityManager()->getRepository('KGCChatBundle:ChatPayment');
            $end = new \Datetime($date->format('Y-m-d '.self::DAY_TIME_REFERENCE));
            $end->modify('first day of this month')->add(new \DateInterval('P01M'));
            $begin = clone $end;
            $begin = $begin->sub(new \DateInterval('P12M'));
            $dateTime = clone $begin;
            $data = array();
            $row = array();
            $aRows = self::LABELS;
            $subColumn = array_merge(array('NB'), $config['columns']);
            while($dateTime != $end) {
                $cloneDateTime = clone $dateTime;
                $row[] = array('date' => $cloneDateTime, 'colspan' => count($subColumn), 'sub_columns' => $subColumn);
                foreach ($aRows as $idx => $aRow) {
                    if(!array_key_exists($aRow, $data)) {
                        $data[$aRow] = array();
                    }
                    if(!array_key_exists($dateTime->format('Y-m-d'), $data[$aRow])) {
                        $cloneDateTimeEnd = clone $cloneDateTime;
                        $cloneDateTimeEnd->add(new \DateInterval('P1M'));
                        $arraySend = array(
                            'NB' => $repo->getChatStatAbo($idx, $cloneDateTime, $cloneDateTimeEnd, $date, 'NB'),
                            'PARAMS' => array(
                                'type' => $idx,
                                'column' => 'NB',
                                'date' => $dateTime->format('Y-m-d'),
                            ),
                        );
                        foreach ($config['columns'] as $column) {
                            $arraySend[$column] = $repo->getChatStatAbo($idx, $cloneDateTime, $cloneDateTimeEnd, $date, $column);
                            if(in_array($column, [AboColumnType::CODE_OPPO, AboColumnType::CODE_REFUND])) {
                                $arraySend['PARAMS_'.$column] = array(
                                    'type' => $idx,
                                    'column' => $column,
                                    'date' => $dateTime->format('Y-m-d'),
                                );
                            }
                        }
                        $data[$aRow][$dateTime->format('Y-m-d')] = $arraySend;
                    }
                }
                $dateTime->add(new \DateInterval('P1M'));
            }
            $row[] = array('date' => 'TOTAL', 'colspan' => count($subColumn), 'sub_columns' => $subColumn);
            foreach ($aRows as $idx => $aRow) {
                if(!array_key_exists('TOTAL', $data[$aRow])) {
                    $cloneDateTime = new \DateTime('2012-01-01');
                    $arraySend = array(
                        'NB' => $repo->getChatStatAbo($idx, $cloneDateTime, $end, $date, 'NB'),
                        'PARAMS' => array(
                            'type' => $idx,
                            'column' => 'NB',
                            'date' => 'TOTAL',
                        ),
                    );
                    foreach ($config['columns'] as $column) {
                        $arraySend[$column] = $repo->getChatStatAbo($idx, $cloneDateTime, $end, $date, $column);
                    }
                    $data[$aRow]['TOTAL'] = $arraySend;
                }
            }
            $data['HEADER'] = $row;
        } else {
            // $date = new \Datetime('2016-04-04');

            /* ### DAY STATS ### */
            list($begin, $end) = $this->getDayIntervalFromDate($date);

            $data['stats']['turnover_day'] = $repo->getChatDateIntervalTotalTurnover($begin, $end);
            $data['stats']['turnover_day_formula_discover'] = $repo->getChatDateIntervalTotalTurnoverFormulaDiscover($begin, $end);
            $data['stats']['turnover_day_formula_standard'] = $repo->getChatDateIntervalTotalTurnoverFormulaStandard($begin, $end);
            $data['stats']['turnover_day_formula_planned'] = $repo->getChatDateIntervalTotalTurnoverSubscriptionPlanned($begin, $end);
            $data['stats']['turnover_day_recup'] = $repo->getChatDateIntervalTotalTurnoverRecup($begin, $end);
            $data['stats']['turnover_day_fna'] =$repo->getChatDateIntervalTotalTurnoverFna($begin, $end);
            $data['stats']['count_day_chat_room'] = $repo->getChatDateIntervalCountRoom($begin, $end);
            $data['stats']['count_day_formula_standard'] = $repo->getChatDateIntervalCountFormulaStandard($begin, $end);
            $data['stats']['count_day_subscription'] = $repo->getChatDateIntervalCountSubscription($begin, $end);
            $data['stats']['count_day_subscription_tchat'] = $repo->getChatDateIntervalCountSubscription($begin, $end, 'tchat');
            $data['stats']['count_day_subscription_love'] = $repo->getChatDateIntervalCountSubscription($begin, $end, 'love');
            $data['stats']['count_day_unsubscription'] = $repo->getChatDateIntervalCountUnsubscription($begin, $end);

            /* ### MONTH STATS ### */
            list($begin, $end) = $this->getMonthIntervalFromDate($date);

            $data['stats']['turnover_month'] = $repo->getChatDateIntervalTotalTurnover($begin, $end);
            $data['stats']['turnover_month_formula_discover'] = $repo->getChatDateIntervalTotalTurnoverFormulaDiscover($begin, $end);
            $data['stats']['turnover_month_formula_standard'] = $repo->getChatDateIntervalTotalTurnoverFormulaStandard($begin, $end);
            $data['stats']['turnover_month_formula_planned'] = $repo->getChatDateIntervalTotalTurnoverSubscriptionPlanned($begin, $end);
            $data['stats']['turnover_month_recup'] = $repo->getChatDateIntervalTotalTurnoverRecup($begin, $end);
            $data['stats']['turnover_month_fna'] =$repo->getChatDateIntervalTotalTurnoverFna($begin, $end);
            $data['stats']['count_month_chat_room'] = $repo->getChatDateIntervalCountRoom($begin, $end);
            $data['stats']['count_month_formula_standard'] = $repo->getChatDateIntervalCountFormulaStandard($begin, $end);
            $data['stats']['count_month_subscription'] = $repo->getChatDateIntervalCountSubscription($begin, $end);
            $data['stats']['count_month_subscription_tchat'] = $repo->getChatDateIntervalCountSubscription($begin, $end, 'tchat');
            $data['stats']['count_month_subscription_love'] = $repo->getChatDateIntervalCountSubscription($begin, $end, 'love');
            $data['stats']['count_month_unsubscription'] = $repo->getChatDateIntervalCountUnsubscription($begin, $end);
        }
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
        if(!empty($params['stat_abo'])) {
            $date = $params['date'];
            if($date != 'TOTAL') {
                $begin = new \DateTime($date);
                $begin->modify('first day of this month');
                $end = clone $begin;
                $end->add(new \DateInterval('P1M'));
            } else {
                $date = $params['request']->getSession()->has('admin_abo_tchat') ? $params['request']->getSession()->get('admin_abo_tchat'): new \DateTime();
                $end = clone $date;
                $end->modify('first day of this month')->add(new \DateInterval('P01M'));
                $begin = clone $end;
                $begin = $begin->sub(new \DateInterval('P12M'));
            }
            $type = $params['type'];
            $column = $params['column'];
            $relatif = $params['request']->getSession()->get('admin_abo_tchat');
            switch($column) {
                case 'NB':
                    switch($type) {
                        case self::ABO_SOUSCRIT:
                            $repo = $this->getEntityManager()->getRepository('KGCChatBundle:ChatSubscription');
                            $data['list'] = $repo->findSubscriptionsForInterval($begin, $end);
                            break;
                        case self::DESABO:
                            $repo = $this->getEntityManager()->getRepository('KGCChatBundle:ChatSubscription');
                            $data['list'] = $repo->findUnsubscriptionsForInterval($begin, $end, $relatif);
                            break;
                        case self::DESACTIVATION:
                            $repo = $this->getEntityManager()->getRepository('KGCChatBundle:ChatSubscription');
                            $data['list'] = $repo->findDesactivationsForInterval($begin, $end, $relatif);
                            break;
                        case self::ABO_ACTIF:
                            $repo = $this->getEntityManager()->getRepository('KGCChatBundle:ChatSubscription');
                            $data['list'] = $repo->findActifForInterval($begin, $end, $relatif);
                            break;
                        case self::ABO_SOUSCRIT_MP:
                            $relatifMP = clone $relatif;
                            $relatifMP->sub(new \DateInterval('P1M'));
                            $repo = $this->getEntityManager()->getRepository('KGCChatBundle:ChatSubscription');
                            $data['list'] = $repo->findActifForInterval($begin, $end, $relatifMP);
                            break;
                        default:
                            $data['list'] = null;
                            break;
                    }
                    break;
                case AboColumnType::CODE_OPPO:
                case AboColumnType::CODE_REFUND:
                    switch($type) {
                        case self::ABO_SOUSCRIT:
                            $repo = $this->getEntityManager()->getRepository('KGCChatBundle:ChatSubscription');
                            $data['list'] = $repo->findSubscriptionsForInterval($begin, $end, null, $column);
                            break;
                        case self::DESABO:
                            $repo = $this->getEntityManager()->getRepository('KGCChatBundle:ChatSubscription');
                            $data['list'] = $repo->findUnsubscriptionsForInterval($begin, $end, $relatif, $column);
                            break;
                        case self::DESACTIVATION:
                            $repo = $this->getEntityManager()->getRepository('KGCChatBundle:ChatSubscription');
                            $data['list'] = $repo->findDesactivationsForInterval($begin, $end, $relatif, $column);
                            break;
                        case self::ABO_ACTIF:
                            $repo = $this->getEntityManager()->getRepository('KGCChatBundle:ChatSubscription');
                            $data['list'] = $repo->findActifForInterval($begin, $end, $relatif, $column);
                            break;
                        case self::ABO_SOUSCRIT_MP:
                            $relatifMP = clone $relatif;
                            $relatifMP->sub(new \DateInterval('P1M'));
                            $repo = $this->getEntityManager()->getRepository('KGCChatBundle:ChatSubscription');
                            $data['list'] = $repo->findActifForInterval($begin, $end, $relatifMP, $column);
                            break;
                        default:
                            $data['list'] = null;
                            break;
                    }
                    break;
            }

        } else {
            $data = [
                'date' => new \DateTime(),
                'stats' => null,
            ];

            $date = isset($params['date']) ? $params['date'] : new \DateTime();
            // $date = new \Datetime('2016-04-04');
            $data['date'] = $date;

            switch ($params['periode']) {
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
                    $data['list'] = $repo->getChatDateIntervalTurnover($begin, $end);
                    break;

                case 'turnover_formula_discover':
                    $data['list'] = $repo->getChatDateIntervalTurnoverFormulaDiscover($begin, $end);
                    break;

                case 'turnover_formula_standard':
                    $data['list'] = $repo->getChatDateIntervalTurnoverFormulaStandard($begin, $end);
                    break;

                case 'turnover_formula_planned':
                    $data['list'] = $repo->getChatDateIntervalTurnoverSubscriptionPlanned($begin, $end);
                    break;

                case 'turnover_recup':
                    $data['list'] = $repo->getChatDateIntervalTurnoverRecup($begin, $end);
                    break;

                case 'turnover_fna':
                    $data['list'] = $repo->getChatDateIntervalTurnoverFna($begin, $end);
                    break;

                case 'formula_standard':
                    $data['list'] = $repo->getChatDateIntervalFormulasStandard($begin, $end);
                    break;

                case 'subscription':
                    $data['list'] = $repo->getChatDateIntervalSubscriptions($begin, $end);
                    break;

                case 'subscription_love':
                    $data['list'] = $repo->getChatDateIntervalSubscriptions($begin, $end, 'love');
                    break;

                case 'subscription_tchat':
                    $data['list'] = $repo->getChatDateIntervalSubscriptions($begin, $end, 'tchat');
                    break;

                case 'unsubscription':
                    $data['list'] = $repo->getChatDateIntervalUnsubscriptions($begin, $end, 'tchat');
                    break;

                default:
                    break;
            }
        }

        if(isset($params['export']) && $params['export']) {
            $data['csv'] = $this->csvDecorator->decorate($data, ['abo_details' => 1]);
        }

        return $data;
    }
}
