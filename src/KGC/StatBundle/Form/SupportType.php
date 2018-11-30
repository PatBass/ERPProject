<?php
// src/KGC/StatBundle/Form/SupportType.php

namespace KGC\StatBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SupportType extends AbstractType
{
    const KEY_ALL = 'all';
    const KEY_NULL = 'NULL';

    const LABEL_ALL = 'Tous';
    const LABEL_NULL = 'Aucun';

    private $choices;

    public function __construct($supports){
        $this->choices = [];

        $this->choices[self::KEY_ALL] = self::LABEL_ALL;
        $this->choices[self::KEY_NULL] = self::LABEL_NULL;

        foreach ($supports as $support) {
            $this->choices[$support->getId()] = $support->getLibelle();
        }
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('supports', 'choice', array(
                'choices' => $this->choices,
                'required' => false,
                'data' => isset($options['data']) ? $options['data']: array(),
                'multiple' => true,
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
        return 'kgc_StatBundle_admin_support';
    }
}
?>