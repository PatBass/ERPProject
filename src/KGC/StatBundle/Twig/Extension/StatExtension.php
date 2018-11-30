<?php

namespace KGC\StatBundle\Twig\Extension;

use JMS\DiExtraBundle\Annotation as DI;
use KGC\StatBundle\Calculator\CalculatorInterface;
use KGC\StatBundle\Entity\PhonisteParameter;

/**
 * @DI\Service("kgc.stat.twig.extension")
 *
 * @DI\Tag("twig.extension")
 */
class StatExtension extends \Twig_Extension
{
    /**
     * @var CalculatorInterface
     */
    protected $phonisteCalculator;

    /**
     * @param CalculatorInterface $phonisteCalculator
     *
     * @DI\InjectParams({
     *     "phonisteCalculator"  = @DI\Inject("kgc.stat.calculator.phoniste")
     * })
     */
    public function __construct(CalculatorInterface $phonisteCalculator)
    {
        $this->phonisteCalculator = $phonisteCalculator;
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('phoniste_current_bonus', [$this, 'getPhonisteCurrentBonus']),
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'stat';
    }

    /**
     * @param PhonisteParameter $params
     * @param $done
     *
     * @return mixed
     */
    public function getPhonisteCurrentBonus(PhonisteParameter $params, $done)
    {
        return $this->phonisteCalculator->getCurrentBonus($params, $done);
    }
}
