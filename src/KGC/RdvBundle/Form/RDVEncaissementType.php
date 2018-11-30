<?php

// src/KGC/RdvBundle/Form/RDVEncaissementType.php


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
class RDVEncaissementType extends RDVType
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
    public function __construct(\KGC\UserBundle\Entity\Utilisateur $user, \KGC\RdvBundle\Entity\Encaissement $encaissement, EntityManager $em, $edit_params = array(), $cbMasked = false, $decrypt = false)
    {
        parent::__construct($user, $edit_params, $em, $edit_params, true, $cbMasked, $decrypt);
        $this->encaissement = $encaissement;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->add('encaissement', new ProcessEncaissementType($this->em, $this->encaissement->getConsultation(), false, true, [], true), array(
            'data' => $this->encaissement,
            'mapped' => false,
        ));
        $this->changeOptions($builder, 'encaissements', ['type' => new EncaissementType(true, false, [$this->encaissement])]);

        $builder->get('mainaction')->setData(ActionSuivi::PROCEED_PAYMENT);
        $builder->remove('notes');
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);
        $resolver->setDefaults(array(
            'validation_groups' => array('Default', 'facturation'),
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
