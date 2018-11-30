<?php

// src/KGC/RdvBundle/Form/RDVMiserelationType.php


namespace KGC\RdvBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Doctrine\ORM\EntityManager;
use KGC\UserBundle\Entity\Utilisateur;
use KGC\RdvBundle\Entity\ActionSuivi;
use KGC\RdvBundle\Repository\DossierRepository;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Constructeur de formulaire de mise en relation pour entité RDV (consultations).
 *
 * @category Form
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class RDVMiserelationType extends RDVType
{
    /**
     * Constructeur.
     *
     * @param \KGC\UserBundle\Entity\Utilisateur $user
     */
    public function __construct(Utilisateur $user, EntityManager $em, $edit_params = array(), $cbMasked = false, $decrypt = false)
    {
        parent::__construct($user, $edit_params, $em, null, false, $cbMasked, $decrypt);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder
            ->add('miserelation', 'checkbox', array(
                'switch_style' => 5,
                'required' => false,
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
        $this->recursiveEnableFields($builder->get('voyant'));
        $builder->get('voyant')->setRequired(true);
        $this->field_defs['voyant']['options']['required'] = true;
        $this->recursiveEnableFields($builder->get('consultant'));

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();
            if (!isset($data['miserelation'])) {
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

        $builder->get('mainaction')->setData(ActionSuivi::CONNECTING_PSYCHIC);
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
