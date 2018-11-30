<?php

namespace KGC\ClientBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class MailType extends AbstractType
{
    protected $tchat = 0;
    /**
     * Constructeur.
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
        $builder
            ->add('code', 'text', array(
                'label' => 'Code',
                'required' => true,
            ))
            ->add('enabled', 'checkbox', [
                'required' => false,
                'switch_style' => 5,
            ])
            ->add('subject', 'text', array(
                'label' => 'Objet',
                'required' => true,
            ))
            ->add('html', 'textarea', array(
                'label' => 'Message',
                'required' => true,
                'attr' => ['class' => 'hidden js-wysiwyg-content'],
            ))
            ->add('tchat', 'hidden', array(
                'data' => $this->tchat,
            ));
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\ClientBundle\Entity\Mail',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_clientbundle_mail';
    }
}
