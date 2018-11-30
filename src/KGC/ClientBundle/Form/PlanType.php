<?php

// src/KGC/ClientBundle/Form/PlanType.php


namespace KGC\ClientBundle\Form;

use KGC\ClientBundle\Entity\Option;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PlanType extends OptionType
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
        $builder->get('type')->setData(Option::TYPE_PLAN);
        $this->changeOptions($builder, 'dataAttr', [
            'required' => true,
            'constraints' => new NotBlank(),
        ]);
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
