<?php
// src/KGC/StatBundle/Form/StatScopeType.php

namespace KGC\StatBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class StatScopeType extends AbstractType
{
    const KEY_ALL = 'all';
    const KEY_CONSULTATION = 'consult';
    const KEY_FOLLOW = 'follow';

    const LABEL_ALL = 'Tous';
    const LABEL_CONSULTATION = 'Consultations';
    const LABEL_FOLLOW = 'Suivis';

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('statScope', 'choice', [
                'label' => 'Scope',
                'choices' => [
                    self::KEY_ALL => self::LABEL_ALL,
                    self::KEY_CONSULTATION => self::LABEL_CONSULTATION,
                    self::KEY_FOLLOW => self::LABEL_FOLLOW,
                ],
                'required' => true,
                'data' => isset($options['data']['selected']) ? $options['data']['selected'] : self::KEY_ALL,
                'attr' => array(
                    'class' => 'chosen-select tag-input-style'
                )
            ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_StatBundle_admin_statscope';
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'mapped' => false
        ));
    }
}
?>