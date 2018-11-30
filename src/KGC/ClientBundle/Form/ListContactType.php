<?php

namespace KGC\ClientBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ListContactType extends AbstractType
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
            ->add('submitFile', 'file', array(
                'label' => 'Fichier Excel (.csv)',
                'required' => false,
                'attr' => array('class' => 'js-file')
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
            'data_class' => 'KGC\ClientBundle\Entity\ListContact',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_clientbundle_listcontact';
    }
}
