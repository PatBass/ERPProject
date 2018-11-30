<?php

// src/KGC/RdvBundle/Form/EnvoiProduitType.php


namespace KGC\RdvBundle\Form;

use KGC\RdvBundle\Entity\EnvoiProduit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Constructeur de formulaire pour entité EnvoiProduit.
 *
 * @category Form
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class EnvoiProduitType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('etat', 'choice', array(
                'choices' => [
                    EnvoiProduit::PLANNED => 'Prévu',
                    EnvoiProduit::DONE => 'Fait',
                    EnvoiProduit::CANCELLED => 'Annulé',
                ],
                'choices_attr' => array(
                    EnvoiProduit::PLANNED => array('data-lightclass' => 'label-warning'),
                    EnvoiProduit::DONE => array('data-lightclass' => 'label-success'),
                    EnvoiProduit::CANCELLED => array('data-lightclass' => 'label-danger'),
                ),
                'attr' => ['class' => 'light-on-value'],
            ))
            ->add('date', 'date', array(
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'date-picker' => true,
                'required' => false,
            ))
            ->add('commentaire', 'text', array(
                'required' => false,
                'attr' => array(
                    'class' => 'commentaire',
                    'placeholder' => ' Ajouter un commentaire',
                ),
            ))
            ->add('allumage', 'choice', array(
                'choices' => [
                    EnvoiProduit::IGNITION_NONE => 'Non',
                    EnvoiProduit::IGNITION_DONE => 'Fait',
                    EnvoiProduit::IGNITION_PLANNED => 'En attente',
                ],
                'choices_attr' => array(
                    EnvoiProduit::IGNITION_PLANNED => array('data-lightclass' => 'label-warning'),
                    EnvoiProduit::IGNITION_DONE => array('data-lightclass' => 'label-success'),
                    EnvoiProduit::IGNITION_NONE => array('data-lightclass' => 'label-light'),
                ),
                'attr' => ['class' => 'light-on-value'],
            ))
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\RdvBundle\Entity\EnvoiProduit',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_RdvBundle_envoiProduit';
    }
}
