<?php
// src/KGC/StatBundle/Form/StatisticColumnType.php

namespace KGC\StatBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AboColumnType extends AbstractType
{

    const CODE_CA_REAL = 'ca_realised';
    const CODE_CA_ENC = 'ca_enc';
    const CODE_OPPO = 'nb_oppo';
    const CODE_REFUND = 'nb_refund';

    const LABEL_CA_REAL = 'CA Réalisé';
    const LABEL_CA_ENC = 'CA Encaissé';
    const LABEL_OPPO = 'Oppositions';
    const LABEL_REFUND = 'Remboursements';

    const LABEL_SHORT_CA_REAL = 'CA REA';
    const LABEL_SHORT_CA_ENC = 'CA ENC';
    const LABEL_SHORT_OPPO = 'OPPO';
    const LABEL_SHORT_REFUND = 'REMB';

    public static $ABO_CHOICE = [
        self::LABEL_OPPO => self::CODE_OPPO,
        self::LABEL_REFUND => self::CODE_REFUND,
        self::LABEL_CA_REAL => self::CODE_CA_REAL,
        self::LABEL_CA_ENC => self::CODE_CA_ENC,
    ];

    public static $ABO_HEADER = [
        self::CODE_OPPO => self::LABEL_SHORT_OPPO,
        self::CODE_REFUND => self::LABEL_SHORT_REFUND,
        self::CODE_CA_REAL => self::LABEL_SHORT_CA_REAL,
        self::CODE_CA_ENC => self::LABEL_SHORT_CA_ENC,
    ];


    public static $ABO_LIGHTED_CHOICES = [
        self::LABEL_CA_REAL => self::CODE_CA_REAL,
        self::LABEL_CA_ENC => self::CODE_CA_ENC,
    ];


    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $multiple = isset($options['data']['multiple']) ? $options['data']['multiple']: false;
            $builder
                ->add('abo', ChoiceType::class, array(
                    'label' => 'Colonnes : ',
                    'choices' => self::$ABO_CHOICE,
                    'data' => $multiple ? isset($options['data']['selected_abo']) ? $options['data']['selected_abo']: self::$ABO_CHOICE : null,
                    'multiple' => $multiple,
                    'expanded' => true,
                    'choices_as_values' => true,
                ));
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
        return 'kgc_StatBundle_admin_abo_tchat_column';
    }
}

?>