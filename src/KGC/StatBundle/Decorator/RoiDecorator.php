<?php

namespace KGC\StatBundle\Decorator;

use Doctrine\ORM\EntityManagerInterface;
use JMS\DiExtraBundle\Annotation as DI;
use KGC\StatBundle\Calculator\AdminCalculator;

/**
 * Class RoiDecorator.
 *
 * @DI\Service("kgc.stat.decorator.roi")
 */
class RoiDecorator implements DecoratorInterface
{
    const TOTAL_KEY = 'Total';
    const OPPO_KEY = 'Oppo';
    const REFUND_KEY = 'Remboursement';
    const AMOUNT_KEY = 'montant';

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @param \DateTime $date
     *
     * @return array
     */
    protected function buildMonthDays(\DateTime $date)
    {
        list($begin, $end) = AdminCalculator::getFullMonthIntervalFromDate($date);

        $aDates = array();

        while ($begin->getTimestamp() < $end->getTimestamp()) {
            $aDates[] = $begin->format('d/m/Y');
            $begin->add(new \DateInterval('P1D'));
        }

        return $aDates;
    }

    protected function buildTpeList()
    {
        $result = [];
        $objects = $this->em->getRepository('KGCRdvBundle:TPE')->findAll();
        foreach ($objects as $o) {
            if ('XSITE' !== $o->getLibelle()) {
                $result[] = $o->getLibelle();
            }
        }

        return $result;
    }

    protected function buildPaymentList()
    {
        $result = [];
        $objects = $this->em->getRepository('KGCRdvBundle:MoyenPaiement')->findAll();
        foreach ($objects as $o) {
            $result[] = $o->getLibelle();
        }

        return $result;
    }

    protected function initGrid(\DateTime $date, $withPaiement = true)
    {
        $grid = [];
        $monthDays = $this->buildMonthDays($date);
        $tpeList = $this->buildTpeList();
        $paymentList = $this->buildPaymentList();

        // We need to create a 2 dimension array to have values
        // 1 .for each TPE/payment type
        // 2. for each date of the month
        foreach ($monthDays as $d) {
            $grid[$d] = [];
            foreach ($tpeList as $tpe) {
                $grid[$d][$tpe][self::AMOUNT_KEY] = 0;
                // We need a total amount for each tpe !
                $grid[self::TOTAL_KEY][$tpe] = 0;
            }
            if ($withPaiement) {
                foreach ($paymentList as $payment) {
                    $grid[$d][$payment][self::AMOUNT_KEY] = 0;
                    // We need a total amount for each payment type !
                    $grid[self::TOTAL_KEY][$payment] = 0;
                }
            }
            // We need a oppo and refund amount for each date !
            $grid[$d][self::OPPO_KEY][self::AMOUNT_KEY] = 0;
            $grid[$d][self::REFUND_KEY][self::AMOUNT_KEY] = 0;

            // We need a total amount for each date !
            $grid[$d][self::TOTAL_KEY] = 0;

            // We need a total amount for each "by date" total !
            $grid[self::TOTAL_KEY][self::OPPO_KEY] = 0;
            $grid[self::TOTAL_KEY][self::REFUND_KEY] = 0;
            $grid[self::TOTAL_KEY][self::TOTAL_KEY] = 0;

            ksort($grid);
        }
        return $grid;
    }

