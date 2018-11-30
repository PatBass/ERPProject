<?php

// src/KGC/RdvBundle/Form/RDVClasserType.php


namespace KGC\RdvBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityManager;
use KGC\RdvBundle\Entity\ActionSuivi;

/**
 * Constructeur de formulaire de classement de consultation.
 *
 * @category Form
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class RDVClasserType extends RDVType
{
    /**
     * Constructeur.
     *
     * @param \KGC\UserBundle\Entity\Utilisateur $user
     */
    public function __construct(\KGC\UserBundle\Entity\Utilisateur $user, EntityManager $em, $edit_params = array())
    {
        parent::__construct($user, $edit_params, $em, null, true);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $this->recursiveEnableFields($builder->get('classement'));
        $this->recursiveEnableFields($builder->get('litige'));

        $builder->get('mainaction')->setData(ActionSuivi::SORT_CONSULT);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_RdvBundle_rdv';
    }
}
