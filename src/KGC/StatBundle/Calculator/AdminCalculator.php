<?php

namespace KGC\StatBundle\Calculator;

use JMS\DiExtraBundle\Annotation as DI;
use KGC\StatBundle\Decorator\DecoratorInterface;
use KGC\StatBundle\Decorator\RoiDecorator;
use KGC\StatBundle\Repository\StatRepository;

/**
 * Class AdminCalculator.
 *
 * @DI\Service("kgc.stat.calculator.admin", parent="kgc.stat.calculator")
 */
class AdminCalculator extends Calculator
{
    const UNPAID_MONTH_SLIDE_OFFSET = 12;

    /**
     * @var DecoratorInterface
     */
    private $roiDecorator;

    /**
     * @var DecoratorInterface
     */
    private $unpaidDecorator;

    /**
     * @var DecoratorInterface
     */
    private $phoningDecorator;

    /**
     * @var DecoratorInterface
     */
    private $csvDecorator;

    /**
     * @param DecoratorInterface $roiDecorator
     *
     * @DI\InjectParams({
     *      "roiDecorator" = @DI\Inject("kgc.stat.decorator.roi"),
     * })
     */
    public function setRoiDecorator(DecoratorInterface $roiDecorator)
    {
        $this->roiDecorator = $roiDecorator;
    }

    /**
     * @param DecoratorInterface $unpaidDecorator
     *
     * @DI\InjectParams({
     *      "unpaidDecorator" = @DI\Inject("kgc.stat.decorator.unpaid"),
     * })
     */
    public function setUnpaidDecorator(DecoratorInterface $unpaidDecorator)
    {
        $this->unpaidDecorator = $unpaidDecorator;
    }

    /**
     * @param DecoratorInterface $phoningDecorator
     *
     * @DI\InjectParams({
     *      "phoningDecorator" = @DI\Inject("kgc.stat.decorator.phoning"),
     * })
     */
    public function setPhoningDecorator(DecoratorInterface $phoningDecorator)
    {
        $this->phoningDecorator = $phoningDecorator;
    }

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
     * @param $getCA
     * @param array $data
     * @param array $config
     *
     * @return array
     */
    protected function getPointageCaIfNeeded($getCA, array $data, array $config)
    {
        if($getCA){
            list($begin, $end) = [$config['date_begin'], $config['date_end']];

            $caTPE = $this->getStatRepository()->getAdminCaTpe($begin, $end);
            $caPayment = $this->getStatRepository()->getAdminCaPayment($begin, $end);
            $caSecu = $this->getStatRepository()->getAdminCaTpeSecu($begin, $end);
            $caOppo = $this->getStatRepository()->getAdminCaOppo($begin, $end);
            $caRefund = $this->getStatRepository()->getAdminCaRefund($begin, $end);
            $caTelecollecte = $this->getStatRepository()->getAdminCaTelecollecte($begin, $end);

            $data['ca'] = [
                'tpe' => $caTPE,
                'payment' => $caPayment,
                'secu' => $caSecu,
                'oppo' => $caOppo,
                'refund' => $caRefund,
                'tel' => $caTelecollecte
            ];

            list($data['ca'], $data['ca_secu'], $data['ca_total'], $data['details']) = $this->roiDecorator->decorate($data, $config);

            if(isset($config['export']) && $config['export']) {
                $data['csv'] = $this->csvDecorator->decorate($data, $config + ['roi_details' => 1]);
            }
        }

        return $data;
    }