    /**
     * Calculates datagrid values in order to display readable information
     * basically, it builds the table
     *
     * @param array     $dataToDecorate
     * @param \DateTime $date
     *
     * @return mixed
     */
    protected function completeValues(array $dataToDecorate, \DateTime $date)
    {
        // First table (Pointage CA hors sécurisation)
        $finalGrid = $this->initGrid($date);
        // Second table (Pointage CA Sécurisation)
        $finalSecuGrid = $this->initGrid($date, false);
        // Third table (Pointage CA Total)
        $finalTotalGrid = array();

        // Ajout des CA des TPE au Pointage CA hors sécurisation
        foreach ($dataToDecorate['ca']['tpe'] as $tpe) {
            $dateTmp = new \DateTime($tpe['date']);
            $dateFormatted = $dateTmp->format('d/m/Y');
            $tpe_name = $tpe['tpe_name'];
            $tpe_amount = $tpe['nb'] / 100;
            
            $finalGrid[$dateFormatted][$tpe_name][self::AMOUNT_KEY] += $tpe_amount;
            $finalGrid[$dateFormatted][self::TOTAL_KEY] += $tpe_amount;
            $finalGrid[self::TOTAL_KEY][$tpe_name] += $tpe_amount;
        }
        
        // Ajout des CA des Moyens de Paiement au Pointage CA hors sécurisation
        foreach ($dataToDecorate['ca']['payment'] as $p) {
            $dateTmp = new \DateTime($p['date']);
            $dateFormatted = $dateTmp->format('d/m/Y');
            $payment_name = $p['payment_name'];
            $payment_amount = $p['nb'] / 100;
            
            $finalGrid[$dateFormatted][$payment_name][self::AMOUNT_KEY] += $payment_amount;
            $finalGrid[$dateFormatted][self::TOTAL_KEY] += $payment_amount;
            $finalGrid[self::TOTAL_KEY][$payment_name] += $payment_amount;
        }
        
        // Ajout des Telecollectes au Pointage CA hors sécurisation
        foreach ($dataToDecorate['ca']['tel'] as $tel) {
            $dateTmp = new \DateTime($tel['date']);
            $dateFormatted = $dateTmp->format('d/m/Y');
            $tpe_name = $tel['tpe_name'];
            $finalGrid[$dateFormatted][$tpe_name]['telecollecte'] = [
                'id' => $tel['id'],
                'tel1' => $tel['tel1'] / 100,
                'tel2' => $tel['tel2'] / 100,
                'tel3' => $tel['tel3'] / 100,
                'ttl' => $tel['total'] / 100,
            ];
        }
        
        // Ajout des Oppos au Pointage CA hors sécurisation
        foreach($dataToDecorate['ca']['oppo'] as $oppo) {
            $dateTmp = new \DateTime($oppo['date']);
            $dateFormatted = $dateTmp->format('d/m/Y');
            $oppoValue = -($oppo['nb'] / 100);
            
            $finalGrid[$dateFormatted][self::OPPO_KEY][self::AMOUNT_KEY] = $oppoValue;
            $finalGrid[$dateFormatted][self::TOTAL_KEY] += $oppoValue;
            $finalGrid[self::TOTAL_KEY][self::OPPO_KEY] += $oppoValue;
        }

        // Ajout des remboursements au Pointage CA hors sécurisation
        foreach($dataToDecorate['ca']['refund'] as $refund) {
            $dateTmp = new \DateTime($refund['date']);
            $dateFormatted = $dateTmp->format('d/m/Y');
            $refundValue = -($refund['nb'] / 100);

            $finalGrid[$dateFormatted][self::REFUND_KEY][self::AMOUNT_KEY] = $refundValue;
            $finalGrid[$dateFormatted][self::TOTAL_KEY] += $refundValue;
            $finalGrid[self::TOTAL_KEY][self::REFUND_KEY] += $refundValue;
        }

        // Calcul de la ligne des totaux du mois pour Pointage CA hors sécurisation
        foreach ($finalGrid[self::TOTAL_KEY] as $tpe => $total) {
            if (self::TOTAL_KEY !== $tpe) {
                $finalGrid[self::TOTAL_KEY][self::TOTAL_KEY] += $finalGrid[self::TOTAL_KEY][$tpe];
            }
        }
        
        // Pointage CA hors Sécurisation
        foreach ($dataToDecorate['ca']['secu'] as $secu) {
            $dateTmp = new \DateTime($secu['date']);
            $dateFormatted = $dateTmp->format('d/m/Y');
            
            $finalSecuGrid[$dateFormatted][$secu['tpe_name']] = $secu['nb'];
            $finalSecuGrid[$dateFormatted][self::TOTAL_KEY] += $secu['nb'];
            $finalSecuGrid[self::TOTAL_KEY][$secu['tpe_name']] += $finalSecuGrid[$dateFormatted][$secu['tpe_name']];
        }
        
        // Calcul de la ligne des totaux du mois pour Pointage CA Sécurisation
        foreach ($finalSecuGrid[self::TOTAL_KEY] as $tpe => $total) {
            if (self::TOTAL_KEY !== $tpe) {
                $finalSecuGrid[self::TOTAL_KEY][self::TOTAL_KEY] += $finalSecuGrid[self::TOTAL_KEY][$tpe];
            }
        }

        // Calcul de Pointage CA Total
        $finalTotalGrid = array_merge_recursive($finalGrid, $finalSecuGrid);

        array_walk(
            $finalTotalGrid,
            function(&$v, $k){
                $v = array_map(function($i){
                    if(is_array($i)){
                        if(is_numeric(key($i))){
                            return array_sum($i);
                        } else {
                            foreach ($i as $key => &$j) {
                                if (is_numeric($key) && isset($i[self::AMOUNT_KEY])){
                                    $i[self::AMOUNT_KEY] = is_array($i[self::AMOUNT_KEY]) ? array_sum($i[self::AMOUNT_KEY]) + $j : $i[self::AMOUNT_KEY] + $j;
                                } else {
                                    $j = is_array($j) ? array_sum($j) : $j;
                                }
                            }
                            return $i;
                        }
                    } else {
                        return $i;
                    }
                }, $v);
            }
        );
        

        return [$finalGrid, $finalSecuGrid, $finalTotalGrid];
    }

