<?php

// src/KGC/RdvBundle/Form/RDVAnnulerType.php


namespace KGC\RdvBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityManager;
use KGC\RdvBundle\Entity\ActionSuivi;
use KGC\RdvBundle\Repository\DossierRepository;

/**
 * Constructeur de formulaire d'annulation de consultation.
 *
 * @category Form
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class RDVAnnulerType extends RDVType
{
    /**
     * Constructeur.
     *
     * @param \KGC\UserBundle\Entity\Utilisateur $user
     */
    public function __construct(\KGC\UserBundle\Entity\Utilisateur $user, EntityManager $em, $edit_params = array())
    {
        parent::__construct($user, $edit_params, $em);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->add('dossier_annulation', 'entity', array(
                    'class' => 'KGCRdvBundle:Dossier',
                    'property' => 'libelle',
                    'query_builder' => function (DossierRepository $classement_rep) {
                        return $classement_rep->getDossiersMotifAnnulationQB(true);
                    },
                    'mapped' => false,
                    'expanded' => true,
                    'empty_value' => false,
                ));
        $builder->get('mainaction')->setData(ActionSuivi::CANCEL_CONSULT);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_RdvBundle_rdv';
    }
}
