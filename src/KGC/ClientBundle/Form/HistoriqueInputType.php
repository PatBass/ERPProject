<?php

namespace KGC\ClientBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;

class HistoriqueInputType extends HistoriqueType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('string', 'text', array(
                'label' => $this->label,
                'label_attr' => ['class' => 'histo-field-label'],
                'required' => false,
                'constraints' => isset($options['constraints']) ? $options['constraints'] : [],
            ))
        ;
    }
}
