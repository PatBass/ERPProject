<?php
// src/KGC/StatBundle/Form/StatController/SourceType.php

namespace KGC\StatBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SourceType extends AbstractType
{
    const KEY_ALL = 'all';
    const KEY_NULL = 'NULL';

    const LABEL_ALL = 'Tous';
    const LABEL_NULL = 'Aucune';

    private $choices;

    /**
     * SourceType constructor.
     * @param $sources
     */
    public function __construct($sources){
        $this->choices = [];

        $this->choices[self::KEY_ALL] = self::LABEL_ALL;
        $this->choices[self::KEY_NULL] = self::LABEL_NULL;

        foreach ($sources as $source) {
            $this->choices[$source->getId()] = $source->getLabel() . " (" . $source->getCode() . ")";
        }
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
             ->add('sources', 'choice', array(
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
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'mapped' => false
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_StatBundle_admin_source';
    }
}
?>