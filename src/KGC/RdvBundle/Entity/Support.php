<?php

// src/KGC/RdvBundle/Entity/Support.php


namespace KGC\RdvBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use KGC\UserBundle\Entity\Profil;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;

/**
 * Entité Support : provenance des consultations.
 *
 * @category Entity
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 *
 * @ORM\Table(name="support")
 * @ORM\Entity(repositoryClass="KGC\RdvBundle\Repository\SupportRepository")
 */
class Support
{
    use ORMBehaviors\Timestampable\Timestampable;

    /**
     * @var int
     * @ORM\Column(name="sup_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="sup_libelle", type="string", length=50)
     */
    protected $libelle;

    /**
     * @var int
     * @ORM\Column(name="sup_idtracker", nullable=true, type="integer")
     */
    protected $idtracker = null;

    /**
     * @var bool
     * @ORM\Column(name="sup_enabled", type="boolean")
     */
    protected $enabled = true;

    /**
     * @var date
     * @ORM\Column(name="sup_disabled_date", type="date", nullable=true)
     */
    protected $disabled_date = null;

    /**
     * @var string
     * @ORM\Column(name="sup_idcode", type="string", length=30, nullable=true)
     */
    protected $idcode = null;

    /**
     * @var ArrayCollection(KGC\UserBundle\Entity\Profil)
     * @ORM\ManyToMany(targetEntity="KGC\UserBundle\Entity\Profil", inversedBy="supports")
     * @ORM\JoinTable(name="profil_support",
     *   joinColumns={@ORM\JoinColumn(name="support_id", referencedColumnName="sup_id")},
     *   inverseJoinColumns={@ORM\JoinColumn(name="profil_id", referencedColumnName="pro_id")}
     * )
     */
    protected $profils;

    const SUIVI_CLIENT = 'SUIVI_CLIENT';

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->profils = new ArrayCollection();
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
     * Set libelle.
     *
     * @param string $libelle
     *
     * @return \KGC\RdvBundle\Entity\Support
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
     * Set idtracker.
     *
     * @param int $idtracker
     *
     * @return \KGC\RdvBundle\Entity\Support
     */
    public function setIdtracker($idtracker)
    {
        $this->idtracker = $idtracker;

        return $this;
    }

    /**
     * Get idtracker.
     *
     * @return int
     */
    public function getIdtracker()
    {
        return $this->idtracker;
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
     * Add profils.
     *
     * @param \KGC\UserBundle\Entity\Profil $profil
     *
     * @return \KGC\RdvBundle\Entity\Support
     */
    public function addProfils(Profil $profil)
    {
        $profil->addSupports($this);
        $this->profils[] = $profil;

        return $this;
    }

    /**
     * Remove profils.
     *
     * @param \KGC\UserBundle\Entity\Profil $profils
     */
    public function removeProfil(Profil $profils)
    {
        $this->profils->removeElement($profils);
    }

    /**
     * Get profils.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProfils()
    {
        return $this->profils;
    }

    public function __toString()
    {
        return sprintf('%s', $this->libelle);
    }
}
