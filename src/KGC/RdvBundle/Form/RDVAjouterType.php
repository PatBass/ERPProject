<?php

// src/KGC/RdvBundle/Form/RDVAjouterType.php


namespace KGC\RdvBundle\Form;

use Doctrine\ORM\EntityManager;
use KGC\RdvBundle\Entity\ActionSuivi;
use KGC\RdvBundle\Entity\Etat;
use KGC\RdvBundle\Entity\Tiroir;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Constructeur de formulaire d'ajout pour entité RDV (consultations).
 *
 * @category Form
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class RDVAjouterType extends RDVType
{
    /**
     * Constructeur.
     *
     * @param \KGC\UserBundle\Entity\Utilisateur $user
     */
    public function __construct(\KGC\UserBundle\Entity\Utilisateur $user, EntityManager $em, $edit_params = array(), $cbMasked = false, $decrypt = false, $forceHide = false)
    {
        parent::__construct($user, $edit_params, $em, null, false, $cbMasked, $decrypt, $forceHide, true);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $options = array_merge($options, array(
            'consultation' => false,
        ));

        //contrainte sur date
        parent::buildForm($builder, $options);
        $field = $builder->get('dateConsultation');
        $options = $field->getOptions();
        $type = $field->getType()->getName();
        $options['required'] = true;
        $options['constraints'] = array(
            new Range(['min' => 'today', 'minMessage' => 'Vous ne pouvez pas ajouter une consultation à une date passée.']),
        );
        $builder->add('dateConsultation', $type, $options);

        $builder
            ->add('idProspect', 'text', array('required' => false));

        $builder
            ->remove('securisation')
            ->remove('TPE')
            ->remove('notes')
            ->remove('tarification')
            ->remove('encaissements')
            ->remove('envoiProduit')
        ;
        $builder->get('consultant')->setRequired(false);
        $builder->get('mainaction')->setData(ActionSuivi::ADD_CONSULT);
        $em = $this->em;
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) use ($em) {
            $form = $event->getForm();
            $rdv = $event->getData();
            if ($rdv->getPreAuthorization()) {
                $etatCode = Etat::CONFIRMED;
            } else {
                $etatCode = Etat::ADDED;
            }
            $etat = $em->getRepository('KGCRdvBundle:Etat')->findOneByIdcode($etatCode);
            $form->get('etat')->setData($etat); // etat = Enregistré
            $classement = $em->getRepository('KGCRdvBundle:Classement')->findOneByIdcode(Tiroir::PROCESSING);
            $form->add('classement', 'entity', [
                'class' => 'KGCRdvBundle:Classement',
                'data' => $classement,
            ]); // classement = Tiroir EN COURS
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $this->sourceGclidValidation($event->getForm());
        });
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);
        $resolver->setDefaults(array(
            'validation_groups' => array('Default', 'ajout'),
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_RdvBundle_rdv';
    }
}
