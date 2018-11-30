<?php

// src/KGC/DashboardBundle/Form/TpeType.php


namespace KGC\DashboardBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class Tpe Type.
 *
 * @category Form
 */
class TpeType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('libelle', 'text', array(
                'required' => true,
            ))
            ->add('enabled', 'checkbox', [
                'required' => false,
                'switch_style' => 5,
            ])
            ->add('hasTelecollecte', 'checkbox', [
                'required' => false,
                'switch_style' => 5,
            ]);
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\RdvBundle\Entity\TPE',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'tpe';
    }
}
