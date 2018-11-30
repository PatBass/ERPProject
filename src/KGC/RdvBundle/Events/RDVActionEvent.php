<?php

// src/KGC/RdvBundle/Events/RDVActionEvent.php
namespace KGC\RdvBundle\Events;

use KGC\UserBundle\Entity\Utilisateur;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\Form;
use KGC\RdvBundle\Entity\RDV;
use KGC\RdvBundle\Entity\Encaissement;

/**
 * RDVActionEvent
 * Evènement d'action sur consultation.
 *
 * @category Events
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class RDVActionEvent extends Event
{
    /**
     * @var \KGC\RdvBundle\Entity\RDV
     */
    protected $rdv;

    /**
     * @var Symfony\Component\Form\Form
     */
    protected $form;

    /**
     * @var \KGC\UserBundle\Entity\Utilisateur
     */
    protected $user;

    /**
     * @var \KGC\UserBundle\Entity\Utilisateur
     */
    protected $enc = null;

    /**
     * @param RDV         $rdv
     * @param Utilisateur $user
     * @param Form|null   $form
     */
    public function __construct(RDV $rdv, Utilisateur $user, Form $form = null)
    {
        $this->rdv = $rdv;
        $this->form = $form;
        $this->user = $user;
        if ($form !== null && $form->has('encaissement')) {
            $this->enc = $form['encaissement']->getData();
        }
    }

    /**
     * @return \KGC\RdvBundle\Entity\RDV
     */
    public function getRDV()
    {
        return $this->rdv;
    }

    /**
     * @return Symfony\Component\Form\Form
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @param \KGC\RdvBundle\Entity\RDV $rdv
     *
     * @return \KGC\RdvBundle\Entity\RDV $rdv
     */
    public function setRDV(RDV $rdv)
    {
        return $this->rdv = $rdv;
    }

    /**
     * @return \KGC\RdvBundle\Entity\Encaissement $enc
     */
    public function getEncaissement()
    {
        return $this->enc;
    }

    /** 
     * @param \KGC\RdvBundle\Entity\Encaissement $enc
     *
     * @return \KGC\RdvBundle\Entity\Encaissement $enc
     */
    public function setEncaissement(Encaissement $enc)
    {
        return $this->enc = $enc;
    }

    /**
     * @return \KGC\Bundle\SharedBundle\Entity\Client $enc
     */
    public function getClient()
    {
        return $this->rdv ? $this->rdv->getClient() : null;
    }

    /**
     * @return Utilisateur
     */
    public function getUser()
    {
        return $this->user;
    }
}
