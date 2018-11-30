<?php

namespace KGC\RdvBundle\Service;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;
use KGC\ChatBundle\Entity\ChatRoom;
use KGC\ClientBundle\Form\ReminderType;
use KGC\UserBundle\Entity\Utilisateur;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @DI\Service("kgc.rdv.planning.service")
 */
class PlanningService
{
    const NB_PLAGES = 10;

    const SIMPLE_DATE_FORMAT = 'd/m/Y';

    const PLANNING_INTERVALLE = 'PT1H30M';

    const INTERVALLE = 'PT30M'; // 30 minutes
    const INTERVALLE_DATE_FORMAT = 'H:i';
    const INTERVALLE_FULL_DATE_FORMAT = 'd/m/Y H:i';

    const HOUR_MORNING_BEGIN = '08:00';
    const HOUR_MORNING_END = '16:00';
    const HOUR_AFTERNOON_BEGIN = '16:00';
    const HOUR_AFTERNOON_END = '00:00';

    const MIDDAY_HOUR = 16;

    const INTERVALLE_TOMORROW = 'tomorrow';
    const INTERVALLE_TODAY = 'today';
    const INTERVALLE_YESTERDAY = 'yesterday';
    const INTERVALLE_WEEK = 'week';
    const INTERVALLE_MONTH = 'month';
    const INTERVALLE_LAST_MONTH = 'last_month';

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var FormFactory
     */
    protected $formFactory;

    /**
     * @var \Doctrine\ORM\EntityRepository
     */
    protected $rdvRepository;

    /**
     * @param null  $data
     * @param array $options
     *
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    protected function createFormBuilder($data = null, array $options = array())
    {
        return $this->formFactory->createBuilder('form', $data, $options);
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getRdvRepository()
    {
        if (null === $this->rdvRepository) {
            $this->rdvRepository = $this->entityManager->getRepository('KGCRdvBundle:RDV');
        }

        return $this->rdvRepository;
    }

    /**
     * @param ObjectManager        $entityManager
     * @param FormFactoryInterface $formFactory
     *
     * @DI\InjectParams({
     *     "entityManager" = @DI\Inject("doctrine.orm.entity_manager"),
     *     "formFactory" = @DI\Inject("form.factory"),
     * })
     */
    public function __construct(ObjectManager $entityManager, FormFactoryInterface $formFactory)
    {
        $this->entityManager = $entityManager;
        $this->formFactory = $formFactory;
    }

    /**
     * Crée le planning de selection ou le planning d'affichage.
     *
     * @param \Datetime $debut
     * @param \Datetime $fin
     * @param string    $type
     *
     * @return array
     */
    protected function constructPlanning(\Datetime $debut, \Datetime $fin, $type = 'select')
    {
        $interval = new \DateInterval(self::INTERVALLE);
        $daterange = new \DatePeriod($debut, $interval, $fin);
        $planning = array();
        foreach ($daterange as $date) {
            for ($i = 1;$i <= self::NB_PLAGES;++$i) {
                if ('select' === $type) {
                    $planning[$date->format(self::INTERVALLE_DATE_FORMAT)] = array(
                        'value' => $date->format(self::INTERVALLE_FULL_DATE_FORMAT),
                        'nb' => 0,
                    );
                } else {
                    $planning[$date->format(self::INTERVALLE_DATE_FORMAT)][$i] = null;
                }
            }
        }

        return $planning;
    }

    /**
     * Distribue les consultations dans le planning.
     *
     * @param array $planning
     * @param $results
     *
     * @return array
     */
    protected function bindResults(array $planning, $results)
    {
        foreach ($results as $rdv) {
            $flag = false;
            $i = 1;
            while ($i <= self::NB_PLAGES and !$flag) {
                $horaire = $rdv->getHeureConsultation();
                if (array_key_exists($horaire, $planning) && !isset($planning[$horaire][$i]) && count($rdv->getCartebancaires()) > 0) {
                    $planning[$horaire][$i] = $rdv;
                    $flag = true;
                }
                ++$i;
            }
        }

        return $planning;
    }

