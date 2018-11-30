<?php
// src/KGC/StatBundle/Form/StatController/SortingColumnType.php

namespace KGC\StatBundle\Form;

use KGC\StatBundle\Entity\StatisticRenderingRule;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SortingColumnType extends AbstractType
{

    const KEY_DESC = "DESC";
    const KEY_ASC = "ASC";

    const LABEL_DESC = "Descendant";
    const LABEL_ASC = "Ascendant";

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'mapped' => false,
        ));
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('column', new StatisticColumnType(), [
                'data' => [
                    'separated' => false,
                    'selected' => isset($options['data']['sorting_column']) ? $options['data']['sorting_column'] : StatisticRenderingRule::CODE_RDV_TOTAL
                ],
            ])
            ->add('dir', ChoiceType::class, array(
                'label' => 'Direction',
                'choices' => array(
                    self::LABEL_DESC => self::KEY_DESC,
                    self::LABEL_ASC => self::KEY_ASC
                ),
                'data' => isset($options['data']['sorting_dir']) ? $options['data']['sorting_dir'] : self::KEY_DESC,
                'multiple' => false,
                'expanded' => true,
                'required' => true,
                'choices_as_values' => true
            ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_StatBundle_admin_sorting';
    }
}

?>