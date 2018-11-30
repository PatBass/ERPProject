<?php

// src/KGC/RdvBundle/Entity/TPE.php


namespace KGC\RdvBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;

/**
 * Class TPE.
 *
 * @category Entity
 *
 * @ORM\Table(name="tpe")
 * @ORM\Entity(repositoryClass="KGC\RdvBundle\Repository\TPERepository")
 */
class TPE
{
    const PAYMENT_GATEWAY_BE2BILL = 'be2bill';
    const PAYMENT_GATEWAY_KLIKANDPAY = 'klikandpay';
    const PAYMENT_GATEWAY_HIPAY = 'hipay';
    const PAYMENT_GATEWAY_HIPAY_TCHAT = 'hipay_tchat';
    const PAYMENT_GATEWAY_HIPAY_MOTO2 = 'hipay_moto2';
    const PAYMENT_GATEWAY_HIPAY_MOTO3 = 'hipay_moto3';

    use ORMBehaviors\Timestampable\Timestampable;

    /**
     * @var int
     * @ORM\Column(name="tpe_id", columnDefinition="TINYINT(3) UNSIGNED NOT NULL")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="tpe_libelle", type="string", length=20)
     * @Assert\NotBlank()
     */
    protected $libelle;

    /**
     * @var bool
     * @ORM\Column(name="tpe_enabled", type="boolean" )
     */
    protected $enabled = true;

    /**
     * @var string
     * @ORM\Column(name="tpe_payment_gateway", type="string", length=20, nullable=true)
     */
    protected $paymentGateway;

    /**
     * @var date
     * @ORM\Column(name="tpe_disabled_date", type="date", nullable=true)
     */
    protected $disabled_date = null;

    /**
     * @var boolean
     * @ORM\Column(name="tpe_has_telecollecte", type="boolean", nullable=false)
     */
    protected $hasTelecollecte = false;

    /**
     * @var bool
     * @ORM\Column(name="tpe_available_for_backoffice", type="boolean")
     */
    protected $availableForBackoffice;

    /**
     * @var bool
     * @ORM\Column(name="tpe_available_for_tchat", type="boolean")
     */
    protected $availableForTchat;

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
     * @param $libelle
     *
     * @return $this
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
     * @param bool $actif
     *
     * @return $this
     */
    public function setEnabled($actif)
    {
        $this->enabled = $actif;
        if (!$actif) {
            $this->setDisabledDate(new \Datetime());
        } else {
            $this->setDisabledDate(null);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param \DateTime $date
     *
     * @return $this
     */
    public function setDisabledDate($date)
    {
        $this->disabled_date = $date;

        return $this;
    }

    /**
     * @return \Datetime
     */
    public function getDisabledDate()
    {
        return $this->disabled_date;
    }

    public function __toString()
    {
        return $this->libelle;
    }

    /**
     * @param string $paymentGateway
     *
     * @return TPE
     */
    public function setPaymentGateway($paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;

        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentGateway()
    {
        return $this->paymentGateway;
    }

    public function getDelay()
    {
        return $this->paymentGateway == self::PAYMENT_GATEWAY_KLIKANDPAY ? 10 : null;
    }

    public function isCancellable()
    {
        return in_array($this->paymentGateway, [self::PAYMENT_GATEWAY_HIPAY, self::PAYMENT_GATEWAY_HIPAY_TCHAT, self::PAYMENT_GATEWAY_HIPAY_MOTO2, self::PAYMENT_GATEWAY_HIPAY_MOTO3]);
    }

    /**
     * Set availableForBackoffice
     *
     * @param boolean $availableForBackoffice
     *
     * @return TPE
     */
    public function setAvailableForBackoffice($availableForBackoffice)
    {
        $this->availableForBackoffice = $availableForBackoffice;

        return $this;
    }

    /**
     * Get availableForBackoffice
     *
     * @return boolean
     */
    public function getAvailableForBackoffice()
    {
        return $this->availableForBackoffice;
    }

    /**
     * Set availableForTchat
     *
     * @param boolean $availableForTchat
     *
     * @return TPE
     */
    public function setAvailableForTchat($availableForTchat)
    {
        $this->availableForTchat = $availableForTchat;

        return $this;
    }

    /**
     * Get availableForTchat
     *
     * @return boolean
     */
    public function getAvailableForTchat()
    {
        return $this->availableForTchat;
    }

    /**
     * @param boolean $has_telecollecte
     *
     * @return TPE
     */
    public function setHasTelecollecte($has_telecollecte)
    {
        $this->hasTelecollecte = $has_telecollecte;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getHasTelecollecte()
    {
        return $this->hasTelecollecte;
    }
}
