<?php
// src/KGC/StatBundle/Form/StatController/CodePromoType.php

namespace KGC\StatBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CodePromoType extends AbstractType
{
    const KEY_ALL = 'all';
    const KEY_NULL = 'NULL';

    const LABEL_ALL = 'Tous';
    const LABEL_NULL = 'Aucun';

    private $choices;

    public function __construct($codesPromo){
        $this->choices = [];

        $this->choices[self::KEY_ALL] = self::LABEL_ALL;
        $this->choices[self::KEY_NULL] = self::LABEL_NULL;

        foreach ($codesPromo as $codePromo) {
            $this->choices[$codePromo->getId()] = $codePromo->getCode();
        }
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('codespromo', 'choice', array(
                'choices' => $this->choices,
                'required' => false,
                'multiple' => true,
                'data' => isset($options['data']) ? $options['data']: array(),
                'attr' => array(
                    'data-placeholder' => "Indifférent",
                    'class' => 'chosen-select tag-input-style'
                )
            ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_StatBundle_admin_codepromo';
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