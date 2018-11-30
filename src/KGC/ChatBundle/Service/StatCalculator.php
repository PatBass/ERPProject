<?php

namespace KGC\ChatBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use JMS\DiExtraBundle\Annotation as DI;
use KGC\ChatBundle\Entity\ChatType;

/**
 * @DI\Service("kgc.chat.calculator.stats")
 */
class StatCalculator
{
    protected $em;

    protected $chatPaymentRepository;
    protected $chatRoomRepository;
    protected $chatSubscriptionRepository;
    protected $statRepository;

    protected function getDayIntervalFromDate(\Datetime $date)
    {
        $first = new \Datetime($date->format('Y-m-d 00:00'));
        $last = clone $first;
        $last = $last->add(new \DateInterval('P01D'));

        return [$first, $last];
    }

    protected function getMonthIntervalFromDate(\Datetime $date)
    {
        $m = (int) $date->format('m');
        $y = $date->format('Y');

        $first = new \Datetime(date('Y-m-d 00:00', mktime(0, 0, 0, $m, 1, $y)));
        // $last is current day
        $last = clone $date;
        // Or $last is last day of the month
        // $last = new \Datetime(date('Y-m-d '.self::DAY_TIME_REFERENCE, mktime(0, 0, 0, $m+1, 0, $y)));
        $last = $last->add(new \DateInterval('P01D'));

        return [$first, $last];
    }

    /**
     * @param EntityManagerInterface $em
     * @param EntityRepository       $statRepo
     *
     * @DI\InjectParams({
     *     "em" = @DI\Inject("doctrine.orm.entity_manager"),
     *     "statRepo" = @DI\Inject("kgc.stat.repository"),
     * })
     */
    public function __construct(EntityManagerInterface $em, EntityRepository $statRepo)
    {
        $this->em = $em;
        $this->statRepository = $statRepo;
    }

    protected function init()
    {
        $this->chatPaymentRepository = $this->em->getRepository('KGCChatBundle:ChatPayment');
        $this->chatRoomRepository = $this->em->getRepository('KGCChatBundle:ChatRoom');
        $this->chatSubscriptionRepository = $this->em->getRepository('KGCChatBundle:ChatSubscription');
    }

    protected function initCount()
    {
        return [
            ChatType::TYPE_MINUTE => '0',
            ChatType::TYPE_QUESTION => '0',
        ];
    }

    protected function typeAsKey(array $data, $key = 'nb')
    {
        $new = [];
        foreach ($data as $item) {
            $new[$item['type']] = $item[$key];
        }

        return $new;
    }

