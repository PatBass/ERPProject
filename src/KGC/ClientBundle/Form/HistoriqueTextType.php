<?php

namespace KGC\ClientBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;

class HistoriqueTextType extends HistoriqueType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('text', 'textarea', array(
                'label' => $this->label,
                'required' => false,
                'label_attr' => ['class' => 'histo-field-label'],
                'constraints' => isset($options['constraints']) ? $options['constraints'] : [],
            ))
        ;
    }
}
