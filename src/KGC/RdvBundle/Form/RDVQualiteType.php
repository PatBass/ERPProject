<?php

// src/KGC/RdvBundle/Form/RDVQualiteType.php


namespace KGC\RdvBundle\Form;

use KGC\ClientBundle\Entity\Historique;
use KGC\ClientBundle\Service\HistoriqueManager;
use KGC\UserBundle\Entity\Utilisateur;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Constructeur de formulaire de RDV avec Notes de voyance éditables.
 *
 * Class RDVNotesType
 */
class RDVQualiteType extends RDVType
{
    /**
     * @param Utilisateur   $user
     * @param EntityManager $em
     * @param array         $edit_params
     */
    public function __construct(Utilisateur $user, EntityManager $em, HistoriqueManager $historiqueManager, $edit_params = array())
    {
        parent::__construct($user, $edit_params, $em, $historiqueManager);
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->get('notes')->setDisabled(false);
        $this->recursiveEnableFields($builder->get('notes')->get('sending'));
        $this->recursiveEnableFields($builder->get('notes')->get('reminder'));
        $this->recursiveEnableFields($builder->get('notes')->get(Historique::TYPE_RECURRENT));
        $this->recursiveEnableFields($builder->get('notes')->get(Historique::TYPE_STOP_FOLLOW));
        $this->recursiveEnableFields($builder->get('qualite'));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_RdvBundle_rdv';
    }
}
