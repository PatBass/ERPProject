<?php

// src/KGC/ClientBundle/Form/ClientSmsType.php


namespace KGC\ClientBundle\Form;

use KGC\ClientBundle\Form\MailSendType;
use KGC\ClientBundle\Form\SmsSendType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class ClientSmsType.
 */
class ClientSmsType  extends AbstractType
{
    private $phone;
    protected $tchat = 0;

    /**
     * Constructeur.
     *
     */
    public function __construct($phone, $tchat = 0)
    {
        $this->phone = $phone;
        $this->tchat = $tchat;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('sms_sent', new SmsSendType($this->phone, $this->tchat), [
            'mapped' => false,
        ]);
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\Bundle\SharedBundle\Entity\Client',
            'validation_groups' => array('Default'),
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_ClientBundle_client';
    }
}
