<?php

// src/KGC/RdvBundle/Form/RDVNotesType.php


namespace KGC\RdvBundle\Form;

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
class RDVNotesType extends RDVType
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

        $this->recursiveEnableFields($builder->get('notes'));
        $this->recursiveEnableFields($builder->get('notesLibres'));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_RdvBundle_rdv';
    }
}
