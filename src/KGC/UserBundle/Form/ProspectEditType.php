<?php

// src/KGC/RdvBundle/Form/RDVEditType.php


namespace KGC\UserBundle\Form;

use KGC\Bundle\SharedBundle\Entity\LandingUser;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use KGC\RdvBundle\Entity\ActionSuivi;

/**
 * Constructeur de formulaire d'accès à l'édition des données du prospect.
 *
 * @category Form
 *
 * @author Nicolas Mendez <nicolas.kgcom@gmail.com>
 */
class ProspectEditType extends AbstractType
{
    /**
     * @var \KGC\Bundle\SharedBundle\Entity\LandingUser
     */
    private $prospect;

    /**
     * Constructeur.
     *
     * @param \KGC\Bundle\SharedBundle\Entity\LandingUser $prospect
     */
    public function __construct(LandingUser $prospect)
    {
        $this->prospect = $prospect;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $common_fields = [
            'mapped' => false,
            'required' => false,
            'switch_style' => 6,
        ];
        if (!empty($options['fiche'])) {
            $builder
                ->add('myastroId', 'checkbox', array_merge($common_fields, array(
                    'enable_fields' => 'kgc_userbundle_prospect_myastroId',
                )))
                ->add('firstname', 'checkbox', array_merge($common_fields, array(
                    'enable_fields' => 'kgc_userbundle_prospect_firstname',
                )))
                ->add('gender', 'checkbox', array_merge($common_fields, array(
                    'enable_fields' => 'kgc_userbundle_prospect_gender',
                )))
                ->add('birthday', 'checkbox', array_merge($common_fields, array(
                    'enable_fields' => 'kgc_userbundle_prospect_birthday',
                )))
                ->add('phone', 'checkbox', array_merge($common_fields, array(
                    'enable_fields' => 'kgc_userbundle_prospect_phone',
                )))
                ->add('email', 'checkbox', array_merge($common_fields, array(
                    'enable_fields' => 'kgc_userbundle_prospect_email',
                )))
                ->add('country', 'checkbox', array_merge($common_fields, array(
                    'enable_fields' => 'kgc_userbundle_prospect_country',
                )));
        }
        if (!empty($options['tracking'])) {
            $builder
                ->add('website', 'checkbox', array_merge($common_fields, array(
                    'enable_fields' => 'kgc_userbundle_prospect_website',
                )))
                ->add('myastroGclid', 'checkbox', array_merge($common_fields, array(
                    'enable_fields' => 'kgc_userbundle_prospect_myastroGclid',
                )))
                ->add('source', 'checkbox', array_merge($common_fields, array(
                    'enable_fields' => 'kgc_userbundle_prospect_source',
                )))
                ->add('support', 'checkbox', array_merge($common_fields, array(
                    'enable_fields' => 'kgc_userbundle_prospect_support',
                )))
                ->add('formurl', 'checkbox', array_merge($common_fields, array(
                    'enable_fields' => 'kgc_userbundle_prospect_formurl',
                )))
                ->add('codepromo', 'checkbox', array_merge($common_fields, array(
                    'enable_fields' => 'kgc_userbundle_prospect_codepromo',
                )))
                ->add('voyant', 'checkbox', array_merge($common_fields, array(
                    'enable_fields' => 'kgc_userbundle_prospect_voyant',
                )));
        }
        if (!empty($options['consultation'])) {
            $builder
                ->add('questionSubject', 'checkbox', array_merge($common_fields, array(
                    'enable_fields' => 'kgc_userbundle_prospect_questionSubject',
                )))
                ->add('questionContent', 'checkbox', array_merge($common_fields, array(
                    'enable_fields' => 'kgc_userbundle_prospect_questionContent',
                )))
                ->add('questionText', 'checkbox', array_merge($common_fields, array(
                    'enable_fields' => 'kgc_userbundle_prospect_questionText',
                )))
                ->add('spouseName', 'checkbox', array_merge($common_fields, array(
                    'enable_fields' => 'kgc_userbundle_prospect_spouseName',
                )))
                ->add('spouseBirthday', 'checkbox', array_merge($common_fields, array(
                    'enable_fields' => 'kgc_userbundle_prospect_spouseBirthday',
                )))
                ->add('spouseSign', 'checkbox', array_merge($common_fields, array(
                    'enable_fields' => 'kgc_userbundle_prospect_spouseSign',
                )));
        }
        if (!empty($options['state'])) {
            $builder
                ->add('state', 'checkbox', array_merge($common_fields, array(
                    'enable_fields' => 'kgc_userbundle_prospect_state',
                )))
                ->add('dateState', 'checkbox', array_merge($common_fields, array(
                    'enable_fields' => 'kgc_userbundle_prospect_dateState',
                )));
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array('consultation' => true, 'fiche' => true, 'state' => true));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_userbundle_prospectedit';
    }
}
