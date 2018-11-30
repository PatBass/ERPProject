<?php

// src/KGC/DashboardBundle/Form/PhoneType.php


namespace KGC\DashboardBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * PhoneType.
 *
 * Type de champ téléphone
 *
 * @category Form
 *
 * @author Laurene Dourdin <2aurene@gmail.com>
 */
class PhoneType extends AbstractType
{
    protected $btnCall = false;

    public function __construct($btnCall = false)
    {
        $this->btnCall = $btnCall;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        /*     $resolver->setDefaults(array(
            'choices' => array(
                'm' => 'Male',
                'f' => 'Female',
            )
        )); */
    }

    public function getParent()
    {
        return 'text';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'phone';
    }
}
