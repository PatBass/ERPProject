<?php

namespace KGC\RdvBundle\Tests\Units\Service;

use KGC\RdvBundle\Service\PlanningService as BasePlanningService;
use KGC\RdvBundle\Tests\Repository\RDVRepository;
use KGC\UserBundle\Entity\Utilisateur;
use atoum\test;

/**
 * To mock some protected functions.
 *
 * Class testedClass
 */
class testedClass extends BasePlanningService
{
    public function constructPlanning(\Datetime $debut, \Datetime $fin, $type = 'select')
    {
        return parent::constructPlanning($debut, $fin, $type);
    }

    protected function getRdvRepository()
    {
        return new RDVRepository();
    }

    protected function getDebutFinSimple($hour)
    {
        return parent::getDebutFinSimple('17:00');
    }
}

class PlanningService extends test
{
    protected $beginHour = '10:00';

    protected function getTodayDate()
    {
        return date('d/m/Y', time());
    }

    protected function getPlanningServiceInstance()
    {
        return new testedClass(
            new \mock\Doctrine\Common\Persistence\ObjectManager(),
            new \mock\Symfony\Component\Form\FormFactoryInterface()
        );
    }

    protected function getFakeDebutFin()
    {
        return [
            \Datetime::createFromFormat(BasePlanningService::INTERVALLE_DATE_FORMAT, $this->beginHour),
            \Datetime::createFromFormat(BasePlanningService::INTERVALLE_DATE_FORMAT, $this->beginHour)
                ->add(new \DateInterval(BasePlanningService::PLANNING_INTERVALLE)),
        ];
    }

    public function testConstructPlanningSelect()
    {
        $today = $this->getTodayDate();
        list($debut, $fin) = $this->getFakeDebutFin();

        $this
            ->given($planningService = $this->getPlanningServiceInstance())
            ->and($planning = $planningService->constructPlanning($debut, $fin))
            ->then
                ->object($planningService)->isInstanceOf('KGC\RdvBundle\Service\PlanningService')
                ->array($planning)->isIdenticalTo([
                    '10:00' => ['value' => $today.' 10:00', 'nb' => 0],
                    '10:30' => ['value' => $today.' 10:30', 'nb' => 0],
                    '11:00' => ['value' => $today.' 11:00', 'nb' => 0],
                ])
        ;
    }

    public function testConstructPlanningSimple()
    {
        list($debut, $fin) = $this->getFakeDebutFin();

        $this
            ->given($planningService = $this->getPlanningServiceInstance())
            ->and($planning = $planningService->constructPlanning($debut, $fin, 'default'))
            ->then
                ->object($planningService)->isInstanceOf('KGC\RdvBundle\Service\PlanningService')
                ->array($planning)->isIdenticalTo([
                    '10:00' => [1 => null, 2 => null, 3 => null, 4 => null, 5 => null, 6 => null, 7 => null, 8 => null],
                    '10:30' => [1 => null, 2 => null, 3 => null, 4 => null, 5 => null, 6 => null, 7 => null, 8 => null],
                    '11:00' => [1 => null, 2 => null, 3 => null, 4 => null, 5 => null, 6 => null, 7 => null, 8 => null],
                ])

        ;
    }

