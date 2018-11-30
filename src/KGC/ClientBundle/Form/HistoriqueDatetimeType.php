<?php

namespace KGC\ClientBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;

class HistoriqueDatetimeType extends HistoriqueType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('datetime', 'datetime',  [
                'required' => false,
                'attr' => ['class' => 'js-original-date'],
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy HH:mm',
                'constraints' => isset($options['constraints']) ? $options['constraints'] : [],
            ])
        ;
    }
}
