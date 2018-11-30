<?php
// src/KGC/StatBundle/Form/StatisticRenderingRuleType.php

namespace KGC\StatBundle\Form;

use KGC\StatBundle\Entity\StatisticRenderingRule;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class StatisticRenderingRuleType extends AbstractType
{
    const COLOR_1 = "#ac725e";
    const COLOR_2 = "#d06b64";
    const COLOR_3 = "#f83a22";
    const COLOR_4 = "#fa573c";
    const COLOR_5 = "#ff7537";
    const COLOR_6 = "#ffad46";
    const COLOR_7 = "#42d692";
    const COLOR_8 = "#16a765";
    const COLOR_9 = "#7bd148";
    const COLOR_10 = "#b3dc6c";
    const COLOR_11= "#fbe983";
    const COLOR_12 = "#fad165";
    const COLOR_13 = "#92e1c0";
    const COLOR_14 = "#9fe1e7";
    const COLOR_15 = "#9fc6e7";
    const COLOR_16 = "#4986e7";
    const COLOR_17 = "#9a9cff";
    const COLOR_18 = "#b99aff";
    const COLOR_19 = "#c2c2c2";
    const COLOR_20 = "#cabdbf";
    const COLOR_21 = "#cca6ac";
    const COLOR_22 = "#f691b2";
    const COLOR_23 = "#cd74e6";
    const COLOR_24 = "#a47ae2";
    const COLOR_25 = "#555";

    private $color_choices = [
        self::COLOR_1 => self::COLOR_1,
        self::COLOR_2 => self::COLOR_2,
        self::COLOR_3 => self::COLOR_3,
        self::COLOR_4 => self::COLOR_4,
        self::COLOR_5 => self::COLOR_5,
        self::COLOR_6 => self::COLOR_6,
        self::COLOR_7 => self::COLOR_7,
        self::COLOR_8 => self::COLOR_8,
        self::COLOR_9 => self::COLOR_9,
        self::COLOR_10 => self::COLOR_10,
        self::COLOR_11 => self::COLOR_11,
        self::COLOR_12 => self::COLOR_12,
        self::COLOR_13 => self::COLOR_13,
        self::COLOR_14 => self::COLOR_14,
        self::COLOR_15 => self::COLOR_15,
        self::COLOR_16 => self::COLOR_16,
        self::COLOR_17 => self::COLOR_17,
        self::COLOR_18 => self::COLOR_18,
        self::COLOR_19 => self::COLOR_19,
        self::COLOR_20 => self::COLOR_20,
        self::COLOR_21 => self::COLOR_21,
        self::COLOR_22 => self::COLOR_22,
        self::COLOR_23 => self::COLOR_23,
        self::COLOR_24 => self::COLOR_24,
        self::COLOR_25 => self::COLOR_25
    ];

    private $operator_choices = [
        StatisticRenderingRule::OPERATOR_EQ => StatisticRenderingRule::OPERATOR_EQ,
        StatisticRenderingRule::OPERATOR_GT => StatisticRenderingRule::OPERATOR_GT,
        StatisticRenderingRule::OPERATOR_GT_EQ => StatisticRenderingRule::OPERATOR_GT_EQ,
        StatisticRenderingRule::OPERATOR_LS => StatisticRenderingRule::OPERATOR_LS,
        StatisticRenderingRule::OPERATOR_LS_EQ => StatisticRenderingRule::OPERATOR_LS_EQ,
    ];

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('columnCode', ChoiceType::class, array(
                'label' => 'Colonne',
                'choices' => array_merge(StatisticColumnType::$RDV_CHOICES, StatisticColumnType::$CA_CHOICES),
                'required' => true,
                'choices_as_values' => true,
                'attr' => array(
                    'class' => 'chosen-select tag-input-style'
                )
            ))
            ->add('operator', ChoiceType::class, array(
                'choices' => $this->operator_choices,
                'required' => true,
                'choices_as_values' => true,
                'attr' => array(
                    'class' => 'chosen-select tag-input-style'
                )))
            ->add('value', 'text', array(
                'label' => 'Operateur',
                'required' => true,
            ))
            ->add('color', ChoiceType::class, array(
                'choices' => $this->color_choices,
                'required' => true,
                'attr' => array(
                    'class' => 'colorpicker'
                )))
            ->add('isRatio', 'checkbox', [
                'required' => false,
                'switch_style' => 5,
            ])
            ->add('enabled', 'checkbox', [
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
            'data_class' => 'KGC\StatBundle\Entity\StatisticRenderingRule',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_StatBundle_admin_statistic_rendering_rule';
    }
}

?>