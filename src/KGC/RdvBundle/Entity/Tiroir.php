<?php

// src/KGC/RdvBundle/Entity/Tiroir.php


namespace KGC\RdvBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité Tiroir : Tiroir de classement des fiches RDV.
 *
 * @category Entity
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 *
 * @ORM\Entity
 * @ORM\Table(name="tiroir")
 */
class Tiroir extends Classement
{
    /**
     * @var string
     */
    protected $icon = 'inbox';

    /**
     * @var string
     */
    protected $type = 'tiroir';

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="Dossier", mappedBy="tiroir")
     * @ORM\OrderBy({"libelle" = "ASC"})
     */
    protected $dossiers;

    const PROCESSING = 'PROCESSING';
    const ARCHIVES = 'ARCHIVES';
    const UNPAID = 'UNPAID';

    public function __construct()
    {
        parent::__construct();
        $this->dossiers = new ArrayCollection();
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
        $vue['tiroir'] = $this;

        return $vue;
    }

    /**
     * @return \KGC\RdvBundle\Entity\Tiroir
     */
    public function getTiroir()
    {
        return $this;
    }

    /**
     * Get dossiers.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDossiers()
    {
        return $this->dossiers;
    }
}