    /**
     * @param $getImpaye
     * @param array $data
     * @param array $config
     *
     * @return array
     */
    protected function getImpayeIfNeeded($getImpaye, array $data, array $config)
    {
        if ($getImpaye) {
            list($begin, $end) = [$config['date_begin'], $config['date_end']];
            list($beginRdv, $endRdv) = $this->getSlidingDateByOffset($config['date'], self::UNPAID_MONTH_SLIDE_OFFSET);

            $recupUnpaid = $this->getStatRepository()->getAdminRecupUnpaid($begin,  $end, $beginRdv, $endRdv);
            $billingUnpaid = $this->getStatRepository()->getAdminBillingUnpaid($beginRdv, $endRdv);
            $paidUnpaid = $this->getStatRepository()->getAdminPaidUnpaid($beginRdv, $endRdv);
            $oppo = $this->getStatRepository()->getAdminOppoUnpaid($begin,  $end, $beginRdv, $endRdv);
            $refund = $this->getStatRepository()->getAdminRefundUnpaid($begin,  $end, $beginRdv, $endRdv);

            $paidUnpaidCurrent = $this->getStatRepository()->getAdminPaidUnpaid($begin, $end, true);
            $billingUnpaidCurrent = $this->getStatRepository()->getAdminBillingUnpaid($begin, $end);
            $recupUnpaidCurrent = $this->getStatRepository()->getAdminRecupUnpaid($begin,  $end, $begin,  $end);
            $oppoCurrent = $this->getStatRepository()->getAdminOppoUnpaid($begin,  $end, $begin, $end);
            $refundCurrent = $this->getStatRepository()->getAdminRefundUnpaid($begin,  $end, $begin, $end);

            $data['unpaid'] = [
                'oppo' => $oppo,
                'refund' => $refund,
                'recup' => $recupUnpaid,
                'billing' => $billingUnpaid,
                'paid' => $paidUnpaid,
                'oppo_current' => $oppoCurrent,
                'refund_current' => $refundCurrent,
                'recup_current' => $recupUnpaidCurrent,
                'billing_current' => $billingUnpaidCurrent,
                'paid_current' => $paidUnpaidCurrent,
            ];

            $data['unpaid'] = $this->unpaidDecorator->decorate($data, $config);
        }

        return $data;
    }

    /**
     * @param $getPhoning
     * @param array $data
     * @param array $config
     *
     * @return array
     */
    protected function getPhoningIfNeeded($getPhoning, array $data, array $config)
    {
        if ($getPhoning) {
            list($begin, $end) = $this->getFullMonthIntervalFromDates($config['date_begin'], $config['date_end']);
            $rdvTotal = $this->getStatRepository()->getAdminTotalPhoning($begin, $end);
            $tenTotal = $this->getStatRepository()->getAdmin10MinPhoning($begin, $end);
            $caTotal = $this->getStatRepository()->getAdminCAPhoning($begin, $end);
            $caSecu = $this->getStatRepository()->getAdminCASecuPhoning($begin, $end);
            $caOppo = $this->getStatRepository()->getAdminCAOppoPhoning($begin, $end);
            $bonus = $this->getStatRepository()->getAdminBonusPhoning($begin, $end);
            $lateness = $this->getStatRepository()->getAdminJournalPhoning($begin, $end);

            $data['phoning'] = [
                'total' => $rdvTotal,
                '10min' => $tenTotal,
                'ca_total' => $caTotal,
                'ca_secu' => $caSecu,
                'ca_oppo' => $caOppo,
                'bonus' => $bonus,
                'lateness' => $lateness,
            ];

            $data['phoning'] = $this->phoningDecorator->decorate($data, $config);
        }

        return $data;
    }

    protected function getGeneralFullIfNeeded($getGeneralFull, array $data, array $config)
    {
        if ($getGeneralFull) {
            list($begin, $end) = [$config['date_begin'], $config['date_end']];
            $billing = $this->getStatRepository()->getAdminGeneralBilling($begin, $end);

            $paid = $this->getStatRepository()->getAdminGeneralPaid($begin, $end);
            $recup = $this->getStatRepository()->getAdminGeneralRecup($begin, $end);
            $secure = $this->getStatRepository()->getAdminGeneralSecure($begin, $end);

            $data['general_full'] = [
                'billing' => $billing,
                'paid' => $paid,
                'recup' => $recup,
                'secu' => $secure,
                'total' => $paid + $secure,
            ];
        }

        return $data;
    }

    protected function getGeneralMonthIfNeeded($getGeneralMonth, array $data, array $config)
    {
        if ($getGeneralMonth) {
            list($begin, $end) = [$config['date_begin'], $config['date_end']];
            $billing = $this->getStatRepository()->getAdminGeneralBilling($begin, $end);
            $paid = $this->getStatRepository()->getAdminGeneralPaid($begin, $end, true);
            $recup = $this->getStatRepository()->getAdminGeneralRecup($begin, $end, true);
            $secure = $this->getStatRepository()->getAdminGeneralSecure($begin, $end, true);

            $data['general_month'] = [
                'billing' => $billing,
                'paid' => $paid,
                'recup' => $recup,
                'secu' => $secure,
                'total' => $paid + $secure,
            ];
        }

        return $data;
    }

