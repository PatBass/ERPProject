<?php

namespace KGC\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UtilisateurPosteType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('poste', 'entity', [
                'class' => 'KGCUserBundle:Poste',
                'property' => 'name',
                'empty_value' => '',
                'required' => false
            ])
            ->add('fermeture', 'checkbox', array(
                'mapped' => false,
                'required' => false,
                'checked_label_style' => false,
                'data' => true
            ));
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
        return 'kgc_userbundle_utilisateur_poste';
    }
}
