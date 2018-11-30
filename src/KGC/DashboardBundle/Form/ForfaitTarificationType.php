<?php

// src/KGC/DashboardBundle/Form/ForfaitTarificationType.php


namespace KGC\DashboardBundle\Form;

use Doctrine\ORM\EntityRepository;
use KGC\ClientBundle\Entity\Option;
use KGC\CommonBundle\Form\CommonAbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class ForfaitTarificationType.
 */
class ForfaitTarificationType extends CommonAbstractType
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
            ->add('codeTarification', 'entity', array(
                'class' => 'KGCRdvBundle:CodeTarification',
                'property' => 'libelle',
            ))
            ->add('forfait', 'entity', array(
                'class' => 'KGCClientBundle:Option',
                'property' => 'label',
                'query_builder' => function (EntityRepository $er) {
                    return $er->findAllByTypeQB(Option::TYPE_PLAN);
                },
            ))
            ->add('price', 'money', array(
                'required' => true,
            ))
        ;

        if ($this->isEdit) {
            $builder->get('codeTarification')->setDisabled(true);
            $builder->get('forfait')->setDisabled(true);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\RdvBundle\Entity\ForfaitTarification',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_userbundle_vforfait_tarification';
    }
}
