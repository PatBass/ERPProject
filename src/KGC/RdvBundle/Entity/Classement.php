<?php

// src/KGC/RdvBundle/Entity/Classement.php


namespace KGC\RdvBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entité Classement : Classement des fiches RDV.
 *
 * @category Entity
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 *
 * @ORM\Entity(repositoryClass="KGC\RdvBundle\Repository\ClassementRepository")
 * @ORM\Table(name="classement",
 *      indexes={
 *          @ORM\Index(name="classement_code_idx", columns={"cla_idcode"}),
 *      })
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="cla_type", type="string", length=10)
 * @ORM\DiscriminatorMap({"tiroir"="Tiroir", "dossier"="Dossier"})
 */
abstract class Classement
{
    /**
     * @var int
     * @ORM\Column(name="cla_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="cla_idcode", type="string", length=20, nullable=true)
     */
    protected $idcode = null;

    /**
     * @var string
     * @ORM\Column(name="cla_libelle", type="string", length=50, nullable=false)
     */
    protected $libelle;

    /**
     * @var string
     * @ORM\Column(name="cla_desc", type="string", length=200, nullable=false)
     */
    protected $desc;

    /**
     * @var date
     * @ORM\Column(name="cla_disabled_date", type="date", nullable=true)
     */
    protected $disabled_date = null;

    /**
     * @var string
     * @ORM\Column(name="cla_couleur", type="string", length=20, nullable=true)
     */
    protected $couleur = null;

    public function __construct()
    {
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
     * @param $couleur
     *
     * @return $this
     */
    public function setCouleur($couleur)
    {
        $this->couleur = $couleur;

        return $this;
    }

    /**
     * Get couleur.
     *
     * @return string
     */
    public function getCouleur()
    {
        return $this->couleur;
    }

    /**
     * @param bool $actif
     *
     * @return $this
     */
    public function setEnabled($actif)
    {
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
        return $this->disabled_date === null;
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
     * GetViewData : ordonne les données pour génération vue du classement.
     */
    abstract public function getViewData();

    /**
     * Get tiroir.
     *
     * @return \KGC\RdvBundle\Entity\Tiroir
     */
    abstract public function getTiroir();

    public function __toString()
    {
        return $this->libelle;
    }
}
