<?php

// src/KGC/RdvBundle/Form/CodePromoType.php


namespace KGC\RdvBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class CodePromoType.
 */
class CodePromoType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('code', 'text', array(
                'label' => 'Code',
                'required' => true,
            ))
            ->add('desc', 'text', array(
                'label' => 'Description',
                'required' => true,
            ))
            ->add('website', 'entity', array(
                'class' => 'KGCSharedBundle:Website',
                'property' => 'libelle',
                'empty_value' => '',
            ))
            ->add('enabled', 'checkbox', [
                'required' => false,
                'switch_style' => 5,
            ])
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\RdvBundle\Entity\CodePromo',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_RdvBundle_codepromo';
    }
}
