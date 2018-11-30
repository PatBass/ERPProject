<?php
// src/KGC/StatBundle/Form/StatController/BonusParameterType.php

namespace KGC\StatBundle\Form;

use Doctrine\ORM\EntityRepository;
use KGC\CommonBundle\Form\CommonAbstractType;
use KGC\StatBundle\Entity\BonusParameter;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class MoyenPaiementType.
 *
 * @category EntityRepository
 */
class BonusParameterType extends CommonAbstractType
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
            ->add('amount', 'number', array(
                'required' => true,
            ))
            ->add('date', 'date', array(
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'date-picker' => true,
                'empty_data' => date('d/m/Y')
            ))
            ->add('objective', 'number')
        ;
        if(isset($options['code'])){
            $builder->add('code', 'hidden', ['data' => $options['code'] ]);
            if(in_array($options['code'], [BonusParameter::PSYCHIC_PENALTY])){
                $builder->get('date')->setRequired(true);
                $builder->remove('objective');
            } elseif(in_array($options['code'], [ BonusParameter::PHONISTE_HEBDO, BonusParameter::PSYCHIC_HEBDO])){
                $builder->get('date')->setRequired(true);
                $builder->get('objective')->setRequired(true);
            }
        }
        if(isset($options['userProfil'])){
            $builder->add('user', 'entity', array(
                'class' => 'KGCUserBundle:Utilisateur',
                'property' => 'username',
                'query_builder' => function (EntityRepository $er) use ($options) {
                    return $er->findAllByMainProfilQB($options['userProfil'], true);
                },
                'attr' => array(
                    'class' => 'chosen-select',
                    'data-width' => '90%',
                ),
                'required' => true,   
            ));
        }
        
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
            'data_class' => 'KGC\StatBundle\Entity\BonusParameter',
            'code' => null,
            'userProfil' => null
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'bonus_parameter';
    }
}