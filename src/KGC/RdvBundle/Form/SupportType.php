<?php

// src/KGC/RdvBundle/Form/SupportType.php


namespace KGC\RdvBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class SupportType.
 * 
 * @category Form
 */
class SupportType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('libelle', 'text', array(
                'label' => 'Libellé',
                'required' => true,
            ))
            ->add('idtracker', 'text', array(
                'label' => 'Tracker',
                'required' => false,
            ))
            ->add('enabled', 'checkbox', [
                'required' => false,
                'switch_style' => 5,
            ])
            ->add('profils', 'entity', array(
                'class' => 'KGCUserBundle:Profil',
                'property' => 'name',
                'multiple' => true,
                'expanded' => true,
            ))
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\RdvBundle\Entity\Support',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_RdvBundle_support';
    }
}
