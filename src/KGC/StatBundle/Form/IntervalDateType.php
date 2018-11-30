<?php

namespace KGC\StatBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class IntervalDateType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('date_begin', 'date', [
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'date-picker' => true,
                'empty_data' => date('d/m/Y'),
                'limit-size' => true,
                'data' => new \Datetime('first day of this month 00:00'),
                'attr' => array(
                    'class' => 'submit-onchange',
                ),
            ])
            ->add('date_end', 'date', [
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'date-picker' => true,
                'empty_data' => date('d/m/Y'),
                'limit-size' => true,
                'data' => new \Datetime('last day of this month 00:00'),
                'attr' => array(
                    'class' => 'submit-onchange',
                ),
            ])
        ;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_RdvBundle_stat_pastdate';
    }
}
