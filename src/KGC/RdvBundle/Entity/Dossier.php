<?php

// src/KGC/RdvBundle/Entity/Dossier.php


namespace KGC\RdvBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entité Dossier : Dossier de classement des fiches RDV.
 *
 * @category Entity
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 *
 * @ORM\Entity(repositoryClass="KGC\RdvBundle\Repository\DossierRepository")
 * @ORM\Table(name="dossier")
 */
class Dossier extends Classement
{
    const OPPOSITION = 'OPPO';
    const VALIDE = 'VALIDE';
    const DIXMIN = '10_MIN';
    const RECUP = 'RECUP';
    const EN_FNA = 'EN_FNA';
    const NRP = 'NRP';
    const NVP = 'VNP';
    const CB_BELGE = 'CB_BELG';
    const CB_VIDE = 'CB_VIDE';
    const REFUS_2E = 'REFUS_2E';
    const ABANDON = 'ABANDON';
    const GEERIM = 'GEERIM';
    const LITIGE = 'LITIGE';

    /**
     * @var \KGC\RdvBundle\Entity\Tiroir
     * @ORM\ManyToOne(targetEntity="\KGC\RdvBundle\Entity\Tiroir", inversedBy="dossiers")
     * @ORM\JoinColumn(nullable=false, name="dos_tiroir", referencedColumnName="cla_id")
     */
    protected $tiroir;

    /**
     * @var bool
     * @ORM\Column(name="dos_motifannulation", type="boolean", options={"default"="0"})
     */
    protected $motifAnnulation = false;

    /**
     * @var string
     */
    protected $icon = 'folder-close';

    /**
     * @var string
     */
    protected $type = 'dossier';

    /**
     * Get tiroir.
     *
     * @return \KGC\RdvBundle\Entity\Tiroir
     */
    public function getTiroir()
    {
        return $this->tiroir;
    }

    /**
     * @param Tiroir $tiroir
     *
     * @return $this
     */
    public function setTiroir(Tiroir $tiroir)
    {
        $this->tiroir = $tiroir;

        return $this;
    }

    /**
     * Get motifAnnulation.
     *
     * @return bool
     */
    public function getMotifAnnulation()
    {
        return $this->motifAnnulation;
    }

    /**
     * @param bool $motifAnnulation
     *
     * @return $this
     */
    public function setMotifAnnulation($motifAnnulation)
    {
        $this->motifAnnulation = $motifAnnulation;

        return $this;
    }

    /**
     * Get icon.
     *
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * GetViewData : ordonne les données pour génération vue du classement.
     */
    public function getViewData()
    {
        $vue['dossier'] = $this;
        $vue['tiroir'] = $this->getTiroir();

        return $vue;
    }
}
