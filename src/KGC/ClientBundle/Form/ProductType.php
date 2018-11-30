<?php
// src/KGC/ClientBundle/Form/ProductType.php

namespace KGC\ClientBundle\Form;

use KGC\ClientBundle\Entity\Option;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ProductType extends OptionType
{
    /**
     * @param bool|false $isEdit
     */
    public function __construct($isEdit = false)
    {
        parent::__construct($isEdit);
    }
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->get('type')->setData(Option::TYPE_PRODUCT);
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\ClientBundle\Entity\Option',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_clientbundle_product_option';
    }
}