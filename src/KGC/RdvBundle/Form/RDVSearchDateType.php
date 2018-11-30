<?php

namespace KGC\RdvBundle\Form;

use KGC\RdvBundle\Service\PlanningService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class RDVSearchDateType extends AbstractType
{
    /**
     * @var PlanningService
     */
    protected $planningService;

    /**
     * @param PlanningService $planningService
     */
    public function __construct(PlanningService $planningService)
    {
        $this->planningService = $planningService;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('intervalle', 'choice', array(
                'choices' => $this->planningService->buildSearchIntervalChoices(),
                'expanded' => true,
                'multiple' => false,
            ))
            ->add('date_begin', 'date', array(
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'date-picker' => true,
                'empty_data' => date('d/m/Y'),
                'limit-size' => true,
            ))
            ->add('date_end', 'date', array(
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'date-picker' => true,
                'empty_data' => date('d/m/Y'),
                'limit-size' => true,
            ))
        ;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_RdvBundle_search_date';
    }
}
