<?php
// src/KGC/StatBundle/Form/StatisticColumnType.php

namespace KGC\StatBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class StatisticColumnType extends AbstractType
{

    const CODE_CA_REAL = 'ca_realised';
    const CODE_CA_UNPAID = 'ca_unpaid';
    const CODE_CA_ENC_MM = 'ca_enc_mm';
    const CODE_CA_ENC_JM = 'ca_enc_jm';
    const CODE_CA_RECUP_MM = 'ca_recup_mm';
    const CODE_CA_RECUP_MP = 'ca_recup_mp';
    const CODE_CA_ENC = 'ca_enc';
    const CODE_CA_ENC_OPPO = 'ca_enc_oppo';
    const CODE_CA_ENC_REFUND = 'ca_enc_refund';
    const CODE_CA_ENC_SECU = 'ca_enc_secu';
    const CODE_CA_AVERAGE_REAL = 'ca_average_realised';
    const CODE_CA_AVERAGE_PAYMENT = 'ca_average_payment';

    const CODE_RDV_TOTAL = 'rdv_total';
    const CODE_RDV_510_MIN = 'rdv_510_min';
    const CODE_RDV_CB_BELGE = 'rdv_cb_belge';
    const CODE_RDV_CB_VIDE = 'rdv_cb_vide';
    const CODE_RDV_FNA = 'rdv_fna';
    const CODE_RDV_NRP = 'rdv_nrp';
    const CODE_RDV_NVP = 'rdv_nvp';
    const CODE_RDV_REFUSED_SECU = 'rdv_refused_secu';
    const CODE_RDV_CANCELLED = 'rdv_cancelled';
    const CODE_RDV_WAITORREPORT = 'rdv_waitorreport';
    const CODE_RDV_TREATED = 'rdv_treated';
    const CODE_RDV_OVER_TEN_MINUTES = 'rdv_overTenMinutes';
    const CODE_RDV_TEN_MINUTES = 'rdv_tenMinutes';
    const CODE_RDV_VALIDATED = 'rdv_validated';
    const CODE_RDV_UNPAID = 'rdv_unpaid';

    const LABEL_CA_REAL = 'Réalisé';
    const LABEL_CA_UNPAID = 'Impayé';
    const LABEL_CA_ENC_MM = 'Encaissé MM';
    const LABEL_CA_ENC_JM = 'Encaissé JM';
    const LABEL_CA_RECUP_MM = 'Récup MM';
    const LABEL_CA_RECUP_MP = 'Récup MP';
    const LABEL_CA_ENC = 'Encaissé';
    const LABEL_CA_ENC_OPPO = 'Oppositions';
    const LABEL_CA_ENC_REFUND = 'Remboursements';
    const LABEL_CA_ENC_SECU = 'Sécurisations';
    const LABEL_CA_AVERAGE_REAL = 'Moyenne réa.';
    const LABEL_CA_AVERAGE_PAYMENT = 'Moyenne paie.';

    const DATE_TYPE_CONSULTATION = 'prise_rdv';
    const DATE_TYPE_HISTORIQUE = 'historique';

    const LABEL_RDV_TOTAL = 'Total';
    const LABEL_RDV_CANCELLED = 'Annulés';
    const LABEL_RDV_510_MIN = 'Annulés (5x10min)';
    const LABEL_RDV_CB_BELGE = 'Annulés (CB Belge)';
    const LABEL_RDV_CB_VIDE = 'Annulés (CB non remplie)';
    const LABEL_RDV_FNA = 'Annulés (FNA)';
    const LABEL_RDV_NRP = 'Annulés (NRP)';
    const LABEL_RDV_NVP = 'Annulés (NVP)';
    const LABEL_RDV_REFUSED_SECU = 'Annulés (Refus 1€)';
    const LABEL_RDV_WAITORREPORT = 'En attente/reporté';
    const LABEL_RDV_TREATED = 'Traités';
    const LABEL_RDV_OVER_TEN_MINUTES = '+10min';
    const LABEL_RDV_TEN_MINUTES = '10min';
    const LABEL_RDV_VALIDATED = 'Validés';
    const LABEL_RDV_UNPAID = 'Impayés';