    protected function getStats(array $data, \DateTime $date)
    {
        $date = isset($data['date']) ? $data['date'] : new \DateTime();
        // $date = new \Datetime('2016-04-04');


        /* ### DAY STATS ### */
        list($begin, $end) = $this->getDayIntervalFromDate($date);
        $data['stats'] = [
            'turnover_day_before' => $this->chatPaymentRepository->getChatDateIntervalTotalTurnover($begin, $end),
            'turnover_day_formula_discover' => $this->chatPaymentRepository->getChatDateIntervalTotalTurnoverFormulaDiscover($begin, $end),
            'turnover_day_formula_standard' => $this->chatPaymentRepository->getChatDateIntervalTotalTurnoverFormulaStandard($begin, $end),
            'turnover_day_formula_planned' => $this->chatPaymentRepository->getChatDateIntervalTotalTurnoverSubscriptionPlanned($begin, $end),
            'turnover_day_recup' => $this->chatPaymentRepository->getChatDateIntervalTotalTurnoverRecup($begin, $end),
            'turnover_day_fna' => $this->chatPaymentRepository->getChatDateIntervalTotalTurnoverFna($begin, $end),
            'turnover_day_oppo' => $this->chatPaymentRepository->getChatDateIntervalTotalTurnoverOppo($begin, $end),
            'turnover_day_refund' => $this->chatPaymentRepository->getChatDateIntervalTotalTurnoverRefund($begin, $end),
            'count_day_chat_room' => $this->chatRoomRepository->getCountForInterval($begin, $end),
            'count_day_formula_standard' => $this->chatPaymentRepository->getChatDateIntervalCountFormulaStandard($begin, $end),
            'count_day_subscription' => $this->chatSubscriptionRepository->getCountForInterval($begin, $end),
            'count_day_subscription_tchat' => $this->chatSubscriptionRepository->getCountForInterval($begin, $end, 'tchat'),
            'count_day_subscription_love' => $this->chatSubscriptionRepository->getCountForInterval($begin, $end, 'love'),
            'count_day_subscription_tarot' => $this->chatSubscriptionRepository->getCountForInterval($begin, $end, 'tarot'),
            'count_day_subscription_power' => $this->chatSubscriptionRepository->getCountForInterval($begin, $end, 'power'),
            'count_day_subscription_myastro' => $this->chatSubscriptionRepository->getCountForInterval($begin, $end, 'myastro'),
            'count_day_unsubscription' => $this->chatSubscriptionRepository->getCountUnsubscriptionsForInterval($begin, $end),
            'count_day_non_conversion' => $this->chatPaymentRepository->getCountNonConversionsForInterval($begin, $end),
        ];

        $data['stats']['turnover_day'] = $data['stats']['turnover_day_before'] - $data['stats']['turnover_day_oppo'] - $data['stats']['turnover_day_refund'];

        /* ### MONTH STATS ### */
        list($begin, $end) = $this->getMonthIntervalFromDate($date);
        $data['stats'] = array_merge($data['stats'], [
            'turnover_month_before' => $this->chatPaymentRepository->getChatDateIntervalTotalTurnover($begin, $end),
            'turnover_month_formula_discover' => $this->chatPaymentRepository->getChatDateIntervalTotalTurnoverFormulaDiscover($begin, $end),
            'turnover_month_formula_standard' => $this->chatPaymentRepository->getChatDateIntervalTotalTurnoverFormulaStandard($begin, $end),
            'turnover_month_formula_planned' => $this->chatPaymentRepository->getChatDateIntervalTotalTurnoverSubscriptionPlanned($begin, $end),
            'turnover_month_recup' => $this->chatPaymentRepository->getChatDateIntervalTotalTurnoverRecup($begin, $end),
            'turnover_month_fna' => $this->chatPaymentRepository->getChatDateIntervalTotalTurnoverFna($begin, $end),
            'turnover_month_oppo' => $this->chatPaymentRepository->getChatDateIntervalTotalTurnoverOppo($begin, $end),
            'turnover_month_refund' => $this->chatPaymentRepository->getChatDateIntervalTotalTurnoverRefund($begin, $end),
            'count_month_chat_room' => $this->chatRoomRepository->getCountForInterval($begin, $end),
            'count_month_formula_standard' => $this->chatPaymentRepository->getChatDateIntervalCountFormulaStandard($begin, $end),
            'count_month_subscription' => $this->chatSubscriptionRepository->getCountForInterval($begin, $end),
            'count_month_subscription_tchat' => $this->chatSubscriptionRepository->getCountForInterval($begin, $end, 'tchat'),
            'count_month_subscription_love' => $this->chatSubscriptionRepository->getCountForInterval($begin, $end, 'love'),
            'count_month_subscription_tarot' => $this->chatSubscriptionRepository->getCountForInterval($begin, $end, 'tarot'),
            'count_month_subscription_power' => $this->chatSubscriptionRepository->getCountForInterval($begin, $end, 'power'),
            'count_month_subscription_myastro' => $this->chatSubscriptionRepository->getCountForInterval($begin, $end, 'myastro'),
            'count_month_unsubscription' => $this->chatSubscriptionRepository->getCountUnsubscriptionsForInterval($begin, $end),
            'count_month_non_conversion' => $this->chatPaymentRepository->getCountNonConversionsForInterval($begin, $end),
        ]);

        $data['stats']['turnover_month'] = $data['stats']['turnover_month_before'] - $data['stats']['turnover_month_oppo'] - $data['stats']['turnover_month_refund'];

        return $data;
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function details(array $params = [])
    {
        $this->init();

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
                $data['list'] = $this->chatPaymentRepository->getChatDateIntervalTurnover($begin, $end);
                break;

            case 'turnover_formula_discover':
                $data['list'] = $this->chatPaymentRepository->getChatDateIntervalTurnoverFormulaDiscover($begin, $end);
                break;

            case 'turnover_formula_standard':
                $data['list'] = $this->chatPaymentRepository->getChatDateIntervalTurnoverFormulaStandard($begin, $end);
                break;

            case 'turnover_formula_planned':
                $data['list'] = $this->chatPaymentRepository->getChatDateIntervalTurnoverSubscriptionPlanned($begin, $end);
                break;

            case 'turnover_recup':
                $data['list'] = $this->chatPaymentRepository->getChatDateIntervalTurnoverRecup($begin, $end);
                break;

            case 'turnover_fna':
                $data['list'] = $this->chatPaymentRepository->getChatDateIntervalTurnoverFna($begin, $end);
                break;

            case 'turnover_oppo':
                $data['list'] = $this->chatPaymentRepository->getChatDateIntervalTurnoverOppo($begin, $end);
                break;

            case 'turnover_refund':
                $data['list'] = $this->chatPaymentRepository->getChatDateIntervalTurnoverRefund($begin, $end);
                break;

            case 'formula_standard':
                $data['list'] = $this->chatPaymentRepository->getChatDateIntervalFormulasStandard($begin, $end);
                break;

            case 'subscription':
                $data['list'] = $this->chatSubscriptionRepository->findSubscriptionsForInterval($begin, $end);
                break;

            case 'subscription_love':
                $data['list'] = $this->chatSubscriptionRepository->findSubscriptionsForInterval($begin, $end, 'love');
                break;

            case 'subscription_tchat':
                $data['list'] = $this->chatSubscriptionRepository->findSubscriptionsForInterval($begin, $end, 'tchat');
                break;

            case 'subscription_tarot':
                $data['list'] = $this->chatSubscriptionRepository->findSubscriptionsForInterval($begin, $end, 'tarot');
                break;

            case 'subscription_power':
                $data['list'] = $this->chatSubscriptionRepository->findSubscriptionsForInterval($begin, $end, 'power');
                break;

            case 'subscription_myastro':
                $data['list'] = $this->chatSubscriptionRepository->findSubscriptionsForInterval($begin, $end, 'myastro');
                break;

            case 'unsubscription':
                $data['list'] = $this->chatSubscriptionRepository->findUnsubscriptionsForInterval($begin, $end);
                break;

            case 'non_conversion':
                $data['list'] = $this->chatPaymentRepository->findNonConversionsForInterval($begin, $end);
                break;

            default:
                break;
        }

        return $data;
    }

