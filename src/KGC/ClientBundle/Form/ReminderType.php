<?php

namespace KGC\ClientBundle\Form;

use KGC\ClientBundle\Entity\Historique;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ReminderType extends AbstractType
{
    /**
     * @var array
     */
    protected $planning;

    /**
     * @var Historique
     */
    protected $defaultValue;

    protected $defaultDatetime;

    /**
     * @param array      $planning
     * @param Historique $defaultValue
     */
    public function __construct(array $planning, $defaultValue)
    {
        $this->planning = $planning;
        $this->defaultValue = $defaultValue;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $planningNew = [];
        foreach (array_keys($this->planning) as $h) {
            $planningNew[$h] = $h;
        }
        $builder
            ->add('day', 'date', [
                'label' => 'Date',
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'input-mask' => true,
                'date-picker' => true,
                'start-date' => date('d/m/Y', strtotime('tomorrow')),
                'attr' => [
                    'class' => 'js-reminder-day',
                    'data-daysofweekdisabled' => '6,0',
                ],
                'required' => false,
            ])
            ->add('hour', 'choice', [
                'attr' => ['class' => 'js-reminder-hour'],
                'label' => 'Heure',
                'choices' => $planningNew,
            ])
        ;

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            if ($this->defaultValue) {
                $datetime = $this->defaultValue->getDatetime();
                if ($datetime instanceof \DateTime) {
                    $form = $event->getForm();
                    $hour = date_format($datetime, 'H:i');
                    $form->get('day')->setData($datetime);
                    $form->get('hour')->setData($hour);
                }
            }

        });
    }

    public function getName()
    {
        return 'reminder';
    }
}
