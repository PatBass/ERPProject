<?php

namespace KGC\ClientBundle\Form;

use Doctrine\ORM\EntityRepository;
use mageekguy\atoum\template\data;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SmsSendType extends AbstractType
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
        $builder
            ->add('sms', 'entity', array(
                'class' => 'KGCClientBundle:Sms',
                'property' => 'formProperty',
                'empty_value' => 'Sms à envoyer au client',
                'label' => 'Sms',
                'required' => false,
                'query_builder' => function (EntityRepository $er) {
                    return $er->getSimpleListQB($this->tchat);
                },
            ))
            ->add('phone', 'text', array(
                'data' => $this->phone,
                'label' => 'Téléphone',
                'required' => true,
            ))
            ->add('text', 'textarea', array(
                'label' => 'Message',
                'required' => true,
                'attr' => ['class' => 'form-control count-sms', 'data-max' => 160, 'rows' => 4],
            ))
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\ClientBundle\Entity\SmsSent',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_clientbundle_smssend';
    }
}
