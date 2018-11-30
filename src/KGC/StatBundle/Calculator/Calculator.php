<?php
// src/KGC/StatBundle/Calculator/Calculator.php

namespace KGC\StatBundle\Calculator;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\DiExtraBundle\Annotation as DI;
use KGC\UserBundle\Repository\UtilisateurRepository;
use KGC\UserBundle\Service\SalaryManager;
use KGC\StatBundle\Repository\StatRepository;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * Class StandardCalculator.
 *
 * @DI\Service("kgc.stat.calculator", abstract=true)
 */
abstract class Calculator implements CalculatorInterface
{
    const DAY_TIME_REFERENCE = '00:00';

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var ObjectManager
     */
    private $statRepository;

    /**
     * @var ObjectManager
     */
    private $utilisateurRepository;

    /**
     * @var SecurityContextInterface
     */
    private $securityContext;

    /*
     * @var SalaryManager
     */
    public $salaryManager;

    /**
     * @var array
     */
    protected $todayInterval;

    /**
     * @var array
     */
    protected $monthInterval;

    protected function getDayIntervalFromDate(\Datetime $date)
    {
        $first = new \Datetime($date->format('Y-m-d '.self::DAY_TIME_REFERENCE));
        $last = clone $first;
        $last = $last->add(new \DateInterval('P01D'));

        return [$first, $last];
    }

    protected function getMonthIntervalFromDate(\Datetime $date, $lastDay = false)
    {
        $return = array();
        $m = (int) $date->format('m');
        $y = $date->format('Y');

        $first = new \Datetime(date('Y-m-d '.self::DAY_TIME_REFERENCE, mktime(0, 0, 0, $m, 1, $y)));
        $return[] = $first;
        // $last is current day
        $last = clone $date;
        $last = $last->add(new \DateInterval('P01D'));
        $return[] = $last;
        if($lastDay){
            // Or $last is last day of the month
            $truelast = new \Datetime(date('Y-m-d '.self::DAY_TIME_REFERENCE, mktime(0, 0, 0, $m+1, 0, $y)));
            $return[] = $truelast;
        }

        return $return;
    }

    /**
     * @param \Datetime $date
     *
     * @return array
     */
    public static function getFullMonthIntervalFromDate(\Datetime $date)
    {
        $m = (int) $date->format('m');
        $y = $date->format('Y');

        $first = new \Datetime(date('Y-m-d '.self::DAY_TIME_REFERENCE, mktime(0, 0, 0, $m, 1, $y)));
        $last = new \Datetime(date('Y-m-d '.self::DAY_TIME_REFERENCE, mktime(0, 0, 0, $m + 1, 1, $y)));

        return [$first, $last];
    }

    /**
     * @param \Datetime $dateBegin
     * @param \Datetime $dateEnd
     *
     * @return array
     */
    public static function getFullMonthIntervalFromDates(\Datetime $dateBegin, \Datetime $dateEnd)
    {
        $last = clone $dateEnd;
        $last = $last->add(new \DateInterval('P01D'));

        return [$dateBegin, $last];
    }

    /**
     * @param \Datetime $refDate
     * @param $offset
     *
     * @return array
     */
    public static function getSlidingDateByOffset(\Datetime $refDate, $offset)
    {
        $m = (int) $refDate->format('m');
        $y = $refDate->format('Y');

        $firstDayOfMonth = new \Datetime(date('Y-m-d '.self::DAY_TIME_REFERENCE, mktime(0, 0, 0, $m + 1, 1, $y)));

        $first = clone $firstDayOfMonth;
        $first = $first->sub(new \DateInterval(sprintf('P%sM', $offset)));

        $last = $last = new \Datetime(date('Y-m-d '.self::DAY_TIME_REFERENCE, mktime(0, 0, 0, $m, 1, $y)));

        return [$first, $last];
    }
    
    /**
     * @param \Datetime $refDate
     * 
     * @return array
     */
    public static function getWeekInterval(\Datetime $refDate = null)
    {
        $refDate = isset($refDate) ? $refDate : new \DateTime('now 00:00');
        // On considère une semaine du lundi au dimanche
        $refDateDay = $refDate->format('w');
        $dayDiff = $refDateDay - 1;
        if($dayDiff == -1){
            $dayDiff = 6;
        }
        $begin = clone $refDate;
        if($dayDiff > 0){
            $diffInterval = new \DateInterval('P'.$dayDiff.'D');
            $begin->sub($diffInterval);
        }
        $end = clone $begin;
        $end->add(new \DateInterval('P6D'));
        
        return [ $begin, $end ];
    }
    