    protected function buildByKey(array $data, $key)
    {
        $result = [];
        $total = [
            'nb' => 0,
            'billing' => 0,
            'paid' => 0,
            'recup' => 0,
        ];
        foreach ($data as $nature => $values) {
            foreach ($values as $item) {
                if (!array_key_exists($item[$key], $result)) {
                    $result[$item[$key]] = [
                        'nb' => 0,
                        'billing' => 0,
                        'percentage_billing' => 0,
                        'paid' => 0,
                        'percentage_paid' => 0,
                        'recup' => 0,
                    ];
                }
                if ($nature === 'billing') {
                    $result[$item[$key]]['nb'] = $item['nb'];
                    $total['nb'] += $item['nb'];
                }
                $result[$item[$key]][$nature] = $item['amount'];
                $total[$nature] += $item['amount'];
            }
        }

        $result = $result + ['Total' => $total];

        foreach ($result as $nature => $values) {
            $result[$nature]['percentage_billing'] = $total['billing'] ? $result[$nature]['billing'] * 100 / $total['billing'] : 0;
            $result[$nature]['percentage_paid'] = $total['billing'] ? $result[$nature]['paid'] * 100 / $total['paid'] : 0;
        }

        return $result;
    }

    protected function getGeneralWebsiteConsultIfNeeded($get, array $data, array $config)
    {
        if ($get) {
            list($begin, $end) = [$config['date_begin'], $config['date_end']];
            $billing = $this->getStatRepository()->getAdminGeneralBillingFilter($begin, $end, StatRepository::SUPPORT_EXCLUDE, 'website');
            $paid = $this->getStatRepository()->getAdminGeneralPaidFilter($begin, $end, false, StatRepository::SUPPORT_EXCLUDE, 'website');
            $recup = $this->getStatRepository()->getAdminGeneralRecupFilter($begin, $end, false, StatRepository::SUPPORT_EXCLUDE, 'website');

            $data['general_website_support'] = [
                'billing' => $billing,
                'paid' => $paid,
                'recup' => $recup,
            ];

            $data['general_website_support'] = $this->buildByKey($data['general_website_support'], 'website_label');
        }

        return $data;
    }

    protected function getGeneralWebsiteFollowIfNeeded($get, array $data, array $config)
    {
        if ($get) {
            list($begin, $end) = [$config['date_begin'], $config['date_end']];
            $billing = $this->getStatRepository()->getAdminGeneralBillingFilter($begin, $end, StatRepository::SUPPORT_INCLUDE, 'website');
            $paid = $this->getStatRepository()->getAdminGeneralPaidFilter($begin, $end, false, StatRepository::SUPPORT_INCLUDE, 'website');
            $recup = $this->getStatRepository()->getAdminGeneralRecupFilter($begin, $end, false, StatRepository::SUPPORT_INCLUDE, 'website');

            $data['general_website_support'] = [
                'billing' => $billing,
                'paid' => $paid,
                'recup' => $recup,
            ];

            $data['general_website_support'] = $this->buildByKey($data['general_website_support'], 'website_label');
        }

        return $data;
    }

    protected function getGeneralWebsiteIfNeeded($get, array $data, array $config)
    {
        if ($get) {
            list($begin, $end) = [$config['date_begin'], $config['date_end']];
            $billing = $this->getStatRepository()->getAdminGeneralBillingFilter($begin, $end, null, 'website');
            $paid = $this->getStatRepository()->getAdminGeneralPaidFilter($begin, $end, false, null, 'website');
            $recup = $this->getStatRepository()->getAdminGeneralRecupFilter($begin, $end, false, null, 'website');

            $data['general_website_support'] = [
                'billing' => $billing,
                'paid' => $paid,
                'recup' => $recup,
            ];

            $data['general_website_support'] = $this->buildByKey($data['general_website_support'], 'website_label');
        }

        return $data;
    }

    protected function getGeneralSupportConsultIfNeeded($get, array $data, array $config)
    {
        if ($get) {
            list($begin, $end) = [$config['date_begin'], $config['date_end']];
            $billing = $this->getStatRepository()->getAdminGeneralBillingFilter($begin, $end, StatRepository::SUPPORT_EXCLUDE, 'support');
            $paid = $this->getStatRepository()->getAdminGeneralPaidFilter($begin, $end, false, StatRepository::SUPPORT_EXCLUDE, 'support');
            $recup = $this->getStatRepository()->getAdminGeneralRecupFilter($begin, $end, false, StatRepository::SUPPORT_EXCLUDE, 'support');

            $data['general_website_support'] = [
                'billing' => $billing,
                'paid' => $paid,
                'recup' => $recup,
            ];

            $data['general_website_support'] = $this->buildByKey($data['general_website_support'], 'support_label');
        }

        return $data;
    }

