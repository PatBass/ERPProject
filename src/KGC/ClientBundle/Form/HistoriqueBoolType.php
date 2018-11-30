<?php

// src/KGC/ClientBundle/Form/HistoriqueBoolType.php


namespace KGC\ClientBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;

class HistoriqueBoolType extends HistoriqueType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('bool', 'checkbox', array(
                'switch_style' => 5,
                'required' => false,
                'constraints' => isset($options['constraints']) ? $options['constraints'] : [],
            ))
        ;
    }
}
