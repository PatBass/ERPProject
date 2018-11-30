<?php

// src/KGC/UserBundle/Form/SalaryParameterType.php


namespace KGC\UserBundle\Form;

use KGC\UserBundle\Entity\Journal;
use KGC\UserBundle\Entity\SalaryParameter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 *
 * @category Form
 *
 */
class SalaryParameterType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', 'choice', array(
                'choices' => array(SalaryParameter::TYPE_BONUS => "Bonus", SalaryParameter::TYPE_CONSULTATION =>  "Consultation", SalaryParameter::TYPE_FOLLOW => "Suivi")
            ))
            ->add('nature', 'choice', array(
                'choices' => array(SalaryParameter::NATURE_AE => "AE", SalaryParameter::NATURE_EMPLOYEE => "Salarié")
            ))
            ->add('caMin', 'number')
            ->add('caMax', 'number')
            ->add('valueMin', 'number')
            ->add('valueMax', 'number')
            ->add('percentage', 'number')
        ;

    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\UserBundle\Entity\SalaryParameter',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_userbundle_salary_parameter';
    }
}