    public static $CA_CHOICES = [
        self::LABEL_CA_REAL => self::CODE_CA_REAL,
        self::LABEL_CA_UNPAID => self::CODE_CA_UNPAID,
        self::LABEL_CA_ENC_MM => self::CODE_CA_ENC_MM,
        self::LABEL_CA_ENC_JM => self::CODE_CA_ENC_JM,
        self::LABEL_CA_RECUP_MM => self::CODE_CA_RECUP_MM,
        self::LABEL_CA_RECUP_MP => self::CODE_CA_RECUP_MP,
        self::LABEL_CA_ENC => self::CODE_CA_ENC,
        self::LABEL_CA_ENC_OPPO => self::CODE_CA_ENC_OPPO,
        self::LABEL_CA_ENC_REFUND => self::CODE_CA_ENC_REFUND,
        self::LABEL_CA_ENC_SECU => self::CODE_CA_ENC_SECU,
        self::LABEL_CA_AVERAGE_REAL => self::CODE_CA_AVERAGE_REAL,
        self::LABEL_CA_AVERAGE_PAYMENT => self::CODE_CA_AVERAGE_PAYMENT
    ];
    public static $RDV_CHOICES = [
        self::LABEL_RDV_TOTAL => self::CODE_RDV_TOTAL,
        self::LABEL_RDV_CANCELLED => self::CODE_RDV_CANCELLED,
        self::LABEL_RDV_510_MIN => self::CODE_RDV_510_MIN,
        self::LABEL_RDV_CB_BELGE => self::CODE_RDV_CB_BELGE,
        self::LABEL_RDV_CB_VIDE => self::CODE_RDV_CB_VIDE,
        self::LABEL_RDV_FNA => self::CODE_RDV_FNA,
        self::LABEL_RDV_NRP => self::CODE_RDV_NRP,
        self::LABEL_RDV_NVP => self::CODE_RDV_NVP,
        self::LABEL_RDV_REFUSED_SECU => self::CODE_RDV_REFUSED_SECU,
        self::LABEL_RDV_WAITORREPORT => self::CODE_RDV_WAITORREPORT,
        self::LABEL_RDV_TREATED => self::CODE_RDV_TREATED,
        self::LABEL_RDV_OVER_TEN_MINUTES => self::CODE_RDV_OVER_TEN_MINUTES,
        self::LABEL_RDV_TEN_MINUTES => self::CODE_RDV_TEN_MINUTES,
        self::LABEL_RDV_VALIDATED => self::CODE_RDV_VALIDATED,
        self::LABEL_RDV_UNPAID => self::CODE_RDV_UNPAID
    ];

    public static $RDV_LIGHTED_CHOICES = [
        self::LABEL_RDV_TOTAL => self::CODE_RDV_TOTAL,
        self::LABEL_RDV_CANCELLED => self::CODE_RDV_CANCELLED,
        self::LABEL_RDV_WAITORREPORT => self::CODE_RDV_WAITORREPORT,
        self::LABEL_RDV_TREATED => self::CODE_RDV_TREATED,
        self::LABEL_RDV_OVER_TEN_MINUTES => self::CODE_RDV_OVER_TEN_MINUTES,
        self::LABEL_RDV_TEN_MINUTES => self::CODE_RDV_TEN_MINUTES,
        self::LABEL_RDV_VALIDATED => self::CODE_RDV_VALIDATED,
        self::LABEL_RDV_UNPAID => self::CODE_RDV_UNPAID
    ];


    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $multiple = isset($options['data']['multiple']) ? $options['data']['multiple']: false;
        $separated = isset($options['data']['separated']) ? $options['data']['separated']: false;

        if($separated) {
            $builder
                ->add('ca', ChoiceType::class, array(
                    'label' => 'CA',
                    'choices' => self::$CA_CHOICES,
                    'data' => $multiple ? isset($options['data']['selected_ca']) ? $options['data']['selected_ca']: self::$CA_CHOICES : null,
                    'multiple' => $multiple,
                    'expanded' => true,
                    'choices_as_values' => true,
                ))
                ->add('rdv', ChoiceType::class, array(
                    'label' => 'RDV',
                    'choices' => self::$RDV_CHOICES,
                    'data' => $multiple ? isset($options['data']['selected_rdv']) ? $options['data']['selected_rdv']: self::$RDV_CHOICES : null,
                    'multiple' => $multiple,
                    'expanded' => true,
                    'choices_as_values' => true,
                ));
        }
        else {
            $builder
            ->add('column', ChoiceType::class, array(
                'label' => 'Colonne',
                'choices' => array_merge(self::$RDV_CHOICES, self::$CA_CHOICES),
                'data' => isset($options['data']['selected']) ? $options['data']['selected']: self::CODE_RDV_TOTAL,
                'multiple' => false,
                'required' => true,
                'choices_as_values' => true,
                'attr' => array(
                    'class' => 'chosen-select tag-input-style'
                )
            ));
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'mapped' => false,
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_StatBundle_admin_statistic_column';
    }
}

?>