    /**
     * @param \Datetime $refDate
     * 
     * @return array|DateTime
     */
    public static function getSQLWeekInterval(\Datetime $refDate = null, \Datetime $end = null)
    {
        if(!isset($refDate)){
            $begin = new \DateTime('now 00:00');
        } else {
            $begin = $refDate;
        }
        if(!isset($end)){
            list($begin, $end) = self::getWeekInterval($refDate);
        }
        $endClone = clone $end;
        $endClone->add(new \DateInterval('P1D'));
        
        return [ $begin, $endClone ];
    }
    
    /**
     * @param \Datetime $refDate
     * 
     * @return array
     */
    protected function getWeekIntervalOfTheMonth(\Datetime $refDate)
    {
        $month = $refDate->format('m');
        $weeks = array();
        $firstSundayOfMonth = new \DateTime('first Sunday of'.$refDate->format('F'));
        $sunday = clone $firstSundayOfMonth;
        do {
            $weeks[] = self::getSQLWeekInterval($sunday);
            $sunday->add(new \DateInterval('P7D'));
        } while($sunday->format('m') == $month );
        
        return $weeks;
    }
    
    /**
     * @param \DateTime $begin
     * @param \DateTime $end
     * 
     * @return array
     */
    protected function getWeekIntervalfromDates(\DateTime $begin, \DateTime $end)
    {
        $weeks = array();
        $refDate = clone $begin;
        do {
            $weeks[] = self::getSQLWeekInterval($refDate);
            $refDate->add(new \DateInterval('P7D'));
        } while($refDate < $end);
        
        return $weeks;
    }

    /**
     * @return array
     */
    protected function getTodayDateInterval()
    {
        if (null === $this->todayInterval) {
            $this->todayInterval = [
                new \DateTime(self::DAY_TIME_REFERENCE),
                new \DateTime('tomorrow '.self::DAY_TIME_REFERENCE),
            ];
        }

        return $this->todayInterval;
    }

    /**
     * @return array
     */
    protected function getLastMonthDateInterval()
    {
        if (null === $this->monthInterval) {
            $this->monthInterval = [
                new \DateTime('first day of previous month '.self::DAY_TIME_REFERENCE),
                new \DateTime('first day of this month '.self::DAY_TIME_REFERENCE),
            ];
        }

        return $this->monthInterval;
    }

    /**
     * @return array
     */
    protected function getCurrentMonthDateInterval()
    {
        if (null === $this->monthInterval) {
            $this->monthInterval = [
                new \DateTime('first day of this month '.self::DAY_TIME_REFERENCE),
                new \DateTime('tomorrow '.self::DAY_TIME_REFERENCE),
            ];
        }

        return $this->monthInterval;
    }

    /**
     * @param EntityManagerInterface $em
     * @param ObjectRepository       $statRepository
     *
     * @DI\InjectParams({
     *      "em" = @DI\Inject("doctrine.orm.entity_manager"),
     *      "statRepository" = @DI\Inject("kgc.stat.repository"),
     *      "securityContext" = @DI\Inject("security.context"),
     *      "salaryManager" = @DI\Inject("kgc.salary.manager"),
     *      "utilisateurRepository" = @DI\Inject("kgc.utilisateur.repository")
     * })
     */
    public function __construct(EntityManagerInterface $em,
                                ObjectRepository $statRepository,
                                SecurityContextInterface $securityContext,
                                SalaryManager $salaryManager,
                                ObjectRepository $utilisateurRepository)
    {
        $this->em = $em;
        $this->statRepository = $statRepository;
        $this->securityContext = $securityContext;
        $this->salaryManager = $salaryManager;
        $this->utilisateurRepository = $utilisateurRepository;
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        return $this->em;
    }

    /**
     * @return StatRepository
     */
    protected function getStatRepository()
    {
        return $this->statRepository;
    }

    /**
     * @return UtilisateurRepository
     */
    protected function getUtilisateurRepository()
    {
        return $this->utilisateurRepository;
    }

    /**
     * @return mixed|void
     */
    public function getUser()
    {
        if (null === $token = $this->securityContext->getToken()) {
            return;
        }

        if (!is_object($user = $token->getUser())) {
            return;
        }

        return $user;
    }
}
