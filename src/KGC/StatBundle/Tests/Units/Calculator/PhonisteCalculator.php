<?php

namespace KGC\StatBundle\Tests\Units\Calculator;

use KGC\StatBundle\Calculator\PhonisteCalculator as BasePhonisteCalculator;
use atoum\test;
use KGC\StatBundle\Entity\PhonisteParameter;
use KGC\StatBundle\Ruler\PhonisteRuler;
use KGC\UserBundle\Entity\Utilisateur;

class testedClass extends BasePhonisteCalculator
{
    /**
     * Return Phoning params from database.
     *
     * @return PhonisteParameter
     */
    protected function getPhonisteParams()
    {
        $params = new PhonisteParameter();
        $params->setBonusSimple(2);
        $params->setMaxObjectivePerDay(30);

        $params->setBonusFirstThreshold(12.00);
        $params->setBonusSecondThreshold(17.00);
        $params->setBonusThirdThreshold(25.00);
        $params->setBonusFourthThreshold(50.00);

        $params->setFirstThreshold(10);
        $params->setSecondThreshold(14);
        $params->setThirdThreshold(27);
        $params->setFourthThreshold(35);

        return $params;
    }

    /**
     * @param $currentUser
     * @param array $data
     *
     * @return array
     */
    public function getPhonisteDateIntervalRdv($currentUser, array $data)
    {
        return ['done' => 15, 'done_month' => 150, 'bonus_month' => 585];
    }

    protected function getUserOrError()
    {
        return new Utilisateur();
    }
}

class PhonisteCalculator extends test
{
    public function testCalculate()
    {
        $this
            ->given($em = new \mock\Doctrine\ORM\EntityManagerInterface())
            ->and($or = new \mock\Doctrine\Common\Persistence\ObjectRepository())
            ->and($sc = new \mock\Symfony\Component\Security\Core\SecurityContextInterface())
            ->and($sm = new \mock\KGC\UserBundle\Service\SalaryManager($em))
            ->and($ur = new \mock\Doctrine\Common\Persistence\ObjectRepository())
            ->and($calculator = new \mock\KGC\StatBundle\Tests\Units\Calculator\testedClass($em, $or, $sc, $sm, $ur))
            ->and($calculator->setPhonisteRuler(new PhonisteRuler(
                new \mock\Doctrine\ORM\EntityManagerInterface()
            )))
            ->then
                ->object($calculator)->isInstanceOf('KGC\StatBundle\Calculator\CalculatorInterface')
            ->then
                ->if($params = $calculator->calculate())
                ->array(array_keys($params))->isIdenticalTo(['params', 'done', 'validated', 'done_month', 'validated_month'])
                ->object($params['params'])->isInstanceOf('KGC\StatBundle\Entity\PhonisteParameter')
                ->variable($params['done'])->isNull()

            ->then
                ->if($params = $calculator->calculate([
                    'get_count' => true,
                    'get_objective' => true,
                    'get_validated' => true,
                ]))
                ->array(array_keys($params))->isIdenticalTo([
                    'done',
                    'done_month',
                    'bonus_month',
                    'objective',
                    'rate',
                    'before_bonus',
                    'first',
                    'second',
                    'third',
                    'validated',
                    'validated_month',
                ])
                ->variable($params['done'])->isEqualTo(15)

            ->then
                ->array($params)->isIdenticalTo([
                    'done' => 15,
                    'done_month' => 150,
                    'bonus_month' => 585,
                    'objective' => 30,
                    'rate' => (float) 50,
                    'before_bonus' => 15,
                    'first' => [
                        'rate' => (float) 150,
                        'before_bonus' => 0,
                    ],
                    'second' => [
                        'rate' => (float) 125,
                        'before_bonus' => 0,
                    ],
                    'third' => [
                        'rate' => 7.69,
                        'before_bonus' => 12,
                    ],
                    'validated' => 0,
                    'validated_month' => 0,
                ])
        ;
    }

    public function testGetCurrentBonus()
    {
        $this
            ->given($em = new \mock\Doctrine\ORM\EntityManagerInterface())
            ->and($or = new \mock\Doctrine\Common\Persistence\ObjectRepository())
            ->and($sc = new \mock\Symfony\Component\Security\Core\SecurityContextInterface())
            ->and($sm = new \mock\KGC\UserBundle\Service\SalaryManager($em))
            ->and($ur = new \mock\Doctrine\Common\Persistence\ObjectRepository())
            ->and($calculator = new \mock\KGC\StatBundle\Tests\Units\Calculator\testedClass($em, $or, $sc, $sm, $ur))
            ->and($calculator->setPhonisteRuler(new PhonisteRuler(
                new \mock\Doctrine\ORM\EntityManagerInterface()
            )))
            ->then
                ->object($calculator)->isInstanceOf('KGC\StatBundle\Calculator\CalculatorInterface')
            ->then
                ->given($calculator->getMockController()->getPhonisteDateIntervalRdv = function () {
                    return ['done' => 0];
                })
                ->if($bonus = $calculator->getCurrentBonus())
                ->variable($bonus)->isIdenticalTo(0)

            ->then
                ->given($calculator->getMockController()->getPhonisteDateIntervalRdv = function () {
                    return ['done' => 9];
                })
                ->if($bonus = $calculator->getCurrentBonus())
                ->variable($bonus)->isIdenticalTo(0)
            ->then
                ->given($calculator->getMockController()->getPhonisteDateIntervalRdv = function () {
                    return ['done' => 10];
                })
                ->if($bonus = $calculator->getCurrentBonus())
                ->variable($bonus)->isIdenticalTo(12.00)

            ->then
                ->given($calculator->getMockController()->getPhonisteDateIntervalRdv = function () {
                    return ['done' => 13];
                })
                ->if($bonus = $calculator->getCurrentBonus())
                ->variable($bonus)->isIdenticalTo(12.00)
            ->then
                ->given($calculator->getMockController()->getPhonisteDateIntervalRdv = function () {
                    return ['done' => 14];
                })
                ->if($bonus = $calculator->getCurrentBonus())
                ->variable($bonus)->isIdenticalTo(17.00)

            ->then
                ->given($calculator->getMockController()->getPhonisteDateIntervalRdv = function () {
                    return ['done' => 26];
                })
                ->if($bonus = $calculator->getCurrentBonus())
                ->variable($bonus)->isIdenticalTo(17.00)
            ->then
                ->given($calculator->getMockController()->getPhonisteDateIntervalRdv = function () {
                    return ['done' => 27];
                })
                ->if($bonus = $calculator->getCurrentBonus())
                ->variable($bonus)->isIdenticalTo(25.00)

            ->then
                ->given($calculator->getMockController()->getPhonisteDateIntervalRdv = function () {
                    return ['done' => 34];
                })
                ->if($bonus = $calculator->getCurrentBonus())
                ->variable($bonus)->isIdenticalTo(25.00)
            ->then
                ->given($calculator->getMockController()->getPhonisteDateIntervalRdv = function () {
                    return ['done' => 35];
                })
                ->if($bonus = $calculator->getCurrentBonus())
                ->variable($bonus)->isIdenticalTo(50.00)

            ->then
                ->given($calculator->getMockController()->getPhonisteDateIntervalRdv = function () {
                    return ['done' => 999];
                })
                ->if($bonus = $calculator->getCurrentBonus())
                ->variable($bonus)->isIdenticalTo(50.00)
        ;
    }
}
