<?php

// src/KGC/UserBundle/Form/UtilisateurDelType.php


namespace KGC\UserBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Constructeur de formulaire de suppression pour entité Utilisateur.
 *
 * @category Form
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class UtilisateurDelType extends UtilisateurType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->remove('date');
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
        return 'kgc_userbundle_utilisateurdelete';
    }
}