    protected function getStatsChatter(array $data, \DateTime $date)
    {
        list($begin, $end) = $this->getDayIntervalFromDate($date);
        $countDay = $this->chatRoomRepository->getCountByPsychicForInterval($begin, $end);

        list($begin, $end) = $this->getMonthIntervalFromDate($date);
        $countMonth = $this->chatRoomRepository->getCountByPsychicForInterval($begin, $end);

        $formatted = [];
        foreach ($countDay as $d) {
            if (!array_key_exists($d['name'], $formatted)) {
                $formatted[$d['name']] = [];
            }
            $formatted[$d['name']]['count_day'] = $d['nb'];
        }

        foreach ($countMonth as $m) {
            if (!array_key_exists($m['name'], $formatted)) {
                $formatted[$m['name']] = [];
            }
            $formatted[$m['name']]['count_month'] = $m['nb'];
        }

        $data['chatter'] = $formatted;

        return $data;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function getStatsUsers(array $data)
    {
        $usersStatus = $this->statRepository->getChatterUsersStatus();

        $data['users'] = ['connected' => [], 'disconnected' => []];
        foreach ($usersStatus as $info) {
            $user = $info[0];
            $processingCount = $info['processing_count'];
            $now = new \Datetime();
            $lastActive = $user->getLastActiveTime();
            $lastActive = $lastActive ?: new \Datetime('yesterday');

            $lastActive->add(new \DateInterval('P0DT0H2M10S'));
            $available = 0 == $processingCount;

            if ($lastActive > $now) {
                $data['users']['connected'][] = [
                    'username' => $user->getUsername(),
                    'available' => $available,
                    'chat_available' => $user->getIsChatAvailable(),
                ];
            } else {
                $data['users']['disconnected'][] = [
                    'username' => $user->getUsername(),
                    'available' => $available,
                    'chat_available' => false,
                ];
            }
        }

        return $data;
    }

    /**
     * @param array $config
     *
     * @return array
     */
    public function calculate(array $config = [])
    {
        $this->init();

        $data = [
            'date' => new \DateTime(),
            'stats' => null,
            'chatter' => null,
            'users' => null,
        ];

        $getGeneral = isset($config['get_general']) && $config['get_general'];
        $getChatter = isset($config['get_chatter']) && $config['get_chatter'];
        $getUsers = isset($config['get_users']) && $config['get_users'];

        $data['date'] = isset($config['date']) ? $config['date'] : null;

        if ($getGeneral) {
            $data = $this->getStats($data, $data['date']);
        }

        if ($getChatter) {
            $data = $this->getStatsChatter($data, $data['date']);
        }

        if ($getUsers) {
            $data = $this->getStatsUsers($data);
        }

        return $data;
    }
}
