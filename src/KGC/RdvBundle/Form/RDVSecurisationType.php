<?php

// src/KGC/RdvBundle/Form/RDVSecurisationType.php


namespace KGC\RdvBundle\Form;

use KGC\RdvBundle\Entity\RDV;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Doctrine\ORM\EntityManager;
use KGC\RdvBundle\Entity\ActionSuivi;
use KGC\RdvBundle\Entity\TPE;
use KGC\RdvBundle\Repository\DossierRepository;
use Symfony\Component\Validator\Constraints\NotBlank;
use Zend\Stdlib\ArrayUtils;

/**
 * Constructeur de formulaire de sécurisation pour entité RDV (consultations).
 *
 * @category Form
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class RDVSecurisationType extends RDVType
{
    protected $securisationOptions;

    /**
     * Constructeur.
     *
     * @param \KGC\UserBundle\Entity\Utilisateur $user
     */
    public function __construct(\KGC\UserBundle\Entity\Utilisateur $user, EntityManager $em, $edit_params = array(), $securisationOptions = [], $cbMasked = false, $decrypt = false)
    {
        parent::__construct($user, $edit_params, $em, null, false, $cbMasked, $decrypt);

        $this->securisationOptions = $securisationOptions;
    }

    protected function getTpeFieldConf()
    {
        $tpeRepo = $this->em->getRepository('KGCRdvBundle:TPE');

        $tpeWithSecuAllowed = $tpeRepo->getTpeIdsWithPaymentGateway(true);
        $tpeWithPreAuthAllowed = $tpeRepo->getTpeIdsForPreAuth(true);

        return ArrayUtils::merge(
            parent::getTpeFieldConf(),
            [
                'options' => [
                    'attr' => [
                        'class' => 'securisation'
                    ],
                    'action_button' => 'Payer',
                    'action_attr' => [
                        'class' => 'btn btn-sm btn-danger securisation-payment',
                        'type' => 'submit',
                        'data-secu-allowed' => implode($tpeWithSecuAllowed, '|'),
                        'data-preauth-allowed' => implode($tpeWithPreAuthAllowed, '|')
                    ]
                ]
            ]
        );
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $this->recursiveEnableFields($builder->get('TPE'));

        $this->field_defs['securisation'] = [
            'type' => 'checkbox',
            'options' => [
                'switch_style' => 5,
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'securisation-checkbox'
                ]
            ]
        ];

        $builder
            ->add('securisation', $this->field_defs['securisation']['type'], $this->field_defs['securisation']['options'])
            ->add('preauthorization', 'choice', array(
                'choices' => [
                    300 => '300 €',
                    150 => '150 €',
                    50 => '50 €',
                    1 => '1 € (sécurisation classique)'
                ],
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'preauthorize-amount'
                ],
                'empty_value' => 'Choisissez un montant...'
            ))
            ->add('skip', 'checkbox', array(
                'switch_style' => 5,
                'mapped' => false,
                'required' => false,
            ))
            ->add('confirmation', 'checkbox', array(
                'mapped' => false,
                'required' => false,
                'switch_style' => 5,
            ))
            ->add('dossier_annulation', 'entity', array(
                'class' => 'KGCRdvBundle:Dossier',
                'property' => 'libelle',
                'query_builder' => function (DossierRepository $classement_rep) {
                    return $classement_rep->getDossiersMotifAnnulationQB(true);
                },
                'mapped' => false,
                'required' => false,
            ));

        $em = $this->em;

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($em) {
            $form = $event->getForm();
            $data = $event->getData();

            if ($data['TPE']) {
                $tpe = $em->find('KGCRdvBundle:TPE', $data['TPE']);
                $isDirectPayment = $tpe instanceof TPE && $tpe->getPaymentGateway() !== null;
            } else {
                $isDirectPayment = false;
            }

            if (!$isDirectPayment && !isset($data['skip']) and !isset($data['confirmation']) and !isset($data['securisation'])) {
                $form->add('dossier_annulation', 'entity', array(
                    'class' => 'KGCRdvBundle:Dossier',
                    'property' => 'libelle',
                    'query_builder' => function (DossierRepository $classement_rep) {
                        return $classement_rep->getDossiersMotifAnnulationQB(true);
                    },
                    'mapped' => false,
                    'required' => false,
                    'constraints' => array(
                        new NotBlank(),
                    ),
                ));
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();

            if (isset($data['securisation']) && $data['securisation'] == '1') {
                $this->tpeFieldConf['options']['constraints'] = [new NotBlank(['message' => 'Pour une sécurisation, veuillez renseigner le TPE.'])];
                $form->add('TPE', $this->tpeFieldConf['type'], $this->tpeFieldConf['options']);
            }

        });
        $builder->get('mainaction')->setData(ActionSuivi::SECURE_BANKDETAILS);
        $builder->remove('notes');
        $builder->remove('tarification');
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_RdvBundle_rdv';
    }
}
