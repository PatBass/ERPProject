<?php

// src/KGC/RdvBundle/Form/RDVAnnulerType.php


namespace KGC\RdvBundle\Form;

use KGC\ClientBundle\Form\MailSendType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityManager;
use KGC\RdvBundle\Entity\ActionSuivi;

/**
 * Class RDVMailType.
 */
class RDVMailType extends RDVType
{
    protected $tchat = 0;
    /**
     * Constructeur.
     *
     * @param \KGC\UserBundle\Entity\Utilisateur $user
     */
    public function __construct(\KGC\UserBundle\Entity\Utilisateur $user, EntityManager $em, $edit_params = array(), $tchat = 0)
    {
        parent::__construct($user, $edit_params, $em);
        $this->tchat = $tchat;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->add('mail_sent', new MailSendType($this->tchat), [
            'mapped' => false,
        ]);

        $builder->get('mainaction')->setData(ActionSuivi::SEND_MAIL);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_RdvBundle_rdv';
    }
}
