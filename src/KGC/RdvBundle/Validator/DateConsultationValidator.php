<?php

// src/KGC/RdvBundle/Validator/DateConsultationValidator.php


namespace KGC\RdvBundle\Validator;

use KGC\RdvBundle\Service\PlanningService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;

class DateConsultationValidator extends ConstraintValidator
{
    private $request;
    private $em;

    public function __construct(Request $request, EntityManager $em)
    {
        $this->request = $request;
        $this->em = $em;
    }

    public function validate($value, Constraint $constraint)
    {
        if ($value !== null) {
            $rep_rdv = $this->em->getRepository('KGCRdvBundle:RDV');
            $plans = $rep_rdv->getPlanned($value);
            if (count($plans) >= PlanningService::NB_PLAGES) {
                $this->context->addViolation($constraint->message);
            }
        }
    }
}
