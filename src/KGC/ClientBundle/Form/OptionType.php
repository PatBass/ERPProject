<?php

// src/KGC/ClientBundle/Form/OptionType.php


namespace KGC\ClientBundle\Form;

use KGC\CommonBundle\Form\CommonAbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class OptionType extends CommonAbstractType
{
    /**
     * @var bool
     */
    protected $isEdit;

    /**
     * @param bool|false $isEdit
     */
    public function __construct($isEdit = false)
    {
        $this->isEdit = $isEdit;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('code', 'text', array(
                'label' => 'Code',
                'required' => true,
            ))
            ->add('type', 'text', array(
                'read_only' => true,
                'required' => true,
            ))
            ->add('label', 'text', array(
                'label' => 'Libellé',
                'required' => true,
            ))
            ->add('enabled', 'checkbox', [
                'required' => false,
                'switch_style' => 5,
            ])
            ->add('dataAttr', 'text', array(
                'label' => 'Donnée',
                'required' => false,
            ))
        ;

        if ($this->isEdit) {
            $this->changeOptions($builder, 'code', ['read_only' => true]);
        }
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
        return 'kgc_clientbundle_option';
    }
}
