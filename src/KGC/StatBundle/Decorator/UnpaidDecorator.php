<?php

namespace KGC\StatBundle\Decorator;

use Doctrine\ORM\EntityManagerInterface;
use JMS\DiExtraBundle\Annotation as DI;
use KGC\RdvBundle\Entity\Tiroir;
use KGC\StatBundle\Calculator\AdminCalculator;

/**
 * Class UnpaidDecorator.
 *
 * @DI\Service("kgc.stat.decorator.unpaid")
 */
class UnpaidDecorator implements DecoratorInterface
{
    const SHOW_DETAILS = false;
    const TOTAL_KEY = 'Total';

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    protected $aDossier = [];

    protected function initGrid(\DateTime $date)
    {
        $grid = [];
        $month = $this->buildSlideMonth($date);
        $month = array_merge($month, [$date->format('m/Y')]);
        $lines = [
            'REALISE',
            'ENCAISSE',
            'FNA',
            'OPPO',
            'REMBOURSEMENTS',
            'RECUP',
            'RESTE A RECUP',
        ];

        foreach ($lines as $l) {
            $grid[$l] = [];
            foreach ($month as $m) {
                $grid[$l][$m] = 0;
            }
            $grid[$l][self::TOTAL_KEY] = 0;
        }

        return $grid;
    }

    protected function initStatGrid(\DateTime $date)
    {
        $grid = [];
        $month = $this->buildSlideMonth($date);
        $month = array_merge($month, [$date->format('m/Y')]);
        $monthDateTime = array_merge($this->buildSlideMonthDateTime($date), [$date->format('Y-m-d')]);

        $rep_tir = $this->em->getRepository('KGCRdvBundle:Tiroir');
        $rep_dos = $this->em->getRepository('KGCRdvBundle:Dossier');

        $tiroirFNA = $rep_tir->findOneByIdcode(Tiroir::UNPAID);
        $list[] = $tiroirFNA;
        $list = array_merge($list, $rep_dos->getDossiersSwitchTiroirCode(Tiroir::UNPAID, true));
        foreach ($list as $classement) {
            $this->aDossier[$classement->getId()] = $classement->getLibelle();
        }
        $lines = $this->aDossier;
        $lines[strtoupper(self::TOTAL_KEY)] = strtoupper(self::TOTAL_KEY);
        foreach ($lines as $id_classement => $l) {
            $grid[$l] = [];
            foreach ($month as $idx => $m) {
                $grid[$l][$m] = ['CA' => 0, 'NB' => 0, 'CLASSEMENT' => $id_classement, 'DATE' => $monthDateTime[$idx]];
            }
            $grid[$l][strtoupper(self::TOTAL_KEY)] = ['CA' => 0, 'NB' => 0, 'CLASSEMENT' => $id_classement, 'DATE' => 'TOTAL'];
        }

        return $grid;
    }

    /**
     * @param \DateTime $date
     *
     * @return array
     */
    protected function buildSlideMonth(\DateTime $date)
    {
        list($begin, $end) = AdminCalculator::getSlidingDateByOffset($date, AdminCalculator::UNPAID_MONTH_SLIDE_OFFSET);

        $aDates = array();

        while ($begin->getTimestamp() < $end->getTimestamp()) {
            $aDates[] = $begin->format('m/Y');
            $begin->add(new \DateInterval('P01M'));
        }

        return $aDates;
    }

    /**
     * @param \DateTime $date
     *
     * @return array
     */
    protected function buildSlideMonthDateTime(\DateTime $date)
    {
        list($begin, $end) = AdminCalculator::getSlidingDateByOffset($date, AdminCalculator::UNPAID_MONTH_SLIDE_OFFSET);

        $aDates = array();

        while ($begin->getTimestamp() < $end->getTimestamp()) {
            $aDates[] = $begin->format('Y-m-d');
            $begin->add(new \DateInterval('P01M'));
        }

        return $aDates;
    }

    /**
     * @param array     $dataToDecorate
     * @param \DateTime $date
     * @param $src
     * @param $dest
     *
     * @return array
     */
    protected function buildUnpaidGridByNature(array $dataToDecorate, \DateTime $date, $src, $dest)
    {
        $grid = $this->initGrid($date);
        foreach ($dataToDecorate['unpaid'][$src] as $o) {
            $month = str_pad($o['month'], 2, '0', STR_PAD_LEFT);
            $grid[$dest][$month.'/'.$o['year']] = $o['amount'];
            $grid[$dest][self::TOTAL_KEY] += $o['amount'];
        }

        return $grid;
    }

