<?php

// src/KGCRdvBundle/Entity/VentesProduits.php


namespace KGC\RdvBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use KGC\ClientBundle\Entity\Historique;
use KGC\ClientBundle\Entity\Option;

/**
 * Entité VentesProduits.
 *
 * @category Entity
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 *
 * @ORM\Table(name="ventes_produits")
 * @ORM\Entity
 */
class VentesProduits
{
    /**
     * @var int
     * @ORM\Column(name="vpr_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var int
     * @ORM\ManyToOne(targetEntity="\KGC\ClientBundle\Entity\Option")
     * @ORM\JoinColumn(name="vpr_produit", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $produit;

    /**
     * @var int
     * @ORM\Column(name="vpr_quantite", type="integer")
     */
    protected $quantite = 1;

    /**
     * @var int
     * @ORM\Column(name="vpr_quantite_envoi", type="integer")
     */
    protected $quantite_envoi = 0;

    /**
     * @var int
     * @ORM\Column(name="vpr_montant", type="integer")
     */
    protected $montant = 0;

    /**
     * @var \KGC\RdvBundle\Entity\RDV
     * @ORM\ManyToOne(targetEntity="\KGC\RdvBundle\Entity\Tarification", inversedBy="produits")
     * @ORM\JoinColumn(nullable=false, name="vpr_tarification", referencedColumnName="tar_id")
     */
    protected $tarification;

    /**
     * @var \KGC\RdvBundle\Entity\EnvoiProduit
     * @ORM\OneToOne(targetEntity="EnvoiProduit", inversedBy="vente_produit", cascade={"persist","remove"})
     * @ORM\JoinColumn(name="vpr_envoi", referencedColumnName="evp_id", nullable=true)
     */
    protected $envoi = null;

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
     * Get produit.
     *
     * @return Option
     */
    public function getProduit()
    {
        return $this->produit;
    }

    /**
     * @param Option $produit
     *
     * @return $this
     */
    public function setProduit(Option $produit)
    {
        if ($produit->getType() == Historique::TYPE_PRODUCT) {
            $this->produit = $produit;
        }

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
     * @param $quantite
     *
     * @return $this
     */
    public function setQuantite($quantite)
    {
        $this->quantite = $quantite;

        return $this;
    }

    /**
     * Get Quantite.
     *
     * @return int
     */
    public function getQuantite()
    {
        return $this->quantite;
    }

    /**
     * @param $quantite
     *
     * @return $this
     */
    public function setQuantiteEnvoi($quantite)
    {
        $this->quantite_envoi = $quantite;

        if ($this->quantite_envoi > 0 && $this->envoi === null) {
            $this->envoi = new EnvoiProduit();
            $this->envoi->setVenteProduit($this);
        }

        return $this;
    }

    /**
     * Get Quantite.
     *
     * @return int
     */
    public function getQuantiteEnvoi()
    {
        return $this->quantite_envoi;
    }

    /**
     * @param $montant
     *
     * @return $this
     */
    public function setMontant($montant)
    {
        if (is_numeric($montant)) {
            $this->montant = $montant * 100;
        }

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
     * @return Tarification
     */
    public function getTarification()
    {
        return $this->tarification;
    }

    /**
     * @param Tarification $tarification
     */
    public function setTarification(Tarification $tarification)
    {
        $this->tarification = $tarification;

        return $this;
    }

    /**
     * @return EnvoiProduit
     */
    public function getEnvoi()
    {
        return $this->envoi;
    }

    /**
     * @param EnvoiProduit $envoi
     */
    public function setEnvoi($envoi)
    {
        $this->envoi = $envoi;

        return $this;
    }

    /**
     * @return VentesProduits
     */
    public function setQuantiteEnvoiSupposee()
    {
        if ($this->getProduit()->getAutomaticSending()) {
            $this->setQuantiteEnvoi($this->getQuantite());
        } else {
            $this->setQuantiteEnvoi(0);
        }

        return $this;
    }
}
