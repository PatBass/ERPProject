<?php

// src/KGC/RdvBundle/Entity/Encaissement.php


namespace KGC\RdvBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entité Encaissement.
 *
 * @category Entity
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 *
 * @ORM\Table(name="encaissements",
 *     indexes={
 *         @ORM\Index(name="date_enc_idx", columns={"enc_date"}),
 *         @ORM\Index(name="date_oppo_enc_idx", columns={"enc_date_oppo"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="KGC\RdvBundle\Repository\EncaissementRepository")
 */
class Encaissement
{
    /**
     * @var int
     * @ORM\Column(name="enc_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var int
     * @ORM\Column(name="enc_montant", type="integer")
     */
    protected $montant;

    /**
     * @var \DateTime
     * @ORM\Column(name="enc_date", type="datetime")
     */
    protected $date;

    /**
     * @var string
     * @ORM\Column(name="enc_etat", type="string", nullable=false, length=20)
     */
    protected $etat = 'STATE_PLANNED';

    /**
     * @var \KGC\RdvBundle\Entity\RDV
     * @ORM\ManyToOne(targetEntity="\KGC\RdvBundle\Entity\RDV", inversedBy="encaissements")
     * @ORM\JoinColumn(nullable=false, name="enc_consultation", referencedColumnName="rdv_id")
     */
    protected $consultation;

    /**
     * @var moyenPaiement
     * @ORM\ManyToOne(targetEntity="MoyenPaiement")
     * @ORM\JoinColumn(nullable=false, name="enc_moyenpaiement", referencedColumnName="mpm_id")
     */
    protected $moyenPaiement;

    /**
     * @var tpe
     * @ORM\ManyToOne(targetEntity="TPE")
     * @ORM\JoinColumn(nullable=true, name="enc_tpe", referencedColumnName="tpe_id")
     */
    protected $tpe;

    /**
     * @var TPE
     * @ORM\ManyToOne(targetEntity="\KGC\PaymentBundle\Entity\Payment")
     * @ORM\JoinColumn(nullable=true, name="enc_payment", referencedColumnName="id")
     */
    protected $payment;

    /**
     * @var \DateTime
     * @ORM\Column(name="enc_date_oppo", type="date", nullable=true)
     */
    protected $date_oppo = null;

    /**
     * @var \DateTime
     * @ORM\Column(name="enc_psychic_asso", type="boolean")
     */
    protected $psychic_asso = true;

    /**
     * @var bool
     * @ORM\Column(name="enc_from_batch", type="boolean")
     */
    protected $fromBatch = false;

    /**
     * @var Encaissement
     * @ORM\ManyToOne(targetEntity="\KGC\RdvBundle\Entity\Encaissement")
     * @ORM\JoinColumn(nullable=true, name="enc_batch_retry_from", referencedColumnName="enc_id")
     */
    protected $batchRetryFrom;

    const PLANNED = 'STATE_PLANNED';
    const DONE = 'STATE_DONE';
    const DENIED = 'STATE_DENIED';
    const CANCELLED = 'STATE_CANCELLED';
    const REFUNDED = 'STATE_REFUNDED';

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->date = new \DateTime();
    }

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
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Set montant.
     *
     * @param int $montant
     *
     * @return \KGC\RdvBundle\Entity\Encaissement
     */
    public function setMontant($montant)
    {
        $this->montant = $montant * 100;

        return $this;
    }

    /**
     * Get montant.
     *
     * @return int
     */
    public function getMontant()
    {
        return $this->montant / 100;
    }

    /**
     * Set date.
     *
     * @param \DateTime $date
     *
     * @return \KGC\RdvBundle\Entity\Encaissement
     */
    public function setDate($date)
    {
        if ($date !== null) {
            $this->date = $date;
        }

        return $this;
    }

    /**
     * Get date.
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set etat.
     *
     * @param string $etat
     *
     * @return \KGC\RdvBundle\Entity\Encaissement
     */
    public function setEtat($etat)
    {
        if ($this->etat !== null) {
            $this->etat = $etat;
        }

        return $this;
    }

    /**
     * Get etat.
     *
     * @return string
     */
    public function getEtat()
    {
        return $this->etat;
    }

    /**
     * @param RDV $consultation
     *
     * @return $this
     */
    public function setConsultation(RDV $consultation)
    {
        $this->consultation = $consultation;

        return $this;
    }

    /**
     * Get consultation.
     *
     * @return \KGC\RdvBundle\Entity\RDV
     */
    public function getConsultation()
    {
        return $this->consultation;
    }

    /**
     * @param $tpe
     *
     * @return $this
     */
    public function setTpe($tpe)
    {
        $this->tpe = $tpe;

        return $this;
    }

    /**
     * Get tpe.
     *
     * @return \KGC\RdvBundle\Entity\TPE
     */
    public function getTpe()
    {
        return $this->tpe;
    }

    /**
     * @param $moyenPaiement
     *
     * @return $this
     */
    public function setMoyenPaiement($moyenPaiement)
    {
        $this->moyenPaiement = $moyenPaiement;

        return $this;
    }

    /**
     * Get moyenPaiement.
     *
     * @return \KGC\RdvBundle\Entity\MoyenPaiement
     */
    public function getMoyenPaiement()
    {
        return $this->moyenPaiement;
    }

    /**
     * Set dateOppo
     *
     * @param \DateTime $dateOppo
     *
     * @return Encaissement
     */
    public function setDateOppo($dateOppo)
    {
        $this->date_oppo = $dateOppo;

        return $this;
    }

    /**
     * Get dateOppo
     *
     * @return \DateTime
     */
    public function getDateOppo()
    {
        return $this->date_oppo;
    }

    /**
     * @param $psychic_asso
     *
     * @return $this
     */
    public function setPsychicAsso($psychic_asso)
    {
        $this->psychic_asso = $psychic_asso;

        return $this;
    }

    /**
     * Get psychic_asso.
     *
     * @return bool
     */
    public function getPsychicAsso()
    {
        return $this->psychic_asso;
    }

    public function getDateES()
    {
        return date_format($this->date, 'Y-m-d');
    }

    public function getContextColor()
    {
        switch ($this->etat) {
            case self::PLANNED   : return 'warning';
            case self::DONE      : return 'success';
            case self::DENIED    : return 'danger';
            case self::CANCELLED : return 'context-purple';
        }
    }

    /**
     * Set payment
     *
     * @param \KGC\PaymentBundle\Entity\Payment $payment
     *
     * @return Encaissement
     */
    public function setPayment(\KGC\PaymentBundle\Entity\Payment $payment = null)
    {
        $this->payment = $payment;

        return $this;
    }

    /**
     * Get payment
     *
     * @return \KGC\PaymentBundle\Entity\Payment
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * Set batchRetryFrom
     *
     * @param \KGC\RdvBundle\Entity\Encaissement $batchRetryFrom
     *
     * @return Encaissement
     */
    public function setBatchRetryFrom(Encaissement $batchRetryFrom = null)
    {
        $this->batchRetryFrom = $batchRetryFrom;

        return $this;
    }

    /**
     * Get batchRetryFrom
     *
     * @return \KGC\RdvBundle\Entity\Encaissement
     */
    public function getBatchRetryFrom()
    {
        return $this->batchRetryFrom;
    }

    /**
     * Set fromBatch
     *
     * @param boolean $fromBatch
     *
     * @return Encaissement
     */
    public function setFromBatch($fromBatch)
    {
        $this->fromBatch = $fromBatch;

        return $this;
    }

    /**
     * Get fromBatch
     *
     * @return boolean
     */
    public function getFromBatch()
    {
        return $this->fromBatch;
    }
}
