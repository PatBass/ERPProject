<?php

namespace KGC\StatBundle\Decorator;

use Doctrine\ORM\EntityManagerInterface;
use JMS\DiExtraBundle\Annotation as DI;
use KGC\StatBundle\Ruler\PhonisteRuler;

/**
 * Class PhoningDecorator.
 *
 * @DI\Service("kgc.stat.decorator.phoning")
 */
class PhoningDecorator implements DecoratorInterface
{
    const NB_RDV_TOTAL = 'RDV TOTAL';
    const NB_RDV_10_MIN = 'RDV +10MIN';
    const PERCENTAGE_10_MIN = '% + 10MIN';
    const CA_RDV = 'CA SUR RDV';
    const CA_SECU = 'CA SECU';
    const CA_OPPO = 'CA OPPO';
    const MOYENNE = 'MOYENNE';
    const BONUS = 'Bonus %s RDV';
    const BONUS_TOTAL = 'Total Bonus';
    const ABSENCES = 'ABSENCES';
    const RETARDS = 'RETARDS';

    /**
     * @var EntityManagerInterface
     */
    protected $em;

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

    protected function addPhonisteIfNotExists(array $grid, array $data)
    {
        $params = $this->phonisteRuler->getPhonisteParams();

        if (!array_key_exists($data['name'], $grid)) {
            $grid[$data['name']] = [
                self::NB_RDV_TOTAL => 0,
                self::NB_RDV_10_MIN => 0,
                self::PERCENTAGE_10_MIN => 0,
                self::CA_RDV => 0,
                //self::CA_SECU => 0,
                self::CA_OPPO => 0,
                self::MOYENNE => 0,
            ];

            $grid[$data['name']] = array_merge($grid[$data['name']], [
                sprintf(self::BONUS, $params->getFirstThreshold()) => 0,
                sprintf(self::BONUS, $params->getSecondThreshold()) => 0,
                sprintf(self::BONUS, $params->getThirdThreshold()) => 0,
                sprintf(self::BONUS, $params->getFourthThreshold()) => 0,
                self::BONUS_TOTAL => 0,
            ]);

            $grid[$data['name']] = array_merge($grid[$data['name']], [
                self::ABSENCES => 0,
                self::RETARDS => 0,
            ]);
        }

        return $grid;
    }

    protected function buildBonusesValues(array $totalGrid, array $finalGrid, $data)
    {
        foreach ($data as $bonus) {
            list($threshold, $amount) = $this->phonisteRuler->getCurrentBonus(null, $bonus['nb']);
            if (0 != $threshold) {
                $finalGrid[$bonus['name']][sprintf(self::BONUS, $threshold)] += $amount;
                $finalGrid[$bonus['name']][self::BONUS_TOTAL] += $amount;

                $totalGrid['Total'][sprintf(self::BONUS, $threshold)] += $amount;
                $totalGrid['Total'][self::BONUS_TOTAL] += $amount;
            }
        }

        return [$totalGrid, $finalGrid];
    }

    /**
     * @param array $dataToDecorate
     *
     * @return array
     */
    protected function buildPhoningGrid(array $dataToDecorate)
    {
        $finalGrid = [];

        $totalGrid = $this->addPhonisteIfNotExists($finalGrid, ['name' => 'Total']);

        foreach ($dataToDecorate['phoning']['total'] as $t) {
            $finalGrid = $this->addPhonisteIfNotExists($finalGrid, $t);
            $finalGrid[$t['name']][self::NB_RDV_TOTAL] = (int) $t['nb'];
            $totalGrid['Total'][self::NB_RDV_TOTAL] += (int) $t['nb'];
        }

        foreach ($dataToDecorate['phoning']['10min'] as $t) {
            $finalGrid = $this->addPhonisteIfNotExists($finalGrid, $t);
            $finalGrid[$t['name']][self::NB_RDV_10_MIN] = (int) $t['nb'];
            $totalGrid['Total'][self::NB_RDV_10_MIN] += (int) $t['nb'];
        }

        foreach ($finalGrid as $name => $data) {
            $total = $finalGrid[$name][self::NB_RDV_TOTAL];
            $finalGrid[$name][self::PERCENTAGE_10_MIN] = $total ? (($finalGrid[$name][self::NB_RDV_10_MIN] * 100) / $total) : 0;

            $total = $totalGrid['Total'][self::NB_RDV_TOTAL];
            $totalGrid['Total'][self::PERCENTAGE_10_MIN] = $total ? (($totalGrid['Total'][self::NB_RDV_10_MIN] * 100) / $total) : 0;
        }

//        foreach ($dataToDecorate['phoning']['ca_secu'] as $t) {
//            $finalGrid = $this->addPhonisteIfNotExists($finalGrid, $t);
//            $finalGrid[$t['name']][self::CA_SECU] = (int) $t['nb'];
//        }

        foreach ($dataToDecorate['phoning']['ca_total'] as $t) {
            $finalGrid = $this->addPhonisteIfNotExists($finalGrid, $t);
            $finalGrid[$t['name']][self::CA_RDV] = $t['amount'];
            $totalGrid['Total'][self::CA_RDV] += $t['amount'];
        }

        foreach ($dataToDecorate['phoning']['ca_oppo'] as $t) {
            $finalGrid = $this->addPhonisteIfNotExists($finalGrid, $t);
            $finalGrid[$t['name']][self::CA_OPPO] = $t['amount'];
            $totalGrid['Total'][self::CA_OPPO] += $t['amount'];
        }

        foreach ($dataToDecorate['phoning']['ca_total'] as $t) {
            $total = $finalGrid[$t['name']][self::CA_RDV];
            $tenTotal = $finalGrid[$t['name']][self::NB_RDV_10_MIN];
            $finalGrid[$t['name']][self::MOYENNE] = $tenTotal ? $total / $tenTotal : 0;

            $total = $totalGrid['Total'][self::CA_RDV];
            $tenTotal = $totalGrid['Total'][self::NB_RDV_10_MIN];
            $totalGrid['Total'][self::MOYENNE] = $tenTotal ? $total / $tenTotal : 0;
        }

        list($totalGrid, $finalGrid) = $this->buildBonusesValues($totalGrid, $finalGrid, $dataToDecorate['phoning']['bonus']);

        foreach ($dataToDecorate['phoning']['lateness'] as $t) {
            $finalGrid = $this->addPhonisteIfNotExists($finalGrid, $t);
            $finalGrid[$t['name']][self::ABSENCES] = $t['abs_count'];
            $finalGrid[$t['name']][self::RETARDS] = $t['late_count'];

            $totalGrid['Total'][self::ABSENCES] += $t['abs_count'];
            $totalGrid['Total'][self::RETARDS] += $t['late_count'];
        }

        $finalGrid = array_merge($finalGrid, $totalGrid);

        return $finalGrid;
    }

    /**
     * @param EntityManagerInterface $em
     * @DI\InjectParams({
     *                                   "em" = @DI\Inject("doctrine.orm.entity_manager"),
     *                                   })
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param array $dataToDecorate
     * @param array $config
     *
     * @return array
     */
    public function decorate(array $dataToDecorate, array $config)
    {
        if (empty($dataToDecorate)) {
            throw new \InvalidArgumentException('You must specify NOT empty data to decorate !');
        }

        return $this->buildPhoningGrid($dataToDecorate);
    }
}
