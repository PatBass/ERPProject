<?php

// src/KGC/UserBundle/Form/UtilisateurType.php


namespace KGC\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use KGC\UserBundle\Entity\Utilisateur;

/**
 * Constructeur de formulaire pour entité Utilisateur.
 *
 * @category Form
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class UtilisateurType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', 'text')
            ->add('password', 'repeated', [
                'type' => 'password',
                'invalid_message' => 'Les mots de passe doivent correspondre.',
                'options' => ['required' => false],
                'first_options' => ['label' => 'Mot de passe'],
                'second_options' => ['label' => 'Confirmation du mot de passe'],
            ])
            ->add('mainProfil', 'entity', [
                'class' => 'KGCUserBundle:Profil',
                'property' => 'name',
            ])
            ->add('chatType', 'entity', [
                'class' => 'KGCChatBundle:ChatType',
                'property' => 'entitled',
                'required' => false,
                'empty_value' => 'Choisissez le type de chat',
            ])
            ->add('sexe', 'choice', [
                'choices' => [
                    Utilisateur::SEXE_MAN => 'Homme',
                    Utilisateur::SEXE_WOMAN => 'Femme',
                ],
            ])
            ->add('profils', 'entity', [
                'class' => 'KGCUserBundle:Profil',
                'property' => 'name',
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('actif', 'checkbox', [
                'switch_style' => 5,
                'required' => false,
            ])
            ->add('fermeture', 'checkbox', [
                'mapped' => false,
                'required' => false,
                'checked_label_style' => false,
            ])
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\UserBundle\Entity\Utilisateur',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_userbundle_utilisateur';
    }
}
