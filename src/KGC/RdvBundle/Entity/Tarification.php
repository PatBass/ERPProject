<?php

// src/KGCRdvBundle/Entity/Tarification.php


namespace KGC\RdvBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité Tarification.
 *
 * @category Entity
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 *
 * @ORM\Table(name="tarification")
 * @ORM\Entity(repositoryClass="KGC\RdvBundle\Repository\TarificationRepository")
 */
class Tarification
{
    /**
     * @var int
     * @ORM\Column(name="tar_id", columnDefinition="MEDIUMINT(8) UNSIGNED NOT NULL")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var int
     * @ORM\ManyToOne(targetEntity="\KGC\RdvBundle\Entity\CodeTarification")
     * @ORM\JoinColumn(nullable=true, name="tar_code", referencedColumnName="cdt_id")
     */
    protected $code = null;

    /**
     * @var string
     * @ORM\Column(name="tar_code_old", type="string", length=20, nullable=true)
     */
    protected $code_old = null;

    /**
     * @var int
     * @ORM\Column(name="tar_temps", type="integer")
     */
    protected $temps = 0;

    /**
     * @var bool
     * @ORM\Column(name="tar_10min", type="boolean")
     */
    protected $decount10min = true;

    /**
     * @var int
     * @ORM\Column(name="tar_montant_minutes", type="integer")
     */
    protected $montant_minutes = 0;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="VentesProduits", mappedBy="tarification", cascade={"persist", "remove"})
     */
    protected $produits;

    /**
     * @var int
     * @ORM\Column(name="tar_montant_produits", type="integer")
     */
    protected $montant_produits = 0;

    /**
     * @var int
     * @ORM\Column(name="tar_montant_frais", type="integer")
     */
    protected $montant_frais = 0;

    /**
     * @var int
     * @ORM\Column(name="tar_montant_remise", type="integer")
     */
    protected $montant_remise = 0;

    /**
     * @var int
     * @ORM\Column(name="tar_montant_total", type="integer")
     */
    protected $montant_total = 0;

    /**
     * @var RDV
     * @ORM\OneToOne(targetEntity="RDV", mappedBy="tarification")
     */
    protected $rdv;

    /**
     * @var \KGC\RdvBundle\Entity\Forfait
     * @ORM\OneToOne(targetEntity="\KGC\RdvBundle\Entity\Forfait", inversedBy="tar_origine", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, name="tar_forfaitvendu", referencedColumnName="frf_id")
     * @Assert\Valid()
     */
    protected $forfait_vendu = null;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="\KGC\RdvBundle\Entity\ConsommationForfait", mappedBy="tarification", cascade={"persist"})
     * @Assert\Valid()
     */
    protected $consommations_forfaits;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->produits = new ArrayCollection();
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
     * Get code_old.
     *
     * @return string
     */
    public function getCodeOld()
    {
        return $this->code_old;
    }

    /**
     * @param CodeTarification|null $code
     *
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return CodeTarification
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param $temps
     *
     * @return $this
     */
    public function setTemps($temps)
    {
        if (is_numeric($temps)) {
            $this->temps = $temps;
        }

        return $this;
    }

    /**
     * Get temps.
     *
     * @return int
     */
    public function getTemps()
    {
        return $this->temps;
    }

    /**
     * @param $decount10min
     *
     * @return $this
     */
    public function setDecount10min($decount10min)
    {
        $this->decount10min = $decount10min;

        return $this;
    }

    /**
     * Get decount10min.
     *
     * @return string
     */
    public function getDecount10min()
    {
        return $this->decount10min;
    }

    /**
     * @param $montant
     *
     * @return $this
     */
    public function setMontantMinutes($montant)
    {
        if (is_numeric($montant)) {
            $this->montant_minutes = $montant * 100;
        }

        return $this;
    }

    /**
     * Get montant_minutes.
     *
     * @return int
     */
    public function getMontantMinutes()
    {
        return $this->montant_minutes / 100;
    }

    /**
     * @param $produits
     *
     * @return $this
     */
    public function setProduits($produits)
    {
        $this->produits = new ArrayCollection();
        foreach ($produits as $produit) {
            $this->addProduits($produit);
        }

        return $this;
    }

    /**
     * Add produits.
     *
     * @param \KGC\RdvBundle\Entity\VentesProduits $produits
     *
     * @return \KGC\RdvBundle\Entity\Tarification
     */
    public function addProduits(VentesProduits $produit)
    {
        if ($produit->getMontant() > 0) {
            $produit->setTarification($this);
            $this->produits[] = $produit;
        }

        return $this;
    }

    /**
     * @param VentesProduits $produits
     *
     * @return $this
     */
    public function removeProduits(VentesProduits $produits)
    {
        $this->produits->removeElement($produits);

        return $this;
    }

    /**
     * Get produits.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProduits()
    {
        return $this->produits;
    }

    /**
     * @param $montant
     *
     * @return $this
     */
    public function setMontantProduits($montant)
    {
        if (is_numeric($montant)) {
            $this->montant_produits = $montant * 100;
        }

        return $this;
    }

    /**
     * Get montant.
     *
     * @return int
     */
    public function getMontantProduits()
    {
        return $this->montant_produits / 100;
    }

    /**
     * @param $montant
     *
     * @return $this
     */
    public function setMontantFrais($montant)
    {
        if (is_numeric($montant)) {
            $this->montant_frais = $montant * 100;
        }

        return $this;
    }

    /**
     * Get montant.
     *
     * @return int
     */
    public function getMontantFrais()
    {
        return $this->montant_frais / 100;
    }

    /**
     * @param $montant
     *
     * @return $this
     */
    public function setMontantRemise($montant)
    {
        if (is_numeric($montant)) {
            $this->montant_remise = $montant * 100;
        }

        return $this;
    }

    /**
     * Get montant.
     *
     * @return int
     */
    public function getMontantRemise()
    {
        return $this->montant_remise / 100;
    }

    /**
     * @param $montant
     *
     * @return $this
     */
    public function setMontantTotal($montant)
    {
        if (is_numeric($montant)) {
            $this->montant_total = $montant * 100;
        }

        return $this;
    }

    /**
     * Get montant.
     *
     * @return int
     */
    public function getMontantTotal()
    {
        return $this->montant_total / 100;
    }

    /**
     * @return RDV
     */
    public function getRdv()
    {
        return $this->rdv;
    }

    /**
     * @param RDV $rdv
     */
    public function setRdv($rdv)
    {
        $this->rdv = $rdv;
    }

    /**
     * @return Forfait
     */
    public function getForfaitVendu()
    {
        return $this->forfait_vendu;
    }

    /**
     * @param Forfait $forfait
     */
    public function setForfaitVendu($forfait)
    {
        if ($forfait instanceof Forfait) {
            $forfait->setTarOrigine($this);
        }
        $this->forfait_vendu = $forfait;
    }

    /**
     * @param $consos
     *
     * @return $this
     */
    public function setConsommationsForfaits($consos)
    {
        $this->consommations_forfaits = new ArrayCollection();
        foreach ($consos as $conso) {
            $this->addConsommationsForfaits($conso);
        }

        return $this;
    }

    /**
     * Add consommations_forfaits.
     *
     * @param \KGC\RdvBundle\Entity\ConsommationForfait $conso
     *
     * @return \KGC\RdvBundle\Entity\Tarification
     */
    public function addConsommationsForfaits(ConsommationForfait $conso)
    {
        $conso->setTarification($this);
        $this->consommations_forfaits[] = $conso;

        return $this;
    }

    /**
     * @param ConsommationForfait $conso
     *
     * @return $this
     */
    public function removeConsommationsForfaits(ConsommationForfait $conso)
    {
        $this->consommations_forfaits->removeElement($conso);
        $conso->getForfait()->removeConsommations($conso);

        return $this;
    }

    /**
     * Get consommations_forfaits.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getConsommationsForfaits()
    {
        return $this->consommations_forfaits;
    }
}