    /**
     * Distribue les consultations dans le planning.
     *
     * @param array $planning
     * @param $results
     *
     * @return array
     */
    protected function bindTchatResults(array $planning, $results)
    {
        foreach ($results as $array) {
            $chatRom = $array['room'];
            $flag = false;
            $i = 1;
            while ($i <= count($results) and !$flag) {
                if($chatRom->getStartDate()->format('i')>=30){
                    $horaire = $chatRom->getStartDate()->format('H:30');
                }else{
                    $horaire = $chatRom->getStartDate()->format('H:00');
                }
                if (array_key_exists($horaire, $planning) && !isset($planning[$horaire][$i])) {
                    $planning[$horaire][$i] = $array;
                    $flag = true;
                }
                ++$i;
            }
        }

        return $planning;
    }

    /**
     * Distribue les consultations dans le planning de sélection de plage horaire.
     *
     * @param array $planning
     * @param $results
     *
     * @return array
     */
    protected function bindResultsforSelect(array $planning, $results)
    {
        foreach ($results as $rdv) {
            $horaire = $rdv->getHeureConsultation();
            ++$planning[$horaire]['nb'];
        }

        return $planning;
    }

    /**
     * @param array $planning
     * @param $removeEmptySlots
     *
     * @return array
     */
    protected function removeEmptySlotIfNeeded(array $planning, $removeEmptySlots)
    {
        if ($removeEmptySlots) {
            $newPlanning = array();
            foreach ($planning as $i => $hours) {
                foreach ($hours as $k => $intervalle) {
                    if (!empty($intervalle)) {
                        if (!array_key_exists($i, $newPlanning)) {
                            $newPlanning[$i] = array();
                        }
                        $newPlanning[$i][$k] = $intervalle;
                    }
                }
            }

            return $newPlanning;
        }

        return $planning;
    }

    /**
     * @param $hour
     *
     * @return array
     */
    protected function getDebutFinSimple($hour)
    {
        return [
            \Datetime::createFromFormat(self::INTERVALLE_DATE_FORMAT, $hour),
            \Datetime::createFromFormat(self::INTERVALLE_DATE_FORMAT, $hour)->add(new \DateInterval(self::PLANNING_INTERVALLE)),
        ];
    }

    /**
     * @param $now
     * @param $tomo
     *
     * @return array
     */
    protected function getDebutFinFull($now, $tomo)
    {
        return [
            \Datetime::createFromFormat(self::INTERVALLE_FULL_DATE_FORMAT, $now.' '.self::HOUR_MORNING_BEGIN),
            \Datetime::createFromFormat(self::INTERVALLE_FULL_DATE_FORMAT, $tomo.' '.self::HOUR_AFTERNOON_END),
        ];
    }

    /**
     * @param $debut
     * @param $fin
     *
     * @return array
     */
    protected function getDebutFinSelect($debut, $fin)
    {
        return [
            \Datetime::createFromFormat(self::INTERVALLE_FULL_DATE_FORMAT, $debut),
            \Datetime::createFromFormat(self::INTERVALLE_FULL_DATE_FORMAT, $fin),
        ];
    }

    public function buildSimplePlanning(Utilisateur $user = null, $removeEmptySlots = false, \Datetime $end = null)
    {
        $now = new \DateTime();
        $heu = $now->format('H');
        $min = $now->format('i') >= 30 ? '30' : '00';

        list($debut, $fin) = $this->getDebutFinSimple($heu.':'.$min);
        $fin = $end ?: $fin;
        $planning = $this->constructPlanning($debut, $fin, 'default');

        $liste = $this->getRdvRepository()->getPlannedBetween($debut, $fin, $user);
        $planning = $this->bindResults($planning, $liste);

        $depasse = $this->getRdvRepository()->getBefore($debut, $user);
        $planning = $this->removeEmptySlotIfNeeded($planning, $removeEmptySlots);

        $alerte = array();
        foreach ($depasse as $rdv) {
            $horaire = $rdv->getDateConsultation()->format(self::INTERVALLE_DATE_FORMAT);
            if (count($rdv->getCartebancaires()) > 0) {
                $alerte[$horaire][] = $rdv;
            }
        }

        return [
            'planning' => $planning,
            'alerte' => $alerte,
            'liste' => $liste,
        ];
    }

