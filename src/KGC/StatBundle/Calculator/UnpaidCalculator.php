<?php

namespace KGC\StatBundle\Calculator;

use JMS\DiExtraBundle\Annotation as DI;
use KGC\RdvBundle\Entity\Tiroir;
use KGC\StatBundle\Decorator\DecoratorInterface;
use KGC\StatBundle\Decorator\RoiDecorator;
use KGC\StatBundle\Repository\StatRepository;

/**
 * Class UnpaidCalculator.
 *
 * @DI\Service("kgc.stat.calculator.unpaid", parent="kgc.stat.calculator")
 */
class UnpaidCalculator extends Calculator
{
    const UNPAID_MONTH_SLIDE_OFFSET = 12;


    /**
     * @var DecoratorInterface
     */
    private $unpaidDecorator;

    /**
     * @var DecoratorInterface
     */
    private $csvDecorator;

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
     * @param $getImpaye
     * @param array $data
     * @param array $config
     *
     * @return array
     */
    protected function getImpayeIfNeeded($getImpaye, array $data, array $config)
    {
        if ($getImpaye) {
            $data['unpaid'] = $this->unpaidDecorator->decorateStat($data, $config);
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
            'unpaid' => null,
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

        $getImpaye = array_key_exists('get_unpaid', $config) && $config['get_unpaid'];
        $data = $this->getImpayeIfNeeded($getImpaye, $data, $config);

        return $data;
    }

    public function getCsvRdvs($rdvs) {
        return $this->csvDecorator->decorate(array('details' => $rdvs), array('rdv_details' => 1));
    }
}
