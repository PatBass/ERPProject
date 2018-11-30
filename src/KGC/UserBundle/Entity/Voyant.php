<?php

// src/KGC/UserBundle/Entity/Voyant.php


namespace KGC\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use KGC\RdvBundle\Entity\CodeTarification;
use KGC\Bundle\SharedBundle\Entity\Website;

/**
 * Entité Voyant : noms de voyants fictifs.
 *
 * @category Entity
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 *
 * @ORM\Table(name="voyant")
 * @ORM\Entity(repositoryClass="KGC\UserBundle\Repository\VoyantRepository")
 */
class Voyant
{
    /**
     * @var int
     * @ORM\Column(name="voy_id", columnDefinition="INT UNSIGNED NOT NULL AUTO_INCREMENT")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="voy_nom", type="string", length=255)
     */
    protected $nom;

    /**
     * @var KGC\UserBundle\Entity\CodeTarification
     * @ORM\ManyToOne(targetEntity="\KGC\RdvBundle\Entity\CodeTarification")
     * @ORM\JoinColumn(nullable=true, name="voy_codetar", referencedColumnName="cdt_id")
     */
    protected $codeTarification;

    /**
     * @var bool
     * @ORM\Column(name="voy_enabled", type="boolean" )
     */
    protected $enabled = true;

    /**
     * @var date
     * @ORM\Column(name="voy_disabled_date", type="date", nullable=true)
     */
    protected $disabled_date = null;

    /**
     * @var int
     * @ORM\Column(name="voy_sexe", type="smallint", nullable=true)
     */
    protected $sexe;

    /**
     * @ORM\ManyToOne(targetEntity="\KGC\Bundle\SharedBundle\Entity\Website")
     * @ORM\JoinColumn(name="website_id", referencedColumnName="web_id", nullable=true)
     */
    protected $website;

    /**
     * @var string
     * @ORM\Column(name="voy_reference", type="string", length=255, nullable=true)
     */
    protected $reference;

    /**
     * @ORM\ManyToOne(targetEntity="\KGC\UserBundle\Entity\Utilisateur")
     * @ORM\JoinColumn(name="utilisateur_id", referencedColumnName="uti_id", nullable=true)
     */
    protected $utilisateur;

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
     * Set nom.
     *
     * @param string $nom
     *
     * @return Voyant
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get nom.
     *
     * @return string
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * @param CodeTarification $codeTarification
     *
     * @return $this
     */
    public function setCodeTarification($codeTarification)
    {
        $this->codeTarification = $codeTarification;

        return $this;
    }

    /**
     * @return CodeTarification
     */
    public function getCodeTarification()
    {
        return $this->codeTarification;
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
        return sprintf('%s', $this->nom);
    }

    /**
     * Set sexe.
     *
     * @param int $sexe
     *
     * @return Voyant
     */
    public function setSexe($sexe)
    {
        $this->sexe = $sexe;

        return $this;
    }

    /**
     * Get sexe.
     *
     * @return int
     */
    public function getSexe()
    {
        return $this->sexe;
    }

    /**
     * Set website.
     *
     * @param Website $website
     *
     * @return this
     */
    public function setWebsite($website)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * Get website.
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * Set reference.
     *
     * @param string $reference
     *
     * @return Voyant
     */
    public function setReference($reference)
    {
        $this->reference = $reference;

        return $this;
    }

    /**
     * Get reference.
     *
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * Set utilisateur
     *
     * @param \KGC\UserBundle\Entity\Utilisateur $utilisateur
     *
     * @return Voyant
     */
    public function setUtilisateur(\KGC\UserBundle\Entity\Utilisateur $utilisateur = null)
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    /**
     * Get utilisateur
     *
     * @return \KGC\UserBundle\Entity\Utilisateur
     */
    public function getUtilisateur()
    {
        return $this->utilisateur;
    }
}
