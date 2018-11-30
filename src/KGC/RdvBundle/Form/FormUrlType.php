<?php
// src/KGC/RdvBundle/Form/FormUrlType.php

namespace KGC\RdvBundle\Form;

use KGC\Bundle\SharedBundle\Repository\WebsiteRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class CodePromoType.
 */
class FormUrlType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('label', 'text', array(
                'label' => 'Description',
                'required' => true,
            ))
            ->add('website', 'entity', array(
                'class' => 'KGCSharedBundle:Website',
                'property' => 'libelle',
                'empty_value' => '',
                'query_builder' => function (WebsiteRepository $web_rep) {
                    return $web_rep->findIsChatQB(false);
                },
            ))
            ->add('source', 'entity', array(
                'class' => 'KGCRdvBundle:Source',
                'property' => 'label',
                'empty_value' => '',
                'required' => false
            ))
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\RdvBundle\Entity\FormUrl',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_RdvBundle_formurl';
    }
}
