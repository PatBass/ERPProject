<?php

// src/KGC/ClientBundle/Form/HistoriqueDrawType


namespace KGC\ClientBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;

class HistoriqueDrawType extends HistoriqueType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('draw', 'collection', [
            'type' => new DrawType(),
            'attr' => array('class' => 'pendulum-histo'),
            'allow_add' => true,
            'by_reference' => false,
            'label' => false,
        ]);
    }
}
