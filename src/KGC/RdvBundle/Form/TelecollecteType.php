<?php
// src/KGC/RdvBundle/Form/TelecollecteType.php

namespace KGC\RdvBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use KGC\RdvBundle\Repository\TPERepository;

/**
 * Constructeur de formulaire pour entité Telecollecte.
 *
 * @category Form
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class TelecollecteType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('date', 'date', [
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'date-picker' => true,
                'input-mask' => true,
                'required' => true
            ])
            ->add('tpe', 'entity', [
                'class' => 'KGCRdvBundle:TPE',
                'query_builder' => function(TPERepository $r){
                    return $r->getTelecollecteTPEQB();
                },
                'property' => 'libelle',
                'required' => true,
                'input_addon' => 'credit-card',
            ])     
            ->add('amountOne', 'money', [
                'required' => true,
                'attr' => ['class' => 'trigger_amount_calc add_amount']
            ])
            ->add('amountTwo', 'money', [
                'attr' => ['class' => 'trigger_amount_calc add_amount']
            ])
            ->add('amountThree', 'money', [
                'attr' => ['class' => 'trigger_amount_calc add_amount']
            ])
            ->add('total', 'money', [
                'required' => true,
                'read_only' => true,
                'attr' => ['class' => 'auto_amount_result']
            ])
            ->add('fermeture', 'checkbox', [
                'mapped' => false,
                'data' => true,
                'required' => false,
                'checked_label_style' => false,
            ])
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\RdvBundle\Entity\Telecollecte',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_rdvbundle_telecollecte';
    }
}
