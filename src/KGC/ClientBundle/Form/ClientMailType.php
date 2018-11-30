<?php

// src/KGC/ClientBundle/Form/ClientMailType.php


namespace KGC\ClientBundle\Form;

use KGC\ClientBundle\Form\MailSendType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityManager;
use KGC\RdvBundle\Entity\ActionSuivi;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;

/**
 * Class ClientMailType.
 */
class ClientMailType  extends AbstractType
{
    protected $tchat = 0;
    /**
     * Constructeur.
     *
     */
    public function __construct($tchat = 0)
    {
        $this->tchat = $tchat;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('mail_sent', new MailSendType($this->tchat), [
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
        return 'kgc_ClientBundle_mail';
    }
}
