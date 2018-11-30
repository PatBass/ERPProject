<?php

// src/KGC/RdvBundle/Form/RDVOppositionType.php


namespace KGC\RdvBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Doctrine\ORM\EntityManager;
use KGC\RdvBundle\Entity\ActionSuivi;

/**
 * Constructeur de formulaire de validation d'encaissement.
 *
 * @category Form
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class RDVOppositionType extends RDVType
{
    /**
     * @var \KGC\RdvBundle\Entity\Encaissement
     */
    private $encaissement;

    /**
     * Constructeur.
     *
     * @param \KGC\UserBundle\Entity\Utilisateur $user
     */
    public function __construct(\KGC\UserBundle\Entity\Utilisateur $user, \KGC\RdvBundle\Entity\Encaissement $encaissement, EntityManager $em, $edit_params = array())
    {
        parent::__construct($user, $edit_params, $em);
        $this->encaissement = $encaissement;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->add('encaissement', new EncaissementType(), array(
            'data' => $this->encaissement,
            'mapped' => false,
        ));
        $builder->get('mainaction')->setData(ActionSuivi::CANCEL_PAYMENT);
        $builder->remove('notes');
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);
        $resolver->setDefaults(array(
            'validation_groups' => false,
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_RdvBundle_rdv';
    }
}
