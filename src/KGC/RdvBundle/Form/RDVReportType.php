<?php

// src/KGC/RdvBundle/Form/RDVReportType.php


namespace KGC\RdvBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;
use KGC\RdvBundle\Entity\ActionSuivi;

/**
 * Constructeur de formulaire de report de consultation.
 *
 * @category Form
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class RDVReportType extends RDVReactiverType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->get('mainaction')->setData(ActionSuivi::POSTPONE_CONSULT);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_RdvBundle_rdv';
    }
}
