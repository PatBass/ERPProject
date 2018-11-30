<?php

// src/KGC/UserBundle/Form/JournalType.php


namespace KGC\UserBundle\Form;

use KGC\UserBundle\Entity\Journal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Constructeur de formulaire pour entité Journal.
 *
 * @category Form
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class JournalType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', 'choice', array(
                'choices' => [
                    Journal::ABSENCE => 'Absence',
                    Journal::LATENESS => 'Retard',
                ],
                'expanded' => true,
            ))
            ->add('date', 'date', array(
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'date-picker' => true,
                'input-mask' => true,
            ))
            ->add('commentaire', 'text', array(
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ajouter un commentaire',
                ],
            ))
            ->add('fermeture', 'checkbox', array(
                'mapped' => false,
                'required' => false,
                'checked_label_style' => false,
            ))
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\UserBundle\Entity\Journal',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_userbundle_journal';
    }
}
