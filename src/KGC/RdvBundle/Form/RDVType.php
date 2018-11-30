<?php
// src/KGC/RdvBundle/Form/RDVType.php

namespace KGC\RdvBundle\Form;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use KGC\Bundle\SharedBundle\Repository\LandingUserRepository;
use KGC\ClientBundle\Form\ClientType;
use KGC\ClientBundle\Form\IdAstroType;
use KGC\Bundle\SharedBundle\Repository\WebsiteRepository;
use KGC\ClientBundle\Service\HistoriqueManager;
use KGC\CommonBundle\Form\CommonAbstractType;
use KGC\DashboardBundle\Form\PhoneType;
use KGC\RdvBundle\Entity\Encaissement;
use KGC\RdvBundle\Entity\RDV;
use KGC\RdvBundle\Entity\Source;
use KGC\RdvBundle\Form\DataTransformer\HiddenEntityTransformer;
use KGC\RdvBundle\Repository\CodePromoRepository;
use KGC\RdvBundle\Repository\DossierRepository;
use KGC\RdvBundle\Repository\EtiquetteRepository;
use KGC\RdvBundle\Repository\SourceRepository;
use KGC\RdvBundle\Repository\SupportRepository;
use KGC\RdvBundle\Repository\TPERepository;
use KGC\UserBundle\Entity\Utilisateur;
use KGC\UserBundle\Entity\Profil;
use KGC\UserBundle\Repository\VoyantRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Constructeur de formulaire pour entité RDV (consultations).
 *
 * @category Form
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class RDVType extends CommonAbstractType
{
    /**
     * @var \KGC\UserBundle\Entity\Utilisateur
     */
    protected $user;

    /**
     * @var array
     */
    protected $edit_params;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    protected $edit_classement = false;
    /**
     * @var HistoriqueManager
     */
    protected $historique_manager = null;

    protected $cbMasked = false;
    protected $decrypt = false;
    protected $forceHide = false;
    protected $forcePhoneFiled = false;

    protected $field_defs = array();

    protected $tpeFieldConf = [];

    /**
     * @param $editParams
     */
    protected function setEditParams($editParams)
    {
        $this->edit_params = $editParams;
    }

    /**
     * @param Utilisateur $user
     * @param $edit_params
     * @param EntityManager $em
     */
    public function __construct(Utilisateur $user, $edit_params, EntityManager $em, $historiqueManager = null, $edit_classement = false, $cbMasked = false, $decrypt = false, $forceHide = false, $forcePhoneFiled = false)
    {
        $this->user = $user;
        $this->edit_params = $edit_params;
        $this->em = $em;
        $this->historique_manager = $historiqueManager;
        $this->edit_classement = $edit_classement;
        $this->cbMasked = $cbMasked;
        $this->decrypt = $decrypt;
        $this->forceHide = $forceHide;
        $this->forcePhoneFiled = $forcePhoneFiled;
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
                'empty_value' => '... ',
                'required' => false,
                'input_addon' => 'credit-card'
            ],
        ];
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->tpeFieldConf = $this->getTpeFieldConf();

        $currentProfils = $this->user->getProfils();
        /* ------------------------------------------ */
        /* --  CHAMPS DONNEES A EDITION TRACKABLE  -- */
        /* ------------------------------------------ */
        $this->field_defs = array(
            'dateConsultation' => array(
                'type' => 'datetime',
                'options' => [
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy HH:mm',
                    'read_only' => true,
                    'attr' => ['class' => 'input-mask-datetime'],
                ],
            ),
            'support' => array(
                'type' => 'entity',
                'options' => [
                    'class' => 'KGCRdvBundle:Support',
                    'property' => 'libelle',
                    'query_builder' => function (SupportRepository $support_rep) use ($currentProfils) {
                        return $support_rep->findByProfilesQB($currentProfils, true);
                    },
                    'empty_value' => ' ',
                    'attr' => ['class' => 'chosen-select'],
                ],
            ),
            'website' => array(
                'type' => 'entity',
                'options' => [
                    'class' => 'KGCSharedBundle:Website',
                    'property' => 'libelle',
                    'query_builder' => function (WebsiteRepository $web_rep) {
                        return $web_rep->findIsChatQB(false);
                    },
                    'empty_value' => '...',
                    'required' => true,
                    'attr' => ['class' => 'js-website-select'],
                ],
            ),
            'codepromo' => array(
                'type' => 'entity',
                'options' => [
                    'class' => 'KGCRdvBundle:CodePromo',
                    'property' => 'code',
                    'query_builder' => function (CodePromoRepository $cpm_rep) {
                        return $cpm_rep->findAllQB(true);
                    },
                    'required' => false,
                    'attr' => ['class' => 'chosen-select'],
                ],
            ),
            'voyant' => array(
                'type' => 'entity',
                'options' => [
                    'class' => 'KGCUserBundle:Voyant',
                    'property' => 'nom',
                    'query_builder' => function (VoyantRepository $voy_rep) {
                        return $voy_rep->findAllHavingTarificationQB(true);
                    },
                    'empty_value' => '...',
                    'required' => false,
                ],
            ),
            'TPE' => $this->tpeFieldConf,
            'encaissements' => array(
                'type' => 'collection',
                'options' => [
                    'type' => new EncaissementType(true),
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                    'error_bubbling' => false,
                ],
            ),
            'source' => array(
                'type' => 'entity',
                'options' => [
                    'class' => 'KGCRdvBundle:Source',
                    'required' => false,
                    'empty_value' => '...',
                    'property' => 'label',
                    'attr' => ['class' => 'js-source-select'],
                    'query_builder' => function (SourceRepository $source_rep) {
                        return $source_rep->findAllQB(true);
                    },
                    'js_dependant_select' => [
                        'depends_on' => 'website',
                        'reference_value' => ['websitesIds'],
                    ],
                ],
            ),
            'formurl' => array(
                'type' => 'entity',
                'options' => [
                    'class' => 'KGCRdvBundle:FormUrl',
                    'property' => 'label',
                    'required' => true,
                    'empty_value' => '',
                    'js_dependant_select' => [
                        'depends_on' => 'website;source',
                        'reference_value' => ['websiteId', 'sourceId'],
                    ],
                    'attr' => array(
                        'class' => 'chosen-select',
                    ),
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('u')->addOrderBy('u.label');
                    },
                ],
            ),
            'proprio' => array(
                'type' => 'entity',
                'options' => [
                    'class' => 'KGCUserBundle:Utilisateur',
                    'property' => 'username',
                    'query_builder' => function (EntityRepository $er) {
                        return $er->findAllProprioQB(true);
                    },
                    'attr' => array(
                        'class' => 'chosen-select',
                    ),
                    'required' => true,
                ],
            ),
            'securisation' => [
                'type' => 'choice',
                'options' => [
                    'required' => false,
                    'choices' => [
                        RDV::SECU_PENDING => 'En attente',
                        RDV::SECU_DENIED => 'Refusée',
                        RDV::SECU_DONE => 'Faite',
                        RDV::SECU_SKIPPED => 'Passée',
                    ]
                ]
            ],
            'cartebancaires' => [
                'type' => 'collection',
                'options' => [
                    'type' => new CarteBancaireType($this->cbMasked, $this->decrypt, $this->forceHide),
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                ]
            ]
        );
        if ($this->edit_classement) {
            $this->field_defs['classement'] = array(
                'type' => 'entity',
                'options' => [
                    'class' => 'KGCRdvBundle:Dossier',
                    'property' => 'libelle',
                    'required' => false,
                    'empty_value' => 'Aucun',
                ],
            );
            $this->field_defs['litige'] = array(
                'type' => 'checkbox',
                'options' => [
                    'mapped' => false,
                    'required' => false,
                    'switch_style' => 5,
                ],
            );
        }
        $this->addFormfromDefArray($builder, $this->field_defs);
        if($this->user->isAllowedToMakeCall() && !$this->forcePhoneFiled) {
            if(!is_null($this->user->getPoste())) {
                $builder
                    ->add('numtel1', new PhoneType(), ['required' => false, 'attr' => ['btnCall' => true]])
                    ->add('numtel2', new PhoneType(), ['required' => false, 'attr' => ['btnCall' => true]]);
            }
        } else {
            $builder
                ->add('numtel1', new PhoneType())
                ->add('numtel2', new PhoneType(), ['required' => false]);
        }
        $builder
            ->add('client', new ClientType())
            ->add('idAstro', new IdAstroType())
            ->add('adresse', new AdresseType())
            ->add($builder
                ->create('etat', 'hidden')
                ->addModelTransformer(new HiddenEntityTransformer($this->em->getRepository('KGCRdvBundle:Etat')))
            )
            ->add('proprio', 'entity', array(
                'class' => 'KGCUserBundle:Utilisateur',
                'property' => 'username',
                'query_builder' => function (EntityRepository $er) {
                    return $er->findAllProprioQB(true);
                },
                'attr' => array(
                    'class' => 'chosen-select',
                ),
                'required' => true,
            ))
            ->add('securisation', $this->field_defs['securisation']['type'], $this->field_defs['securisation']['options'])
            ->add('consultant', 'entity', array(
                'class' => 'KGCUserBundle:Utilisateur',
                'property' => 'username',
                'empty_value' => '',
                'query_builder' => function (EntityRepository $er) {
                    return $er->findAllByMainProfilQB(Profil::VOYANT, true);
                },
                'attr' => array(
                    'class' => 'chosen-select',
                ),
            ))
            ->add('notesLibres', 'textarea', array(
                'required' => false,
                'attr' => array(
                    'placeholder' => 'Prenez ici des notes sur le déroulement de la consultation...',
                    'rows' => 4,
                ),
            ))
            ->add('tarification', new TarificationType())
            ->add('notes', new NotesType([
                HistoriqueManager::HISTORY_SECTION_HISTORY,
                HistoriqueManager::HISTORY_SECTION_NOTES,
                HistoriqueManager::HISTORY_SECTION_PENDULUM,
                HistoriqueManager::HISTORY_SECTION_COM,
                HistoriqueManager::HISTORY_SECTION_ALERT,
                HistoriqueManager::HISTORY_SECTION_DRAW,
            ], $this->historique_manager))
            ->add('qualite', new NotesType([
                HistoriqueManager::HISTORY_SECTION_QUALITY,
            ], $this->historique_manager))
            ->add('envoisProduits', 'collection', [
                'type' => new EnvoiProduitType(),
            ])
            ->add('gclid', 'text', array(
                'required' => false,
                'attr' => array(
                    'class' => 'gclid',
                    'placeholder' => 'GClid',
                ),
            ))
            ->add('questionSubject', 'text',
                array(
                    'required' => false
                )
            )
            ->add('questionContent', 'text',
                array(
                    'required' => false
                )
            )
            ->add('questionText', 'text',
                array(
                    'required' => false
                )
            )
            ->add('spouseName', 'text',
                array(
                    'required' => false
                )
            )
            ->add('spouseBirthday', 'date', array(
                'required' => false,
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'input-mask' => true,
            ))
            ->add('spouseSign', 'text',
                array(
                    'required' => false
                )
            );
        /* --------------------------------- */
        /* --  TRAITEMENT DES/ACTIVATION  -- */
        /* --------------------------------- */
        if ($options['consultation']) {
            $this->enableEditFields($builder);
        }
        /* --------------------------------- */
        /* --   AUTRES CHAMPS DE DONNEES  -- */
        /* --------------------------------- */
        $builder
            ->add('cartebancaires_selected', 'hidden', ['mapped' => false, 'empty_data' => '0'])
            ->add('commentaire', 'text', array(
                'mapped' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'commentaire',
                    'placeholder' => ' Ajouter un commentaire',
                ),
            ))
            ->add('etiquettes', 'entity', array(
                'class' => 'KGCRdvBundle:Etiquette',
                'property' => 'libelle',
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'query_builder' => function (EtiquetteRepository $eti_rep) use ($currentProfils) {
                    return $eti_rep->getEtiquettesSwitchProfils($currentProfils);
                },
            ));
        /* ----------------------------- */
        /* --   CHAMPS FONCTIONNELS   -- */
        /* ----------------------------- */
        $builder
            ->add('mainaction', 'hidden', array(
                'mapped' => false,
                'required' => false,
                'data' => $options['mainaction'],
            ))
            ->add('actions', 'choice', array(
                'mapped' => false,
                'choices' => array_flip($this->edit_params),
                'multiple' => true,
                'required' => false,
            ))
            ->add('fermeture', 'checkbox', array(
                'mapped' => false,
                'required' => false,
                'checked_label_style' => false,
            ));
        /* ------------------ */
        /* --  EVENEMENTS  -- */
        /* ------------------ */
        // notes du consultant/voyant
        if ($this->historique_manager instanceof HistoriqueManager) {
            $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
                $this->historique_manager->getHistoryFromRdv($event->getForm());
            });

            $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $this->historique_manager->createHistoryFromRdv(
                    $event->getForm(),
                    $this->user
                );
            });
        }
        // champs dependant des donnees
        $builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'onPreSetData'));
        // autres evenements
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            $rdv = $form->getData();

            $igni_config = [
                'class' => 'KGCRdvBundle:EnvoiProduit',
                'property' => 'libelle',
                'required' => false,
            ];

            if ($rdv->getAllumage() === null) {
                $igni_config = $igni_config + [
                        'query_builder' => function ($er) use ($rdv) {
                            return $er->findIgnitionAvailableQB($rdv);
                        },
                    ];
            }
            $form->add('allumage', 'entity', $igni_config);
        });
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $this->sourceGclidValidation($event->getForm());
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();
            if (isset($data['securisation']) && $data['securisation'] !== RDV::SECU_SKIPPED) {
                $this->tpeFieldConf['options']['constraints'] = [new NotBlank(['message' => 'Le TPE est obligatoire (sauf sécurisation "passée").'])];
                $form->add('TPE', $this->tpeFieldConf['type'], $this->tpeFieldConf['options']);
                if (empty($data['TPE'])) {
                    $form->addError(new FormError('Le TPE est obligatoire (sauf sécurisation "passée").'));
                }
            }

            if (isset($data['new_card_send_choice'])) {
                unset($data['cartebancaires']);
                $event->setData($data);
            }
        });
    }

    protected function sourceGclidValidation($form)
    {
        $rdv = $form->getData();
        $source = $rdv->getSource();

        if ($source && Source::SOURCE_ADWORDS !== $rdv->getSource()->getCode() || !$source) {
            $rdv->setGclid(null);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\RdvBundle\Entity\RDV',
            'cascade_validation' => true,
            'consultation' => true,
            'validation_groups' => array('Default'),
            'mainaction' => null
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_RdvBundle_rdv';
    }

    /**
     * onPreSetData : Event Listener Action.
     *
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event)
    {
        $field_def = $this->field_defs;
        $currentProfils = $this->user->getProfils();
        $form = $event->getForm();
        $rdv = $event->getData();
        $date_rdv = $rdv->getDateConsultation();
        // -- support
        $field_def['support']['options']['query_builder'] = function (SupportRepository $support_rep) use ($currentProfils, $date_rdv) {
            return $support_rep->findByProfilesQB($currentProfils, true, $date_rdv);
        };
        $form->add('support', $field_def['support']['type'], $field_def['support']['options']);
        // -- codepromo
        $field_def['codepromo']['options']['query_builder'] = function (CodePromoRepository $cpm_rep) use ($date_rdv) {
            return $cpm_rep->findAllQB(true, $date_rdv);
        };
        $form->add('codepromo', $field_def['codepromo']['type'], $field_def['codepromo']['options']);
        // -- website
        $field_def['website']['options']['query_builder'] = function (WebsiteRepository $web_rep) use ($date_rdv) {
            return $web_rep->findIsChatQB(false, true, $date_rdv);
        };
        $form->add('website', $field_def['website']['type'], $field_def['website']['options']);
        // -- voyant
        $field_def['voyant']['options']['query_builder'] = function (VoyantRepository $voy_rep) use ($date_rdv) {
            return $voy_rep->findAllHavingTarificationQB(true, $date_rdv);
        };
        $form->add('voyant', $field_def['voyant']['type'], $field_def['voyant']['options']);

        if ($this->edit_classement) {
            $classement = $rdv->getClassement();
            $litige = $rdv->getCloture() === false ? true : false;

            $field_def['classement']['options']['query_builder'] = function (DossierRepository $classement_rep) use ($classement) {
                return $classement_rep->getDossiersSwitchTiroirQB($classement, true);
            };
            $field_def['classement']['options']['data'] = $classement;
            $field_def['litige']['options']['data'] = $litige;

            $form->add('classement', $field_def['classement']['type'], $field_def['classement']['options']);
            $form->add('litige', $field_def['litige']['type'], $field_def['litige']['options']);
        }

        if (
            $form->has('securisation')
            && ($preAuthorization = $rdv->getPreAuthorization())
        ) {
            $capturedAmount = $preAuthorization->getCapturedAmount();
            if ($capturedAmount > 0) {
                $suffix = sprintf('Utilisée (%d/%d €)', $capturedAmount, $preAuthorization->getAuthorizedAmount());
            } else if ($capturedAmount !== null && $capturedAmount == 0) {
                $suffix = sprintf('Annulée (%d €)', $preAuthorization->getAuthorizedAmount());
            } else {
                $suffix = sprintf('Faite (%d €)', $preAuthorization->getAuthorizedAmount());

                if ($preAuthorization->getAuthorizePayment()->getTpe()->isCancellable()) {
                    $field_def['securisation']['options']['attr']['class'] = 'securisation-preauth-cancellable';
                    $field_def['securisation']['options'] += [
                        'action_button' => 'Annuler',
                        'action_attr' => [
                            'class' => 'btn btn-sm btn-danger authorization-cancel',
                            'type' => 'button',
                            'data-ajax-url' => ['kgc_secu_cancel_preauth', ['id' => $rdv->getId()]]
                        ]
                    ];
                }
            }

            if (isset($field_def['securisation']['options']['choices'])) {
                $field_def['securisation']['options']['choices'][RDV::SECU_DONE] = $suffix;
            }
            $isDisabled = $form->get('securisation')->isDisabled();

            $form->add('securisation', $field_def['securisation']['type'], ['disabled' => $isDisabled] + $field_def['securisation']['options']);
        }

        if ($form->has('TPE') && $rdv->getTpe() && !$rdv->getTpe()->getEnabled()) {
            $field_def['TPE']['options']['query_builder'] = function (TPERepository $repo) use ($rdv) {
                return $repo->findAllBackofficeQB(true)
                    ->orWhere('t.id = :id')->setParameter('id', $rdv->getTpe()->getId());
            };

            $form->add('TPE', $field_def['TPE']['type'], $field_def['TPE']['options']);
        }

        if (count($rdv->getCartebancaires()) == 0) {
            if ($rdv->getNewCardHash()) {
                $form->add('new_card_send_checkbox', 'checkbox', [
                    'mapped' => false,
                    'required' => false,
                    'label' => 'Envoyer un lien de paiement au client'
                ]);

                $form->add('new_card_send_by_mail', 'button', [
                    'attr' => ['type' => 'button'],
                    'label' => 'Envoyer par email'
                ]);
            } else {
                $form->add('new_card_send_choice', 'choice', [
                    'mapped' => false,
                    'required' => false,
                    'expanded' => true,
                    'multiple' => true,
                    'choices' => [
                        'mail' => 'Envoyer le lien par mail',
                        'sms' => 'Envoyer le lien par sms'
                    ]
                ]);

                $field_def['cartebancaires']['options'] = [
                        'disabled' => $form->get('cartebancaires')->isDisabled(),
                        'required' => false,
                    ] + $field_def['cartebancaires']['options'];

                $form->add('cartebancaires', $field_def['cartebancaires']['type'], $field_def['cartebancaires']['options']);
            }
        }

        foreach ($rdv->getEncaissements() as $enc) {
            if ($enc->getBatchRetryFrom() !== null && $enc->getEtat() == Encaissement::DENIED) {
                $rdv->removeEncaissements($enc);
            }
        }
    }

    /**
     * addAction : ajoute une valeur au champ action (définit les actions à enregistrer dans l'historique).
     *
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array(\KGC\RdvBundle\Entity\ActionSuivi) $new_actions
     */
    protected function addAction(FormBuilderInterface $builder, array $new_actions)
    {
        $actions_field = $builder->get('actions');
        $choices = $actions_field->getOption('choices') + $new_actions;
        $options = array_merge($actions_field->getOptions(), array('choices' => $choices));
        $builder->add('actions', $actions_field->getType()->getName(), $options);
    }

    /**
     * enableEditFields : permet la modification des champs de consultaion selon edit_params.
     *
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     */
    private function enableEditFields(FormBuilderInterface $builder)
    {
        foreach ($builder->all() as $childformbuilder) {
            $this->recursiveDisableFields($childformbuilder);
        }
        foreach ($this->edit_params as $key => $value) {
            $fields = explode(':', $key);
            foreach ($fields as $field) {
                $field_access = explode('_', $field);
                $form = $builder;
                foreach ($field_access as $nom) {
                    $form = $form->get($nom);
                    $form->setDisabled(false);
                }
                $this->recursiveEnableFields($form); // active tous les enfants du formulaires s'il y en a.
                unset($form);
            }
        }
    }

    /**
     * recurisveDisableFields : désactive récursivement tous les formulaires/champs du builder en paramètre.
     *
     * @param \Symfony\Component\Form\FormBuilderInterface $formBuilder
     */
    protected function recursiveDisableFields(FormBuilderInterface $formBuilder)
    {
        $childs = $formBuilder->all();
        foreach ($childs as $formchildBuilder) {
            $this->recursiveDisableFields($formchildBuilder);
        }
        $formBuilder->setDisabled(true);
        $name = $formBuilder->getName();
        if (isset($this->field_defs[$name]) && $name != 'cartebancaires_selected') {
            $this->field_defs[$name]['options']['disabled'] = true;
        }
    }

    /**
     * recurisveEnableFields : active récursivement tous les formulaires/champs du builder en paramètre.
     *
     * @param \Symfony\Component\Form\FormBuilderInterface $formBuilder
     */
    protected function recursiveEnableFields(FormBuilderInterface $formBuilder)
    {
        $childs = $formBuilder->all();
        foreach ($childs as $formchildBuilder) {
            $this->recursiveEnableFields($formchildBuilder);
        }
        $formBuilder->setDisabled(false);
        $name = $formBuilder->getName();
        if (isset($this->field_defs[$name])) {
            $this->field_defs[$name]['options']['disabled'] = false;
        }
    }
}