    public function testBuildSimplePlanning()
    {
        $this
            ->given($planningService = $this->getPlanningServiceInstance())
            ->and($user = new Utilisateur())
            ->and($planning = $planningService->buildSimplePlanning($user, false))
            ->then
                ->array($planning)->size->isEqualTo(3)
                ->array($planning['planning'])->size->isEqualTo(3)
                ->array($planning['alerte'])->size->isEqualTo(1)
                ->array($planning['liste'])->size->isEqualTo(3)

            ->given($firstHour = '17:00')
            ->and($secondHour = '17:30')
            ->and($thirdHour = '18:00')

            // planning
            ->then
                ->variable($planning['planning'][$firstHour][1])->isNull()
                ->variable($planning['planning'][$firstHour][2])->isNull()
                ->variable($planning['planning'][$firstHour][3])->isNull()
                ->variable($planning['planning'][$firstHour][4])->isNull()
                ->variable($planning['planning'][$firstHour][5])->isNull()
                ->variable($planning['planning'][$firstHour][6])->isNull()

                ->object($planning['planning'][$secondHour][1])->isInstanceOf('KGC\RdvBundle\Entity\RDV')
                ->variable($planning['planning'][$secondHour][2])->isNull()
                ->variable($planning['planning'][$secondHour][3])->isNull()
                ->variable($planning['planning'][$secondHour][4])->isNull()
                ->variable($planning['planning'][$secondHour][5])->isNull()
                ->variable($planning['planning'][$secondHour][6])->isNull()

                ->object($planning['planning'][$thirdHour][1])->isInstanceOf('KGC\RdvBundle\Entity\RDV')
                ->variable($planning['planning'][$thirdHour][2])->isNull()
                ->variable($planning['planning'][$thirdHour][3])->isNull()
                ->variable($planning['planning'][$thirdHour][4])->isNull()
                ->variable($planning['planning'][$thirdHour][5])->isNull()
                ->variable($planning['planning'][$thirdHour][6])->isNull()

            //alerte
            ->then
                ->object($planning['alerte'][$firstHour][0])->isInstanceOf('KGC\RdvBundle\Entity\RDV')

            //liste
            ->then
                ->object($planning['liste'][0])->isInstanceOf('KGC\RdvBundle\Entity\RDV')
                ->object($planning['liste'][1])->isInstanceOf('KGC\RdvBundle\Entity\RDV')
                ->object($planning['liste'][2])->isInstanceOf('KGC\RdvBundle\Entity\RDV')

        ;
    }

    public function testBuildSimplePlanningRemoveEmptySlots()
    {
        $this
            ->given($planningService = $this->getPlanningServiceInstance())
            ->and($user = new Utilisateur())
            ->and($planning = $planningService->buildSimplePlanning($user, true))
            ->then
                ->array($planning)->size->isEqualTo(3)
                ->array($planning['planning'])->size->isEqualTo(2)
                ->array($planning['alerte'])->size->isEqualTo(1)
                ->array($planning['liste'])->size->isEqualTo(3)

            ->given($firstHour = '17:00')
            ->and($secondHour = '17:30')
            ->and($thirdHour = '18:00')

            // planning
            ->then
                ->array($planning['planning'][$secondHour])->size->isEqualTo(1)
                ->object($planning['planning'][$secondHour][1])->isInstanceOf('KGC\RdvBundle\Entity\RDV')
                ->array($planning['planning'][$thirdHour])->size->isEqualTo(1)
                ->object($planning['planning'][$thirdHour][1])->isInstanceOf('KGC\RdvBundle\Entity\RDV')

            //alerte
            ->then
                ->object($planning['alerte'][$firstHour][0])->isInstanceOf('KGC\RdvBundle\Entity\RDV')

            //liste
            ->then
                ->object($planning['liste'][0])->isInstanceOf('KGC\RdvBundle\Entity\RDV')
                ->object($planning['liste'][1])->isInstanceOf('KGC\RdvBundle\Entity\RDV')
                ->object($planning['liste'][2])->isInstanceOf('KGC\RdvBundle\Entity\RDV')

        ;
    }

    public function testBuildSimplePlanningRemoveEmptySlotsEnd()
    {
        $this
            ->given($planningService = $this->getPlanningServiceInstance())
            ->and($user = new Utilisateur())
            ->and($end = new \Datetime('tomorrow'))
            ->and($planning = $planningService->buildSimplePlanning($user, true, $end))
            ->then
                ->array($planning)->size->isEqualTo(3)
                ->array($planning['planning'])->size->isEqualTo(3)
                ->array($planning['alerte'])->size->isEqualTo(1)
                ->array($planning['liste'])->size->isEqualTo(3)

            ->given($lastHour = '22:30')

            // planning
            ->then
                ->array($planning['planning'][$lastHour])->size->isEqualTo(1)
                ->object($planning['planning'][$lastHour][1])->isInstanceOf('KGC\RdvBundle\Entity\RDV')

        ;
    }
}
