<?php

// src/KGC/RdvBundle/Form/RDVReportType.php


namespace KGC\RdvBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;
use KGC\RdvBundle\Entity\ActionSuivi;

class RDVPauseType extends RDVValidationType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->get('mainaction')->setData(ActionSuivi::PAUSE_CONSULT);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_RdvBundle_rdv';
    }
}
