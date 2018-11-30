<?php
// src/KGC/RdvBundle/Entity/ActionSuivi.php

namespace KGC\RdvBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entité ActionSuivi : désigne une action effectuée sur un RDV.
 *
 * @category Entity
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 *
 * @ORM\Table(name="actions",
 *      indexes={
 *          @ORM\Index(name="action_code_idx", columns={"act_idcode"}),
 *      })
 * @ORM\Entity()
 */
class ActionSuivi
{
    /**
     * @var int
     * @ORM\Column(name="act_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="act_idcode", type="string", length=30, nullable=false)
     */
    protected $idcode;

    /**
     * @var string
     * @ORM\Column(name="act_libelle", type="string", length=255)
     */
    protected $libelle;

    /**
     * @var string
     * @ORM\Column(name="act_typedonnee", type="string", length=255, nullable=true)
     */
    protected $typeDonnee;

    /**
     * @var string
     * @ORM\Column(name="act_event", type="string", length=30, nullable=true)
     */
    protected $event;

    /**
     * @var bool
     * @ORM\Column(name="act_compact", type="boolean")
     */
    protected $compact = false;

    /**
     * @var GroupeAction
     * @ORM\ManyToOne(targetEntity="\KGC\RdvBundle\Entity\GroupeAction")
     * @ORM\JoinColumn(nullable=true, name="act_groupe", referencedColumnName="gpa_id")
     */
    protected $groupe = null;

    const ADD_CONSULT = 'ADD_CONSULT';
    const SECURE_BANKDETAILS = 'SECURE_BANKDETAILS';
    const CONNECTING_PSYCHIC = 'CONNECTING_PSYCHIC';
    const UPDATE_BANKDETAILS = 'UPDATE_BANKDETAILS';
    const UPDATE_SURNAME = 'UPDATE_SURNAME';
    const UPDATE_NAME = 'UPDATE_NAME';
    const UPDATE_BIRTHDATE = 'UPDATE_BIRTHDATE';
    const UPDATE_PHONE = 'UPDATE_PHONE';
    const UPDATE_MAIL = 'UPDATE_MAIL';
    const UPDATE_ADDRESS = 'UPDATE_ADDRESS';
    const TAKE_CONSULT = 'TAKE_CONSULT';
    const ADD_BILLING = 'ADD_BILLING';
    const POSTPONE_CONSULT = 'POSTPONE_CONSULT';
    const CANCEL_CONSULT = 'CANCEL_CONSULT';
    const SEND_MAIL = 'SEND_MAIL';
    const SEND_SMS = 'SEND_SMS';
    const PROCEED_PAYMENT = 'PROCEED_PAYMENT';
    const UPDATE_BILLING = 'UPDATE_BILLING';
    const PAUSE_CONSULT = 'PAUSE_CONSULT';
    const UPDATE_PRICERANGE = 'UPDATE_PRICERANGE';
    const UPDATE_IDASTRO = 'UPDATE_IDASTRO';
    const CLOSE_CONSULT = 'CLOSE_CONSULT';
    const IMPORT_CONSULT = 'IMPORT_CONSULT';
    const SORT_CONSULT = 'SORT_CONSULT';
    const CANCEL_PAYMENT = 'CANCEL_PAYMENT';
    const REACTIVATE_CONSULT = 'REACTIVATE_CONSULT';
    const DO_CONSULT = 'DO_CONSULT';
    const UPDATE_SUPPORT = 'UPDATE_SUPPORT';
    const UPDATE_WEBSITE = 'UPDATE_WEBSITE';
    const UPDATE_OFFERCODE = 'UPDATE_CODEPROMO';
    const UPDATE_OWNER = 'UPDATE_PROPRIO';
    const UPDATE_PSYCHIC = 'UPDATE_VOYANT';
    const UPDATE_CONSULTANT = 'UPDATE_CONSULTANT';
    const UPDATE_PRODUCT_SENDING = 'UPDATE_PRODUCT_SENDING';
    const UPDATE_GENDER = 'UPDATE_GENDER';
    const UPDATE_SECURISATION = 'UPDATE_SECURISATION';
    const UPDATE_SOURCE = 'UPDATE_SOURCE';
    const UPDATE_GCLID = 'UPDATE_GCLID';
    const UNCLOSE_CONSULT = 'UNCLOSE_CONSULT';
    const UPDATE_FORMURL = 'UPDATE_FORMURL';

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $idcode
     *
     * @return $this
     */
    public function setIdcode($idcode)
    {
        $this->idcode = $idcode;

        return $this;
    }

    /**
     * Get idcode.
     *
     * @return string
     */
    public function getIdcode()
    {
        return $this->idcode;
    }

    /**
     * Set libelle.
     *
     * @param string $libelle
     *
     * @return \KGC\RdvBundle\Entity\ActionSuivi
     */
    public function setLibelle($libelle)
    {
        $this->libelle = $libelle;

        return $this;
    }

    /**
     * Get libelle.
     *
     * @return string
     */
    public function getLibelle()
    {
        return $this->libelle;
    }

    /**
     * Set typeDonnee.
     *
     * @param string $typeDonnee
     *
     * @return \KGC\RdvBundle\Entity\ActionSuivi
     */
    public function setTypeDonnee($typeDonnee)
    {
        $this->typeDonnee = $typeDonnee;

        return $this;
    }

    /**
     * Get typeDonnee.
     *
     * @return string
     */
    public function getTypeDonnee()
    {
        return $this->typeDonnee;
    }

    /**
     * Set event.
     *
     * @param string $event
     *
     * @return \KGC\RdvBundle\Entity\ActionSuivi
     */
    public function setEvent($event)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Get event.
     *
     * @return string
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Set compact.
     *
     * @param string $compact
     *
     * @return \KGC\RdvBundle\Entity\ActionSuivi
     */
    public function setCompact($compact)
    {
        $this->compact = $compact;

        return $this;
    }

    /**
     * Get compact.
     *
     * @return bool
     */
    public function getCompact()
    {
        return $this->compact;
    }

    /**
     * @param GroupeAction $groupe
     *
     * @return $this
     */
    public function setGroupe($groupe)
    {
        $this->groupe = $groupe;

        return $this;
    }

    /**
     * @return GroupeAction
     */
    public function getGroupe()
    {
        return $this->groupe;
    }
}