    public function buildFullPlanning(\DateTime $date = null)
    {
        $now = isset($date) ? $date : new \DateTime();
        $str_now = date_format($now, self::SIMPLE_DATE_FORMAT);
        $tomo = clone $date;
        $tomo->add(new \DateInterval('P1D'));
        $str_tomo = date_format($tomo, self::SIMPLE_DATE_FORMAT);

        list($debut, $fin) = $this->getDebutFinFull($str_now, $str_tomo);
        $planning = $this->constructPlanning($debut, $fin, 'default');

        $liste = $this->getRdvRepository()->getPlannedBetween($debut, $fin);
        $planning = $this->bindResults($planning, $liste);
        $planning_index = array();
        $i = 1;

        foreach ($planning as $horaire => $tranche) {
            $planning_index[$i][$horaire] = $tranche;
            ++$i;
        }

        return [
            'planning' => $planning_index,
            'liste' => $liste,
        ];
    }

    public function buildFullTchatPlanning(\DateTime $date = null)
    {
        $now = isset($date) ? $date : new \DateTime();
        $str_now = date_format($now, self::SIMPLE_DATE_FORMAT);
        $tomo = clone $date;
        $tomo->add(new \DateInterval('P1D'));
        $str_tomo = date_format($tomo, self::SIMPLE_DATE_FORMAT);

        list($debut, $fin) = $this->getDebutFinFull($str_now, $str_tomo);
        $planning = $this->constructPlanning($debut, $fin, 'default');
        $liste = $this->entityManager->getRepository('KGCChatBundle:ChatRoom')->findAllByPlanning($debut, $fin);
        $planning = $this->bindTchatResults($planning, $liste);
        $planning_index = array();
        $i = 1;

        foreach ($planning as $horaire => $tranche) {
            $planning_index[$i][$horaire] = $tranche;
            ++$i;
        }

        return [
            'planning' => $planning_index,
        ];
    }

    public function buildSelectReminderPlanning($defaultValue)
    {
        $jourd = new \DateTime();
        $jourf = clone $jourd;
        $jourf->add(new \DateInterval('P1D'));

        $hd = self::HOUR_MORNING_BEGIN;
        $hf = self::HOUR_AFTERNOON_END;

        $jourd = date_format($jourd, self::SIMPLE_DATE_FORMAT);
        $jourf = date_format($jourf, self::SIMPLE_DATE_FORMAT);

        list($debut, $fin) = $this->getDebutFinSelect($jourd.' '.$hd,  $jourf.' '.$hf);
        $planning = $this->constructPlanning($debut, $fin);

        $form = $this->formFactory->create(
            new ReminderType($planning, $defaultValue)
        );

        return [
            'planning' => $planning,
            'form' => $form->createView(),
        ];
    }

    public function buildSelectPlanning(Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('jour', 'date', array(
                'widget' => 'single_text', 'format' => 'dd/MM/yyyy',
                'input-mask' => true, 'date-picker' => true, 'start-date' => 'today',
                'empty_data' => date(self::SIMPLE_DATE_FORMAT),
            ))
            ->add('periode', 'choice', array(
                'attr' => array('class' => 'form-control'),
                'choices' => array('journee' => 'Journée', 'soiree' => 'Soirée'),
            ))
            ->getForm();

        $form->bind($request);
        if ($form->isValid()) {
            $jourd = $form['jour']->getData();
            $periode = $form['periode']->getData();
        } else {
            $jourd = new \DateTime();
            $periode = date('H') < self::MIDDAY_HOUR ? 'journee' : 'soiree';
        }

        $jourf = clone $jourd;
        if ($periode == 'journee') {
            $hd = self::HOUR_MORNING_BEGIN;
            $hf = self::HOUR_MORNING_END;
        } else {
            $hd = self::HOUR_AFTERNOON_BEGIN;
            $hf = self::HOUR_AFTERNOON_END;
            $jourf->add(new \DateInterval('P1D'));
        }

        $jourd = date_format($jourd, self::SIMPLE_DATE_FORMAT);
        $jourf = date_format($jourf, self::SIMPLE_DATE_FORMAT);

        list($debut, $fin) = $this->getDebutFinSelect($jourd.' '.$hd,  $jourf.' '.$hf);
        $planning = $this->constructPlanning($debut, $fin);
        $liste = $this->getRdvRepository()->getPlannedBetween($debut, $fin, null, true);
        $planning = $this->bindResultsforSelect($planning, $liste);

        return [
            'planning' => $planning,
            'liste' => $liste,
            'form' => $form->createView(),
        ];
    }

