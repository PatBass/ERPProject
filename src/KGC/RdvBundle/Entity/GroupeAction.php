<?php

// src/KGC/RdvBundle/Entity/GroupeAction.php


namespace KGC\RdvBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entité GroupeAction : Groupe d'actions.
 *
 * @category Entity
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 *
 * @ORM\Table(name="groupeaction")
 * @ORM\Entity()
 */
class GroupeAction
{
    /**
     * @var int
     * @ORM\Column(name="gpa_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="gpa_idcode", type="string", length=30, nullable=false)
     */
    protected $idcode;

    /**
     * @var string
     * @ORM\Column(name="gpa_libelle", type="string", length=255)
     */
    protected $libelle;

    /**
     * @var string
     * @ORM\Column(name="gpa_icon", type="string", length=255)
     */
    protected $icon;

    const UPDATE = 'UPDATE';
    const POSTPONE = 'POSTPONE';
    const CANCEL = 'CANCEL';
    const ADD = 'ADD';
    const SECURE = 'SECURE';
    const BILLING = 'BILLING';
    const CLOSE = 'CLOSE';
    const IMPORT = 'IMPORT';

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
     * Set libelle.
     *
     * @param string $libelle
     *
     * @return \KGC\RdvBundle\Entity\GroupeAction
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
     * Set icon.
     *
     * @param string $icon
     *
     * @return \KGC\RdvBundle\Entity\GroupeAction
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;

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
}
