<?php

// src/KGC/RdvBundle/Form/RDVSmsType.php


namespace KGC\RdvBundle\Form;

use KGC\ClientBundle\Form\MailSendType;
use KGC\ClientBundle\Form\SmsSendType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityManager;
use KGC\RdvBundle\Entity\ActionSuivi;

/**
 * Class RDVSmsType.
 */
class RDVSmsType extends RDVType
{
    private $phone;
    protected $tchat = 0;
    /**
     * Constructeur.
     *
     * @param \KGC\UserBundle\Entity\Utilisateur $user
     */
    public function __construct(\KGC\UserBundle\Entity\Utilisateur $user, EntityManager $em, $edit_params = array(), $phone, $tchat = 0)
    {
        parent::__construct($user, $edit_params, $em);
        $this->phone = $phone;
        $this->tchat = $tchat;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->add('sms_sent', new SmsSendType($this->phone, $this->tchat), [
            'mapped' => false,
        ]);

        $builder->get('mainaction')->setData(ActionSuivi::SEND_SMS);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_RdvBundle_rdv';
    }
}
