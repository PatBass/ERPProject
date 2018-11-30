<?php

// src/KGC/RdvBundle/Form/ClassementType.php


namespace KGC\RdvBundle\Form;

use KGC\RdvBundle\Repository\DossierRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * ClassementType.
 *
 * @category Form
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class ClassementType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $rdv = $event->getData();
            $classement = $rdv->getClassement();
            $form = $event->getForm();
            $litige = $rdv->getCloture() === false ? true : false;
            $form
                ->add('dossier', 'entity', array(
                    'class' => 'KGCRdvBundle:Dossier',
                    'property' => 'libelle',
                    'query_builder' => function (DossierRepository $classement_rep) use ($classement) {
                        return $classement_rep->getDossiersSwitchTiroir($classement);
                    },
                    'required' => false,
                    'mapped' => false,
                    'expanded' => true,
                    'empty_value' => false,
                    'data' => $rdv->getClassement(),
                ))
                ->add('litige', 'checkbox', array(
                    'mapped' => false,
                    'required' => false,
                    'switch_style' => 5,
                    'data' => $litige,
                ));
        });
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_RdvBundle_classement';
    }
}
