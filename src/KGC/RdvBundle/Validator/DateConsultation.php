<?php

// src/Sdz/BlogBundle/Validator/DateConsultation.php


namespace KGC\RdvBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class DateConsultation extends Constraint
{
    public $message = 'Cet horaire n&#039;est pas disponible ou est surchargé.';

    public function validatedBy()
    {
        return 'kgcrdv_dateconsultation_validator'; // Ici, on fait appel à l'alias du service
    }
}
