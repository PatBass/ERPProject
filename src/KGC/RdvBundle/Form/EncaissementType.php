<?php

// src/KGC/RdvBundle/Form/EncaissementType.php


namespace KGC\RdvBundle\Form;

use KGC\CommonBundle\Form\CommonAbstractType;
use KGC\CommonBundle\Form\DateTimeType;
use KGC\RdvBundle\Entity\Encaissement;
use KGC\RdvBundle\Entity\MoyenPaiement;
use KGC\RdvBundle\Repository\MoyenPaiementRepository;
use KGC\RdvBundle\Repository\TPERepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Constructeur de formulaire d'encaissement.
 *
 * @category Form
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class EncaissementType extends CommonAbstractType
{
    // le form est il utilisé dans le cadre d'une collection
    private $collection = false;
    // accès à l'état de l'encaissement, permet d'enregister état fait/refusé
    private $etat = false;
    // désactiver le formulaire pour ces encaissements
    private $disabled_enc = array();

    private $no_date = false;

    protected $field_defs = array();

    public function __construct($collection = false, $etat = false, $disabled_enc = array(), $no_date = false)
    {
        $this->collection = $collection;
        $this->etat = $etat;
        $this->disabled_enc = $disabled_enc;
        $this->no_date = $no_date;
    }

    /**
     * @return array
     */
    protected function getTpeFieldConf()
    {
        return [
            'type' => 'entity',
            'options' => [
                'class' => 'KGCRdvBundle:TPE',
                'property' => 'libelle',
                'query_builder' => function (TPERepository $tpe_rep) {
                     return $tpe_rep->findAllBackofficeQB(true);
                },
                'empty_value' => 'TPE non renseigné',
                'required' => false,
                'input_addon' => 'credit-card',
                'attr' => ['class' => 'encaissement-tpe']
            ],
        ];
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->field_defs = array(
            'tpe' => $this->getTpeFieldConf(),
            'moyenPaiement' => array(
                'type' => 'entity',
                'options' => [
                    'class' => 'KGCRdvBundle:MoyenPaiement',
                    'property' => 'libelle',
                    'query_builder' => function (MoyenPaiementRepository $mpm_rep) {
                         return $mpm_rep->findAllQB(true);
                    },
                    'required' => true,
                    'input_addon' => 'money',
                ],
            ),
            'montant' => array(
                'type' => 'money',
                'options' => ['required' => true],
            ),
            'psychic_asso' => array(
                'type' => 'checkbox',
                'options' => [
                    'label' => 'Attribué au voyant',
                    'required' => false,
                    'attr' => [
                        'checked' => 'checked',
                        'style' => 'margin-right: 6px; font-size: 12px;	white-space: nowrap;',
                    ],
                ]
            ),
        );
        if (!$this->no_date) {
            $this->field_defs['date'] = array(
                'type' => new DateTimeType(),
                'options' => [
                    'date_widget' => 'single_text',
                    'time_widget' => 'single_text',
                    'date_format' => 'dd/MM/yyyy',
                    'date-picker' => true,
                    'input-mask' => true,
                    'with_seconds' => true,
                    'hide_time' => true,
                ],
            );
        }
        $etat_field_defs = [
            'type' => 'choice',
            'options' => [
                'empty_value' => 'État',
                'choices' => [
                    Encaissement::DONE => 'Fait',
                    Encaissement::DENIED => 'Refusé',
                ],
                'choices_attr' => array(
                    Encaissement::DONE => array('data-lightclass' => 'label-success'),
                    Encaissement::DENIED => array('data-lightclass' => 'label-danger'),
                ),
                'attr' => ['class' => 'encaissement-status light-on-value no-appearance']
            ]
        ];

        if ($this->etat) {
            $this->field_defs['etat'] = $etat_field_defs;
        }

        $this->addFormfromDefArray($builder, $this->field_defs);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($etat_field_defs) {
            $encaissement = $event->getData();
            $form = $event->getForm();
            $curr_field_defs = $this->field_defs;
            if (
                $this->collection
                && (
                    $encaissement === null
                    || $encaissement->getPayment() === null
                )
            ) {
                $curr_field_defs['supprimer'] = array(
                    'type' => 'button',
                    'options' => [
                        'label' => '<i class="icon-trash bigger-120"></i>',
                        'attr' => array(
                            'class' => 'collection_del btn-danger btn-xs',
                            'style' => 'margin: 5px;',
                        ),
                    ],
                );
            }
            if (isset($encaissement)) {
                if (in_array($encaissement->getEtat(), [Encaissement::DENIED, Encaissement::DONE])) {
                    unset($etat_field_defs['options']['empty_value']);
                    $curr_field_defs['etat'] = $etat_field_defs;
                }
                $this->updateTpeFieldConf($curr_field_defs, $encaissement);
                $date = $encaissement->getDate();
                // moyens paiement disponibles selon date
                $curr_field_defs['moyenPaiement']['options']['query_builder'] = function (MoyenPaiementRepository $mpm_rep) use ($date) {
                    return $mpm_rep->findAllQB(true, $date);
                };
                // désactivation formulaire si encaissement désigné
                if (in_array($encaissement, $this->disabled_enc, true)) {
                    foreach ($curr_field_defs as $fieldname => $field) {
                        $curr_field_defs[$fieldname]['options']['disabled'] = true;
                        $curr_field_defs[$fieldname]['options']['attr']['data-enableable'] = 0;
                    }
                }
            }
            // MAJ des champs
            foreach ($curr_field_defs as $fieldname => $field) {
                $form->add($fieldname, $field['type'], $field['options']);
            }
        });
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            $enc = $event->getData();
            $form = $event->getForm();

            if ($enc === null) {
                $this->field_defs['psychic_asso']['options']['attr']['checked'] = 'checked';
                $form->add('psychic_asso', $this->field_defs['psychic_asso']['type'], $this->field_defs['psychic_asso']['options']);
            } else if ($enc->getId() > 0 && $enc->getPsychicAsso() == 0) {
                unset($this->field_defs['psychic_asso']['options']['attr']['checked']);
                $form->add('psychic_asso', $this->field_defs['psychic_asso']['type'], $this->field_defs['psychic_asso']['options']);
            }
        });
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $enc = $form->getData();
            $data = $event->getData();
            if ($enc instanceof Encaissement) {
                if ($this->etat && $data['moyenPaiement'] == '1') { //  MoyenPaiement::DEBIT_CARD
                    $this->field_defs['tpe']['options']['constraints'] = [new NotBlank(['message' => 'Pour un paiement en CB, veuillez renseigner le TPE.'])];
                    $form->add('tpe', $this->field_defs['tpe']['type'], $this->field_defs['tpe']['options']);
                }
            }
        });
    }

    protected function updateTpeFieldConf(&$fieldDefs, Encaissement $enc)
    {
        if ($isPaidEnc = $enc->getPayment() && $enc->getTpe() && $enc->getPayment()->getTpe()->getId() == $enc->getTpe()->getId()) {
            $fieldDefs['tpe']['options']['required'] = true;
            unset($fieldDefs['tpe']['options']['empty_value']);
        }

        // tpe disponibles selon date
        $fieldDefs['tpe']['options']['query_builder'] = function (TPERepository $tpe_rep) use ($enc, $isPaidEnc) {
            if ($isPaidEnc) {
                return $tpe_rep->createQueryBuilder('tpe')->where('tpe.id = :id')->setParameter('id', $enc->getTpe()->getId());
            } else if ($enc->getTpe() && !$enc->getTpe()->getEnabled()) {
                return $tpe_rep->findAllBackofficeQB(true, $enc->getDate())
                    ->orWhere('t.id = :id')->setParameter('id', $enc->getTpe()->getId());
            } else {
                return $tpe_rep->findAllBackofficeQB(true, $enc->getDate());
            }
        };
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\RdvBundle\Entity\Encaissement',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_RdvBundle_encaissement';
    }
}
