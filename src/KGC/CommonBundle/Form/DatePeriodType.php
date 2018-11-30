<?php

// src/KGC/RdvBundle/Form/DatePeriodType.php


namespace KGC\CommonBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Constructeur de formulaire pour entité Carte bancaire.
 *
 * @category Form
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class DatePeriodType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('begin', 'date', [
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'date-picker' => true,
                'input-mask' => true,
                'attr' => ['placeholder' => 'Début'],
                'required' => isset($options['required']) ? $options['required'] : false,
                'data' => isset($options['data']['begin']) ? $options['data']['begin'] : null,
            ])
            ->add('end', 'date', [
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'date-picker' => true,
                'input-mask' => true,
                'attr' => ['placeholder' => 'Fin'],
                'required' => isset($options['required']) ? $options['required'] : false,
                'data' => isset($options['data']['end']) ? $options['data']['end'] : null,
            ])
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'mapped' => false,
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_datePeriod';
    }
}
