<?php
// src/KGC/StatBundle/Form/StatController/BonusParameterType.php

namespace KGC\StatBundle\Form;

use KGC\StatBundle\Entity\BonusParameter;
use KGC\UserBundle\Entity\Profil;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class MoyenPaiementType.
 *
 * @category EntityRepository
 */
class PhonisteBonusHebdoType extends BonusParameterType
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
        parent::__construct($isEdit);
        $this->isEdit = $isEdit;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $options['userProfil'] = Profil::PHONISTE;
        parent::buildForm($builder, $options);
        
        $builder->add('code', 'hidden', ['data' => BonusParameter::PHONISTE_HEBDO ]);
        $builder->get('date')->setRequired(true);
        $builder->get('objective')->setRequired(true);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'bonus_parameter_phonistehebdo';
    }
}