<?php

// src/KGC/RdvBundle/Entity/Etat.php


namespace KGC\RdvBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entité Etat : Etat des rdv.
 *
 * @category Entity
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 *
 * @ORM\Table(name="etatconsultation")
 * @ORM\Entity()
 */
class Etat
{
    /**
     * @var int
     * @ORM\Column(name="eta_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var type string
     * @ORM\Column(name="eta_idcode", type="string", length=20, nullable=false)
     */
    protected $idcode;

    /**
     * @var string
     * @ORM\Column(name="eta_libelle", type="string", length=50, nullable=false)
     */
    protected $libelle;

    /**
     * @var string
     * @ORM\Column(name="eta_couleur", type="string", length=20, nullable=false)
     */
    protected $couleur;

    const ADDED = 'ADDED';
    const CONFIRMED = 'CONFIRMED';
    const CANCELLED = 'CANCELLED';
    const INPROGRESS = 'INPROGRESS';
    const COMPLETED = 'COMPLETED';
    const CLOSED = 'CLOSED';
    const UNPAID = 'UNPAID';
    const PAUSED = 'PAUSED';

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->id = 1;
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

    public function __toString()
    {
        return $this->libelle;
    }
}
