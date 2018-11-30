<?php

namespace KGC\ClientBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CampagneType extends AbstractType
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
            ->add('name', 'text', array(
                'label' => 'Nom',
                'required' => true,
            ))
            ->add('archivage', 'checkbox', [
                'switch_style' => 5,
                'required' => false,
            ])
            ->add('sender', 'text', array(
                'label' => 'Expéditeur',
                'required' => true,
            ))
            ->add('text', 'textarea', array(
                'label' => 'Message',
                'required' => true,
                'attr' => ['class' => 'form-control count-sms', 'data-max' => 160, 'rows' => 4],
            ))
            ->add('list', 'entity', array(
                'class' => 'KGCClientBundle:ListContact',
                'property' => 'formProperty',
                'empty_value' => 'Liste de contact',
                'label' => 'Liste',
                'required' => true,
                'query_builder' => function (EntityRepository $er) {
                    return $er->getSimpleListQB($this->tchat);
                },
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
            'data_class' => 'KGC\ClientBundle\Entity\CampagneSms',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_clientbundle_campagnesms';
    }
}
