<?php

// src/KGC/RdvBundle/Entity/MoyenPaiement.php


namespace KGC\RdvBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;

/**
 * Class MoyenPaiement.
 *
 * @ORM\Table(name="moyenpaiement")
 * @ORM\Entity(repositoryClass="KGC\RdvBundle\Repository\MoyenPaiementRepository")
 */
class MoyenPaiement
{
    use ORMBehaviors\Timestampable\Timestampable;

    /**
     * @var int
     * @ORM\Column(name="mpm_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="mpm_idcode", type="string", length=30, nullable=false)
     */
    protected $idcode;

    /**
     * @var string
     * @ORM\Column(name="mpm_libelle", type="string", length=20)
     * @Assert\NotBlank()
     */
    protected $libelle;

    /**
     * @var bool
     * @ORM\Column(name="mpm_enabled", type="boolean" )
     */
    protected $enabled = true;

    /**
     * @var date
     * @ORM\Column(name="mpm_disabled_date", type="date", nullable=true)
     */
    protected $disabled_date = null;

    const DEBIT_CARD = 'DEBIT_CARD';
    const CHEQUE = 'CHEQUE';
    const TRANSFER = 'TRANSFER';

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
}
