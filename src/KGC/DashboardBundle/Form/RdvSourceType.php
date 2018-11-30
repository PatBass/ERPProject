<?php
// src/KGC/DashboardBundle/Form/RdvSourceType.php

namespace KGC\DashboardBundle\Form;

use KGC\Bundle\SharedBundle\Repository\WebsiteRepository;
use KGC\CommonBundle\Form\CommonAbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class RdvSourceType.
 *
 * @category EntityRepository
 */
class RdvSourceType extends CommonAbstractType
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
            ->add('label', 'text', array(
                'label' => 'Libellé',
                'required' => true,
            ))
            ->add('hasGclid', 'checkbox', [
                'required' => false,
                'switch_style' => 5,
            ])
            ->add('affiliateAllowed', 'checkbox', [
                'required' => false,
                'switch_style' => 5,
            ])
            ->add('enabled', 'checkbox', [
                'required' => false,
                'switch_style' => 5,
            ])
            ->add('websites', 'entity', array(
                'class' => 'KGCSharedBundle:Website',
                'property' => 'libelle',
                'query_builder' => function (WebsiteRepository $web_rep) {
                    return $web_rep->findIsChatQB(false);
                },
                'multiple' => true,
                'expanded' => true,
            ))
        ;

        if ($this->isEdit) {
            $this->changeOptions($builder, 'idcode', ['read_only' => true]);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\RdvBundle\Entity\Source',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'rdv_source';
    }
}