    protected function getGeneralSupportFollowIfNeeded($get, array $data, array $config)
    {
        if ($get) {
            list($begin, $end) = [$config['date_begin'], $config['date_end']];
            $billing = $this->getStatRepository()->getAdminGeneralBillingFilter($begin, $end, StatRepository::SUPPORT_INCLUDE, 'support');
            $paid = $this->getStatRepository()->getAdminGeneralPaidFilter($begin, $end, false, StatRepository::SUPPORT_INCLUDE, 'support');
            $recup = $this->getStatRepository()->getAdminGeneralRecupFilter($begin, $end, false, StatRepository::SUPPORT_INCLUDE, 'support');

            $data['general_website_support'] = [
                'billing' => $billing,
                'paid' => $paid,
                'recup' => $recup,

            ];

            $data['general_website_support'] = $this->buildByKey($data['general_website_support'], 'support_label');
        }

        return $data;
    }

    protected function getGeneralSupportIfNeeded($get, array $data, array $config)
    {
        if ($get) {
            list($begin, $end) = [$config['date_begin'], $config['date_end']];
            $billing = $this->getStatRepository()->getAdminGeneralBillingFilter($begin, $end, null, 'support');
            $paid = $this->getStatRepository()->getAdminGeneralPaidFilter($begin, $end, false, null, 'support');
            $recup = $this->getStatRepository()->getAdminGeneralRecupFilter($begin, $end, false, null, 'support');

            $data['general_website_support'] = [
                'billing' => $billing,
                'paid' => $paid,
                'recup' => $recup,

            ];

            $data['general_website_support'] = $this->buildByKey($data['general_website_support'], 'support_label');
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
        $data = [
            'ca' => null,
            'ca_secu' => null,
            'ca_total' => null,
            'unpaid' => null,
            'phoning' => null,
            'general_full' => null,
            'general_month' => null,
            'general_website_support' => null,
        ];

        if (isset($config['date'])) {
            list($begin, $end) = $this->getFullMonthIntervalFromDate($config['date']);
        } else {
            $begin = new \DateTime($config['date_begin']->format('Y-m-d').' '.parent::DAY_TIME_REFERENCE);
            $end = new \DateTime($config['date_end']->format('Y-m-d').' '.parent::DAY_TIME_REFERENCE);
            $end->add(new \DateInterval('P1D'));
        }
        $config['date_begin'] = $begin;
        $config['date_end'] = $end;

        $getCA = array_key_exists('get_ca', $config) && $config['get_ca'];
        $data = $this->getPointageCaIfNeeded($getCA, $data, $config);

        $getImpaye = array_key_exists('get_unpaid', $config) && $config['get_unpaid'];
        $data = $this->getImpayeIfNeeded($getImpaye, $data, $config);

        $getPhoning = array_key_exists('get_phoning', $config) && $config['get_phoning'];
        $data = $this->getPhoningIfNeeded($getPhoning, $data, $config);

        $getGeneralFull = array_key_exists('get_general_full', $config) && $config['get_general_full'];
        $data = $this->getGeneralFullIfNeeded($getGeneralFull, $data, $config);

        $getGeneralMonth = array_key_exists('get_general_month', $config) && $config['get_general_month'];
        $data = $this->getGeneralMonthIfNeeded($getGeneralMonth, $data, $config);

        $getWebsiteConsult = array_key_exists('get_general_website_consult', $config) && $config['get_general_website_consult'];
        $data = $this->getGeneralWebsiteConsultIfNeeded($getWebsiteConsult, $data, $config);

        $getWebsiteFollow = array_key_exists('get_general_website_follow', $config) && $config['get_general_website_follow'];
        $data = $this->getGeneralWebsiteFollowIfNeeded($getWebsiteFollow, $data, $config);

        $getWebsite = array_key_exists('get_general_website', $config) && $config['get_general_website'];
        $data = $this->getGeneralWebsiteIfNeeded($getWebsite, $data, $config);

        $getSupportConsult = array_key_exists('get_general_support_consult', $config) && $config['get_general_support_consult'];
        $data = $this->getGeneralSupportConsultIfNeeded($getSupportConsult, $data, $config);

        $getSupportFollow = array_key_exists('get_general_support_follow', $config) && $config['get_general_support_follow'];
        $data = $this->getGeneralSupportFollowIfNeeded($getSupportFollow, $data, $config);

        $getSupport = array_key_exists('get_general_support', $config) && $config['get_general_support'];
        $data = $this->getGeneralSupportIfNeeded($getSupport, $data, $config);

        return $data;
    }
}
