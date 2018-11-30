<?php

namespace KGC\ClientBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class MailSendType extends AbstractType
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
            ->add('mail', 'entity', array(
                'class' => 'KGCClientBundle:Mail',
                'property' => 'formProperty',
                'empty_value' => 'Mail à envoyer au client',
                'label' => 'Mail',
                'required' => false,
                'query_builder' => function (EntityRepository $er) {
                    return $er->getSimpleListQB($this->tchat);
                },
            ))
            ->add('subject', 'text', array(
                'label' => 'Objet',
                'required' => true,
            ))
            ->add('html', 'textarea', array(
                'label' => 'Message',
                'required' => true,
                'attr' => ['class' => 'hidden'],
            ))
            ->add('file', 'file', [
                'attr' => ['class' => 'js-file ace_file_input'],
                'required' => false,
            ])
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\ClientBundle\Entity\MailSent',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_clientbundle_mailsend';
    }
}
