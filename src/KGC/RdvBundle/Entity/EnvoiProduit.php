<?php
// src/KGC/RdvBundle/Entity/EnvoiProduit.php

namespace KGC\RdvBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use KGC\ClientBundle\Entity\Option;

/**
 * Entité EnvoiProduit.
 *
 * @category Entity
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 *
 * @ORM\Table(name="envoiproduit")
 * @ORM\Entity(repositoryClass="KGC\RdvBundle\Repository\EnvoiProduitRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class EnvoiProduit
{
    /**
     * @var int
     * @ORM\Column(name="evp_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \KGC\RdvBundle\Entity\VentesProduits
     * @ORM\OneToOne(targetEntity="VentesProduits", mappedBy="envoi")
     * @ORM\JoinColumn(name="evp_venteproduit", referencedColumnName="vpr_id", onDelete="CASCADE")
     */
    protected $vente_produit;

    /**
     * @var string
     * @ORM\Column(name="evp_etat", type="string", nullable=false, length=20)
     */
    protected $etat = self::PLANNED;

    /**
     * @var string
     * @ORM\Column(name="evp_date", type="date", nullable=true)
     */
    protected $date = null;

    /**
     * @var string
     * @ORM\Column(name="evp_commentaire", nullable=true, type="string", length=255)
     */
    protected $commentaire = null;

    /**
     * @var \KGC\RdvBundle\Entity\RDV
     * @ORM\ManyToOne(targetEntity="\KGC\RdvBundle\Entity\RDV", inversedBy="envoisProduits")
     * @ORM\JoinColumn(nullable=false, name="evp_consultation", referencedColumnName="rdv_id", onDelete="CASCADE")
     */
    protected $consultation;

    /**
     * @var string
     * @ORM\Column(name="evp_allumage", type="string", nullable=false, length=20)
     */
    protected $allumage = self::IGNITION_NONE;

    /**
     * @var \KGC\RdvBundle\Entity\RDV
     * @ORM\OneToOne(targetEntity="\KGC\RdvBundle\Entity\RDV", inversedBy="allumage")
     * @ORM\JoinColumn(nullable=true, name="evp_allumage_consultation", referencedColumnName="rdv_id", onDelete="SET NULL")
     */
    protected $allumage_consultation = null;

    const PLANNED = 'STATE_PLANNED';
    const DONE = 'STATE_DONE';
    const CANCELLED = 'STATE_CANCELLED';

    const IGNITION_NONE = 'IGNITION_NONE';
    const IGNITION_PLANNED = 'IGNITION_PLANNED';
    const IGNITION_DONE = 'IGNITION_DONE';

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
     * @return VentesProduits
     */
    public function getVenteProduit()
    {
        return $this->vente_produit;
    }

    /**
     * @param VentesProduits $produit
     */
    public function setVenteProduit(VentesProduits $produit)
    {
        $this->vente_produit = $produit;
    }

    /**
     * @param $etat
     *
     * @return $this
     */
    public function setEtat($etat)
    {
        $this->etat = $etat;

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
     * @param $date
     *
     * @return $this
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date.
     *
     * @return string
     */
    public function getDate()
    {
        return $this->date;
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
     * @param $commentaire
     *
     * @return $this
     */
    public function setCommentaire($commentaire)
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    /**
     * Get commentaire.
     *
     * @return string
     */
    public function getCommentaire()
    {
        return $this->commentaire;
    }

    /**
     * @param RDV $consultation
     *
     * @return $this
     */
    public function setConsultation(RDV $consultation)
    {
        $this->consultation = $consultation;
        $consultation->addEnvoisProduits($this);

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
     * @param $allumage
     *
     * @return $this
     */
    public function setAllumage($allumage)
    {
        $this->allumage = $allumage;

        return $this;
    }

    /**
     * Get allumage.
     *
     * @return string
     */
    public function getAllumage()
    {
        return $this->allumage;
    }

    /**
     * @param RDV|null $consultation
     *
     * @return $this
     */
    public function setAllumageConsultation(RDV $consultation)
    {
        $this->allumage_consultation = $consultation;
        if ($consultation !== null) {
            $this->allumage = self::IGNITION_DONE;
        }

        return $this;
    }

    /**
     * Get consultation.
     *
     * @return \KGC\RdvBundle\Entity\RDV
     */
    public function getAllumageConsultation()
    {
        return $this->allumage_consultation;
    }

    public function getLibelle()
    {
        $lib = $this->vente_produit->getQuantiteEnvoi().' '.$this->vente_produit->getProduit()->getLabel();
        $lib .= ' vendue(s) par '.$this->getConsultation()->getConsultant()->getUsername().' ('.$this->getConsultation()->getVoyant()->getNom().')';
        $lib .= ' le '.$this->getConsultation()->getDateConsultation()->format('d/m/Y');

        return $lib;
    }

    protected function needIgnition($produit)
    {
        $need_ignition = array(
            Option::PRODUCT_CANDLE,
            Option::PRODUCT_CANDLE_GREEN,
            Option::PRODUCT_CANDLE_PINK,
            Option::PRODUCT_CANDLE_BLACK,
            Option::PRODUCT_CANDLE_WHITE,
            Option::PRODUCT_CANDLE_YELLOW,
            Option::PRODUCT_CANDLE_RED,
            Option::PRODUCT_NOVENA,
            Option::PRODUCT_NOVENA_GREEN,
            Option::PRODUCT_NOVENA_PINK,
            Option::PRODUCT_NOVENA_BLACK,
            Option::PRODUCT_NOVENA_WHITE,
            Option::PRODUCT_NOVENA_YELLOW,
            Option::PRODUCT_NOVENA_RED,
            Option::PRODUCT_PACK_FULL,
            Option::PRODUCT_PACK_LOVE,
            Option::PRODUCT_PACK_TRUST,
            Option::PRODUCT_PACK_POSITIVE,
        );

        return in_array($produit, $need_ignition);
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersistDefinitions()
    {
        $rdv = $this->vente_produit->getTarification()->getRdv();
        if ($this->needIgnition($this->vente_produit->getProduit()->getCode())) {
            $this->allumage = self::IGNITION_PLANNED;
        }
        $this->setConsultation($rdv);
    }
}
