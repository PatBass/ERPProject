<?php
// src/KGC/DashboardBundle/Form/MoyenPaiementType.php

namespace KGC\DashboardBundle\Form;

use KGC\CommonBundle\Form\CommonAbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class MoyenPaiementType.
 *
 * @category EntityRepository
 */
class MoyenPaiementType extends CommonAbstractType
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
            ->add('idcode', 'text', array(
                'required' => true,
            ))
            ->add('libelle', 'text', array(
                'required' => true,
            ))
            ->add('enabled', 'checkbox', [
                'required' => false,
                'switch_style' => 5,
            ])
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
            'data_class' => 'KGC\RdvBundle\Entity\MoyenPaiement',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'moyen_paiement';
    }
}