    /**
     * @param array $grid
     *
     * @return array
     */
    protected function completePercentages(array $grid)
    {
        $tpeList = $this->buildTpeList();
        $paymentList = $this->buildPaymentList();

        $totalPercentage = 0;
        $total = $grid[self::TOTAL_KEY][self::TOTAL_KEY];

        foreach ($tpeList as $tpe) {
            $grid['%'][$tpe] = $total != 0 ? $grid[self::TOTAL_KEY][$tpe] / $total * 100 : 0;
            $totalPercentage += $grid['%'][$tpe];
        }
        foreach ($paymentList as $payment) {
            if (array_key_exists($payment, $grid[self::TOTAL_KEY])) {
                $grid['%'][$payment] = $total != 0 ? $grid[self::TOTAL_KEY][$payment] / $total * 100 : 0;
                $totalPercentage += $grid['%'][$payment];
            }
        }

        $grid['%'][self::OPPO_KEY] = $total != 0 ? $grid[self::TOTAL_KEY][self::OPPO_KEY] / $total * 100 : 0;
        $totalPercentage += $grid['%'][self::OPPO_KEY];

        $grid['%'][self::REFUND_KEY] = $total != 0 ? $grid[self::TOTAL_KEY][self::REFUND_KEY] / $total * 100 : 0;
        $totalPercentage += $grid['%'][self::REFUND_KEY];

        $grid['%'][self::TOTAL_KEY] = $totalPercentage;

        return $grid;
    }

    /**
     * @param array $dataToDecorate
     * @param $selectTpe
     * @param $selectDate
     *
     * @return array
     */
    protected function buildDetails(array $dataToDecorate, $selectTpe, $selectDate)
    {
        $result = [];

        foreach ($dataToDecorate['ca'] as $values) {
            foreach ($values as $v) {
                $tmp = new \DateTime($v['date']);
                $date = $tmp->format('d/m/Y');

                if ($selectDate == $date) {
                    if (!array_key_exists($date, $result)) {
                        $result[$date] = [];
                    }

                    if (isset($v['nb_enc'])) {
                        $tpeOrPayment = isset($v['tpe_name']) ? $v['tpe_name'] : (isset($v['payment_name']) ? $v['payment_name'] : '');

                        if ($tpeOrPayment == $selectTpe) {
                            if (!array_key_exists($tpeOrPayment, $result[$date])) {
                                $result[$date][$tpeOrPayment] = [];
                            }
                            $result[$date][$tpeOrPayment][] = ['rdv' => $v['id'], 'amount' => $v['nb'], 'nb' => $v['nb_enc'], 'user' => $v['prenom'].' '.$v['nom']];
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param array $dataToDecorate
     * @param array $config
     *
     * @return array
     */
    protected function buildCaGrid(array $dataToDecorate, array $config)
    {
        $date = $config['date'];

        $finalGrid = $finalSecuGrid = $finalTotalGrid = $details = [];

        if (isset($config['details']) && $config['details']) {
            $details = $this->buildDetails($dataToDecorate, $config['select_tpe'], $config['select_date']);
        } else {
            list($finalGrid, $finalSecuGrid, $finalTotalGrid) = $this->completeValues($dataToDecorate, $date);
            $finalGrid = $this->completePercentages($finalGrid, $date);
            $finalSecuGrid = $this->completePercentages($finalSecuGrid, $date);

            // $finalTotalGrid = $this->completePercentages($finalTotalGrid, $date);
        }

        if(isset($finalGrid[RoiDecorator::TOTAL_KEY])) {
            //if the result line is set to 0, we assume that the whole column is empty
            //so, we unset all $key cell in the three array
            foreach ($finalGrid[RoiDecorator::TOTAL_KEY] as $key => $value) {
                // Helper to unset value for array
                $unset = function (&$a) use ($key) {
                    unset($a[$key]);
                };

                if (
                    $key !== RoiDecorator::TOTAL_KEY &&
                    $key !== RoiDecorator::OPPO_KEY &&
                    $key !== RoiDecorator::REFUND_KEY &&
                    $value === 0
                ) {
                    array_walk($finalGrid, $unset);
                    array_walk($finalSecuGrid, $unset);
                    array_walk($finalTotalGrid, $unset);
                }
            }
        }

        return [$finalGrid, $finalSecuGrid, $finalTotalGrid, $details];
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
        if (empty($config['date'])) {
            throw new \InvalidArgumentException('You must specify a date configuration');
        }

        return $this->buildCaGrid($dataToDecorate, $config);
    }
}
