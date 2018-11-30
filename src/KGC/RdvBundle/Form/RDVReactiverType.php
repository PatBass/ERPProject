<?php

// src/KGC/RdvBundle/Form/RDVReactiverType.php


namespace KGC\RdvBundle\Form;

use KGC\UserBundle\Entity\Utilisateur;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityManager;
use KGC\RdvBundle\Entity\ActionSuivi;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Constructeur de formulaire de report de consultation.
 *
 * @category Form
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class RDVReactiverType extends RDVType
{
    /**
     * Constructeur.
     *
     * @param \KGC\UserBundle\Entity\Utilisateur $user
     */
    public function __construct(Utilisateur $user, EntityManager $em, $edit_params = array())
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

        $field = $builder->get('dateConsultation');
        $options = $field->getOptions();
        $type = $field->getType()->getName();
        $options['constraints'] = array(
            new Range(['min' => 'today', 'minMessage' => 'Vous ne pouvez pas reporter une consultation à une date passée.']),
        );
        $builder->add('dateConsultation', $type, $options);

        $this->recursiveEnableFields($builder->get('dateConsultation'));
        $builder->get('mainaction')->setData(ActionSuivi::REACTIVATE_CONSULT);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_RdvBundle_rdv';
    }
}