    public function buildSearchIntervalChoices()
    {
        $tomorrow = $this->getIntervalleByType(self::INTERVALLE_TOMORROW);
        $today = $this->getIntervalleByType(self::INTERVALLE_TODAY);
        $yesterday = $this->getIntervalleByType(self::INTERVALLE_YESTERDAY);
        $week = $this->getIntervalleByType(self::INTERVALLE_WEEK);
        $month = new \DateTime('0:00 first day of this month');
        $month = date_format($month, 'm/Y');
        $lastMonth = new \DateTime('0:00 first day of previous month');
        $lastMonth = date_format($lastMonth, 'm/Y');
        $choices = [
            self::INTERVALLE_TOMORROW => sprintf('Demain (%s)', $tomorrow['begin']),
            self::INTERVALLE_TODAY => sprintf("Aujourd'hui (%s)", $today['begin']),
            self::INTERVALLE_YESTERDAY => sprintf('Hier (%s)', $yesterday['begin']),
            self::INTERVALLE_WEEK => sprintf('7 derniers jours (%s - %s)', $week['begin'], $week['end']),
            self::INTERVALLE_MONTH => sprintf('Mois en cours (%s)', $month),
            self::INTERVALLE_LAST_MONTH => sprintf('Mois dernier (%s)', $lastMonth),
        ];

        return $choices;
    }

    /**
     * @param $type
     *
     * @return array
     */
    public function getIntervalleByType($type)
    {
        $result = [];
        $originDate = new \DateTime();
        switch ($type) {
            case self::INTERVALLE_TOMORROW:
                $formattedBegin = date_format(new \DateTime('tomorrow'), self::SIMPLE_DATE_FORMAT);
                $result = [
                    'begin' => $formattedBegin,
                    'end' => $formattedBegin,
                ];
                break;
            case self::INTERVALLE_TODAY:
                $formattedBegin = date_format($originDate, self::SIMPLE_DATE_FORMAT);
                $result = [
                    'begin' => $formattedBegin,
                    'end' => $formattedBegin,
                ];
                break;
            case self::INTERVALLE_YESTERDAY:
                $originDate->sub(new \DateInterval('P1D'));
                $formattedBegin = date_format($originDate, self::SIMPLE_DATE_FORMAT);
                $result = [
                    'begin' => $formattedBegin,
                    'end' => $formattedBegin,
                ];
                break;
            case self::INTERVALLE_WEEK:
                $end = clone $originDate;
                $originDate->sub(new \DateInterval('P7D'));
                $result = [
                    'begin' => date_format($originDate, self::SIMPLE_DATE_FORMAT),
                    'end' => date_format($end, self::SIMPLE_DATE_FORMAT),
                ];
                break;
            case self::INTERVALLE_MONTH:
                $begin = new \DateTime('0:00 first day of this month');
                $end = new \DateTime('0:00 last day of this month');
                $result = [
                    'begin' => date_format($begin, self::SIMPLE_DATE_FORMAT),
                    'end' => date_format($end, self::SIMPLE_DATE_FORMAT),
                ];
                break;
            case self::INTERVALLE_LAST_MONTH:
                $begin = new \DateTime('0:00 first day of previous month');
                $end = new \DateTime('0:00 last day of previous month');
                $result = [
                    'begin' => date_format($begin, self::SIMPLE_DATE_FORMAT),
                    'end' => date_format($end, self::SIMPLE_DATE_FORMAT),
                ];
                break;
            default:
                throw new \InvalidArgumentException(
                    sprintf('Type argument "%s" does not exist', $type)
                );
        }

        return $result;
    }

    /**
     * Make an array indexed by day, hour of a datetime criteria with given list of elements.
     *
     * @param type     $list
     * @param callback $criteria return the Datetime criteria
     */
    public function sortByDayHour($list, $criteria)
    {
        $orderedList = array();
        foreach ($list as $e) {
            $datetime = $e->$criteria();
            $day = date_format($datetime, 'd/m/Y');
            $hour = date_format($datetime, 'H:i');
            $orderedList[$day][$hour][] = $e;
        }
        foreach ($orderedList as $dayKey => $byDayList) {
            ksort($orderedList[$dayKey]);
        }
        ksort($orderedList);

        return $orderedList;
    }
}