    /**
     * @param array     $dataToDecorate
     * @param \DateTime $date
     *
     * @return array
     */
    protected function buildUnpaidGrid(array $dataToDecorate, \DateTime $date)
    {
        $finalGrid = $this->initGrid($date);

        $oppo = $this->buildUnpaidGridByNature($dataToDecorate, $date, 'oppo', 'OPPO');
        $rb = $this->buildUnpaidGridByNature($dataToDecorate, $date, 'refund', 'REMBOURSEMENTS');
        $recup = $this->buildUnpaidGridByNature($dataToDecorate, $date, 'recup', 'RECUP');
        $billing = $this->buildUnpaidGridByNature($dataToDecorate, $date, 'billing', 'REALISE');
        $paid = $this->buildUnpaidGridByNature($dataToDecorate, $date, 'paid', 'ENCAISSE');

        foreach ($finalGrid as $type => $line) {
            foreach ($line as $dateTmp => $cell) {
                // Values
                $finalGrid['REALISE'][$dateTmp] = $billing['REALISE'][$dateTmp];
                $finalGrid['ENCAISSE'][$dateTmp] = $paid['ENCAISSE'][$dateTmp];

                $finalGrid['OPPO'][$dateTmp] = $oppo['OPPO'][$dateTmp];
                $finalGrid['REMBOURSEMENTS'][$dateTmp] = $oppo['REMBOURSEMENTS'][$dateTmp];
                $finalGrid['RECUP'][$dateTmp] = $recup['RECUP'][$dateTmp];
                $finalGrid['FNA'][$dateTmp] = $billing['REALISE'][$dateTmp] - $paid['ENCAISSE'][$dateTmp];
                $finalGrid['RESTE A RECUP'][$dateTmp] = $finalGrid['FNA'][$dateTmp] - $finalGrid['RECUP'][$dateTmp];
            }
        }

        $m = (int) $date->format('m');
        $y = $date->format('Y');
        $tmp = new \Datetime(date('Y-m-d 00:00', mktime(0, 0, 0, $m + 1, 1, $y)));
        $month = $date->format('m/Y');

        $oppoCurrent = $this->buildUnpaidGridByNature($dataToDecorate, $tmp, 'oppo_current', 'OPPO');
        $rbCurrent = $this->buildUnpaidGridByNature($dataToDecorate, $tmp, 'refund_current', 'REMBOURSEMENTS');
        $recupCurrent = $this->buildUnpaidGridByNature($dataToDecorate, $tmp, 'recup_current', 'RECUP');
        $billingCurrent = $this->buildUnpaidGridByNature($dataToDecorate, $tmp, 'billing_current', 'REALISE');
        $paidCurrent = $this->buildUnpaidGridByNature($dataToDecorate, $tmp, 'paid_current', 'ENCAISSE');

        $finalGrid['REALISE'][$month] = $billingCurrent['REALISE'][$month];
        $finalGrid['ENCAISSE'][$month] = $paidCurrent['ENCAISSE'][$month];

        $finalGrid['OPPO'][$month] = $oppoCurrent['OPPO'][$month];
        $finalGrid['REMBOURSEMENTS'][$month] = $rbCurrent['REMBOURSEMENTS'][$month];
        $finalGrid['RECUP'][$month] = $recupCurrent['RECUP'][$month];
        $finalGrid['FNA'][$month] = $billingCurrent['REALISE'][$month] - $paidCurrent['ENCAISSE'][$month];
        $finalGrid['RESTE A RECUP'][$month] = $finalGrid['FNA'][$month] - $finalGrid['RECUP'][$month];

        $finalGrid['OPPO'][self::TOTAL_KEY] += $finalGrid['OPPO'][$month];
        $finalGrid['REMBOURSEMENTS'][self::TOTAL_KEY] += $finalGrid['REMBOURSEMENTS'][$month];
        $finalGrid['RECUP'][self::TOTAL_KEY] += $finalGrid['RECUP'][$month];
        $finalGrid['FNA'][self::TOTAL_KEY] += $finalGrid['FNA'][$month];
        $finalGrid['RESTE A RECUP'][self::TOTAL_KEY] += $finalGrid['RESTE A RECUP'][$month];

        if (!self::SHOW_DETAILS) {
            unset($finalGrid['REALISE']);
            unset($finalGrid['ENCAISSE']);
        }

        return $finalGrid;
    }
    /**
     * @param array     $dataToDecorate
     * @param \DateTime $date
     *
     * @return array
     */
    protected function buildUnpaidStatGrid(array $dataToDecorate, \DateTime $date)
    {
        $finalGrid = $this->initStatGrid($date);

        $rep_rdv = $this->em->getRepository('KGCRdvBundle:RDV');
        $totalNb = $totalCa = array();
        foreach ($finalGrid as $type => $line) {
            if($type != strtoupper(self::TOTAL_KEY)) {
                $totalNbType = $totalCAType = 0;
                foreach ($line as $dateTmp => $cell) {
                    if($dateTmp != strtoupper(self::TOTAL_KEY)) {
                        $explode = explode('/',$dateTmp);
                        $startDate = new \DateTime($explode[0].'/01/'.$explode[1]);
                        $endClone = null;
                        if ($startDate) {
                            $endClone = clone $startDate;
                            $endClone->modify('last day of this month');
                            $endClone->add(new \DateInterval('P1D'));
                        }
                        $dateinterval = ['begin' => $startDate, 'end' => $endClone];
                        $nb = $rep_rdv->getUnpaidByClassementCount(array_search($type, $this->aDossier), $dateinterval, 'rdv', array());
                        $totalNbType += $nb;
                        if(empty($totalNb[$dateTmp])) {
                            $totalNb[$dateTmp] = 0;
                        }
                        $totalNb[$dateTmp] += $nb;
                        $finalGrid[$type][$dateTmp]['NB'] = $nb;
                        if($nb) {
                            $ca = 0;
                            foreach ($rep_rdv->getUnpaidByClassement(array_search($type, $this->aDossier), null, $dateinterval, 'rdv', array()) as $rdv) {
                                $ca += $rdv->getTarification() ? ($rdv->getTarification()->getMontantTotal() - $rdv->getMontantEncaisse()) : 0;
                            }
                            $totalCAType += $ca;
                            if(empty($totalCa[$dateTmp])) {
                                $totalCa[$dateTmp] = 0;
                            }
                            $totalCa[$dateTmp] += $ca;
                            $finalGrid[$type][$dateTmp]['CA'] = $ca;
                        }
                    }
                }
                $finalGrid[$type][strtoupper(self::TOTAL_KEY)]['NB'] = $totalNbType;
                if(empty($totalNb[strtoupper(self::TOTAL_KEY)])) {
                    $totalNb[strtoupper(self::TOTAL_KEY)] = 0;
                }
                $totalNb[strtoupper(self::TOTAL_KEY)] += $totalNbType;

                $finalGrid[$type][strtoupper(self::TOTAL_KEY)]['CA'] = $totalCAType;
                if(empty($totalCa[strtoupper(self::TOTAL_KEY)])) {
                    $totalCa[strtoupper(self::TOTAL_KEY)] = 0;
                }
                $totalCa[strtoupper(self::TOTAL_KEY)] += $totalCAType;
            }
        }
        $finalGrid[strtoupper(self::TOTAL_KEY)][$dateTmp]['CLASSEMENT'] = 'TOTAL';
        $finalGrid[strtoupper(self::TOTAL_KEY)][$dateTmp]['DATE'] = 'TOTAL';
        foreach($totalNb as $dateTmp => $nb) {
            $finalGrid[strtoupper(self::TOTAL_KEY)][$dateTmp]['NB'] = $nb;
        }
        foreach($totalCa as $dateTmp => $ca) {
            $finalGrid[strtoupper(self::TOTAL_KEY)][$dateTmp]['CA'] = $ca;
        }
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
        if (empty($config['date'])) {
            throw new \InvalidArgumentException('You must specify a date configuration');
        }

        return $this->buildUnpaidGrid($dataToDecorate, $config['date']);
    }

    /**
     * @param array $dataToDecorate
     * @param array $config
     *
     * @return array
     */
    public function decorateStat(array $dataToDecorate, array $config)
    {
        if (empty($config['date'])) {
            throw new \InvalidArgumentException('You must specify a date configuration');
        }

        return $this->buildUnpaidStatGrid($dataToDecorate, $config['date']);
    }
}
