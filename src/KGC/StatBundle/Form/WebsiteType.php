<?php
// src/KGC/StatBundle/Form/WebsiteType.php

namespace KGC\StatBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class WebsiteType extends AbstractType
{
    const KEY_ALL = 'all';
    const KEY_NULL = 'NULL';

    const LABEL_ALL = 'Tous';
    const LABEL_NULL = 'Aucun';

    private $choices;

    public function __construct($websites){
        $this->choices = [];

        $this->choices[self::KEY_ALL] = self::LABEL_ALL;
        $this->choices[self::KEY_NULL] = self::LABEL_NULL;

        foreach ($websites as $website) {
            $this->choices[$website->getId()] = $website->getLibelle();
        }
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('websites', 'choice', array(
                'required' => false,
                'multiple' => true,
                'choices' => $this->choices,
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
        return 'kgc_StatBundle_admin_website';
    }
}
?>