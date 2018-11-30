<?php

// src/KGC/RdvBundle/Entity/Etiquette.php


namespace KGC\RdvBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use KGC\UserBundle\Entity\Profil;

/**
 * Entité Etiquette.
 *
 * @category Entity
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 *
 * @ORM\Table(name="etiquettes")
 * @ORM\Entity(repositoryClass="KGC\RdvBundle\Repository\EtiquetteRepository")
 */
class Etiquette
{
    const NVP = 'NVP';
    const NRP = 'NRP';
    const CBI = 'CBI';
    const OPPO = 'OPPO';
    const MSG_VOC = 'MSG_VOC';
    const FX_NUM = 'FX_NUM';
    const FX_ADDR = 'FX_ADDR';
    const GEERIM = 'GEERIM';
    const MR_1 = 'MR_1';
    const MR_2 = 'MR_2';
    const MR_3 = 'MR_3';
    const MR_4 = 'MR_4';
    const CANCEL = 'CANCEL';
    const POSTPONE = 'POSTPONE';
    const SECURE_DENIED = 'SECURE_DENIED';
    const CB_BELG = 'CB_BELG';
    const EN_FNA = 'EN_FNA';
    const DELETE = 'DELETE';
    const MR_5 = 'MR_5';
    const CB_EXPIRED = 'CB_EXPIRED';

    /**
     * @var int
     * @ORM\Column(name="eti_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="eti_idcode", type="string", length=30, nullable=false)
     */
    protected $idcode = null;

    /**
     * @var bool
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    protected $active = true;
    /**
     * @var string
     * @ORM\Column(name="eti_libelle", type="string", length=20, nullable=false)
     */
    protected $libelle;

    /**
     * @var string
     * @ORM\Column(name="eti_desc", type="string", length=200, nullable=false)
     */
    protected $desc;

    /**
     * @var ArrayCollection(KGC\UserBundle\Entity\Profil)
     * @ORM\ManyToMany(targetEntity="KGC\UserBundle\Entity\Profil", inversedBy="etiquettes")
     * @ORM\JoinTable(
     *                                                    name="etiquettes_profil",
     *                                                    joinColumns={@ORM\JoinColumn(name="etiquette_id", referencedColumnName="eti_id", onDelete="CASCADE")},
     *                                                    inverseJoinColumns={@ORM\JoinColumn(name="profil_id", referencedColumnName="pro_id", onDelete="CASCADE")}
     *                                                    )
     */
    protected $profils;

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
     * @param $active
     *
     * @return $this
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active.
     *
     * @return boolean
     */
    public function getActive()
    {
        return $this->active;
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
     * @param $desc
     *
     * @return $this
     */
    public function setDesc($desc)
    {
        $this->desc = $desc;

        return $this;
    }

    /**
     * Get desc.
     *
     * @return string
     */
    public function getDesc()
    {
        return $this->desc;
    }

    /**
     * @param Profil $profils
     *
     * @return $this
     */
    public function addProfil(Profil $profils)
    {
        $this->profils[] = $profils;

        return $this;
    }

    /**
     * @param Profil $profils
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
        return $this->libelle;
    }
}
