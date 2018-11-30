<?php

// src/KGC/RdvBundle/Form/RDVEditType.php


namespace KGC\RdvBundle\Form;

use KGC\RdvBundle\Entity\RDV;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use KGC\RdvBundle\Entity\ActionSuivi;

/**
 * Constructeur de formulaire d'accès à l'édition des données du RDV.
 *
 * @category Form
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class RDVEditType extends AbstractType
{
    /**
     * @var \KGC\RdvBundle\Entity\RDV
     */
    private $rdv;

    /**
     * Constructeur.
     *
     * @param \KGC\RdvBundle\Entity\RDV $rdv
     */
    public function __construct(RDV $rdv)
    {
        $this->rdv = $rdv;
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
        $builder
            ->add('cartebancaires', 'checkbox', array_merge($common_fields, array(
                'value' => ActionSuivi::UPDATE_BANKDETAILS,
                'enable_fields' => 'kgc_RdvBundle_rdv_cartebancaire;add_cartebancaire',
            )))
            ->add('client_nom', 'checkbox', array_merge($common_fields, array(
                'value' => ActionSuivi::UPDATE_SURNAME,
                'enable_fields' => 'kgc_RdvBundle_rdv_client_nom',
            )))
            ->add('client_prenom', 'checkbox', array_merge($common_fields, array(
                'value' => ActionSuivi::UPDATE_NAME,
                'enable_fields' => 'kgc_RdvBundle_rdv_client_prenom',
            )))
            ->add('client_dateNaissance', 'checkbox', array_merge($common_fields, array(
                'value' => ActionSuivi::UPDATE_BIRTHDATE,
                'enable_fields' => 'kgc_RdvBundle_rdv_client_dateNaissance',
            )))
            ->add('client_genre', 'checkbox', array_merge($common_fields, array(
                'value' => ActionSuivi::UPDATE_GENDER,
                'enable_fields' => 'kgc_RdvBundle_rdv_client_genre',
            )))
            ->add('numtel1', 'checkbox', array_merge($common_fields, array(
                'value' => ActionSuivi::UPDATE_PHONE,
                'enable_fields' => 'kgc_RdvBundle_rdv_numtel1',
            )))
            ->add('numtel2', 'checkbox', array_merge($common_fields, array(
                'value' => ActionSuivi::UPDATE_PHONE,
                'enable_fields' => 'kgc_RdvBundle_rdv_numtel2',
            )))
            ->add('source', 'checkbox', array_merge($common_fields, array(
                'value' => ActionSuivi::UPDATE_SOURCE,
                'enable_fields' => 'kgc_RdvBundle_rdv_source',
            )))
            ->add('gclid', 'checkbox', array_merge($common_fields, array(
                'value' => ActionSuivi::UPDATE_GCLID,
                'enable_fields' => 'kgc_RdvBundle_rdv_gclid',
            )))
            ->add('client_mail', 'checkbox', array_merge($common_fields, array(
                'value' => ActionSuivi::UPDATE_MAIL,
                'enable_fields' => 'kgc_RdvBundle_rdv_client_mail',
            )))
            ->add('adresse', 'checkbox', array_merge($common_fields, array(
                'value' => ActionSuivi::UPDATE_MAIL,
                'enable_fields' => 'kgc_RdvBundle_rdv_adresse',
            )))
            ->add('encaissements', 'checkbox', array_merge($common_fields, array(
                'value' => ActionSuivi::UPDATE_BILLING,
                'enable_fields' => 'kgc_RdvBundle_rdv_encaissements',
                'attr' => array(
                    'class' => 'display-switch',
                    'data-displaytarget' => 'encaissement_edit',
                ),
            )))
            ->add('idAstro_valeur', 'checkbox', array_merge($common_fields, array(
                'value' => ActionSuivi::UPDATE_IDASTRO,
                'enable_fields' => 'kgc_RdvBundle_rdv_idAstro_valeur',
            )))
            ->add('securisation', 'checkbox', array_merge($common_fields, array(
                'value' => ActionSuivi::UPDATE_SECURISATION,
                'enable_fields' => 'kgc_RdvBundle_rdv_securisation',
            )))
            ->add('TPE', 'checkbox', array_merge($common_fields, array(
                'value' => ActionSuivi::UPDATE_SECURISATION,
                'enable_fields' => 'kgc_RdvBundle_rdv_TPE',
            )))
            ->add('tarification', 'checkbox', array_merge($common_fields, array(
                'value' => ActionSuivi::UPDATE_PRICERANGE,
                'enable_fields' => 'kgc_RdvBundle_rdv_tarification',
                'attr' => array(
                    'class' => 'display-switch',
                    'data-displaytarget' => 'tarification_edit',
                ),
            )))
            ->add('support', 'checkbox', array_merge($common_fields, array(
                'value' => ActionSuivi::UPDATE_SUPPORT,
                'enable_fields' => 'kgc_RdvBundle_rdv_support',
            )))
            ->add('website', 'checkbox', array_merge($common_fields, array(
                'value' => ActionSuivi::UPDATE_WEBSITE,
                'enable_fields' => 'kgc_RdvBundle_rdv_website',
            )))
            ->add('codepromo', 'checkbox', array_merge($common_fields, array(
                'value' => ActionSuivi::UPDATE_OFFERCODE,
                'enable_fields' => 'kgc_RdvBundle_rdv_codepromo',
            )))
            ->add('proprio', 'checkbox', array_merge($common_fields, array(
                'value' => ActionSuivi::UPDATE_OWNER,
                'enable_fields' => 'kgc_RdvBundle_rdv_proprio',
            )))
            ->add('voyant', 'checkbox', array_merge($common_fields, array(
                'value' => ActionSuivi::UPDATE_PSYCHIC,
                'enable_fields' => 'kgc_RdvBundle_rdv_voyant',
            )))
            ->add('consultant', 'checkbox', array_merge($common_fields, array(
                'value' => ActionSuivi::UPDATE_CONSULTANT,
                'enable_fields' => 'kgc_RdvBundle_rdv_consultant',
            )))
            ->add('envoisProduits', 'checkbox', array_merge($common_fields, array(
                'value' => ActionSuivi::UPDATE_PRODUCT_SENDING,
                'enable_fields' => 'kgc_RdvBundle_rdv_envoisProduits',
            )))
            ->add('classement:litige', 'checkbox', array_merge($common_fields, array(
                'value' => ActionSuivi::SORT_CONSULT,
                'enable_fields' => 'kgc_RdvBundle_rdv_classement;kgc_RdvBundle_rdv_litige',
            )))
            ->add('formurl', 'checkbox', array_merge($common_fields, array(
                'value' => ActionSuivi::UPDATE_FORMURL,
                'enable_fields' => 'kgc_RdvBundle_rdv_formurl',
            )))
            ->add('questionSubject', 'checkbox', array_merge($common_fields, array(
                'enable_fields' => 'kgc_RdvBundle_rdv_questionSubject',
            )))
            ->add('questionContent', 'checkbox', array_merge($common_fields, array(
                'enable_fields' => 'kgc_RdvBundle_rdv_questionContent',
            )))
            ->add('questionText', 'checkbox', array_merge($common_fields, array(
                'enable_fields' => 'kgc_RdvBundle_rdv_questionText',
            )))
            ->add('spouseName', 'checkbox', array_merge($common_fields, array(
                'enable_fields' => 'kgc_RdvBundle_rdv_spouseName',
            )))
            ->add('spouseBirthday', 'checkbox', array_merge($common_fields, array(
                'enable_fields' => 'kgc_RdvBundle_rdv_spouseBirthday',
            )))
            ->add('spouseSign', 'checkbox', array_merge($common_fields, array(
                'enable_fields' => 'kgc_RdvBundle_rdv_spouseSign',
            )));
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array());
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_RdvBundle_rdvedit';
    }
}
