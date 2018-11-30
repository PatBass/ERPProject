<?php

// src/KGC/RdvBundle/Events/EncaissementEvent.php
namespace KGC\RdvBundle\Events;

use KGC\UserBundle\Entity\Utilisateur;
use Symfony\Component\Form\Form;
use KGC\RdvBundle\Entity\RDV;
use KGC\RdvBundle\Entity\Encaissement;

/**
 * EncaissementEvent
 * Evènement sur un encaissement.
 *
 * @category Events
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class EncaissementEvent extends RDVActionEvent
{
    /**
     * @var \KGC\RdvBundle\Entity\RDV
     */
    protected $rdv;

    /**
     * @var \KGC\RdvBundle\Entity\Encaissement
     */
    protected $enc;

    /**
     * @param RDV          $rdv
     * @param Utilisateur  $user
     * @param Form         $form
     * @param Encaissement $enc
     */
    public function __construct(RDV $rdv, Utilisateur $user, Form $form, Encaissement $enc)
    {
        parent::__construct($rdv, $user, $form);
        $this->enc = $enc;
    }

    /**
     * Le listener a accès au rdv.
     *
     * @return \KGC\RdvBundle\Entity\RDV $rdv
     */
    public function getRDV()
    {
        return $this->rdv;
    }

    /**
     * Le listener peut modifier le rdv.
     *
     * @param \KGC\RdvBundle\Entity\RDV $rdv
     *
     * @return \KGC\RdvBundle\Entity\RDV $rdv
     */
    public function setRDV(RDV $rdv)
    {
        return $this->rdv = $rdv;
    }

    /**
     * Le listener a accès à l'encaissement.
     *
     * @return \KGC\RdvBundle\Entity\Encaissement $enc
     */
    public function getEncaissement()
    {
        return $this->enc;
    }

    /**
     * Le listener peut modifier l'encaissement.
     *
     * @param \KGC\RdvBundle\Entity\Encaissement $enc
     *
     * @return \KGC\RdvBundle\Entity\Encaissement $enc
     */
    public function setEncaissement(Encaissement $enc)
    {
        return $this->enc = $enc;
    }
}
