<?php

// src/KGC/UserBundle/Entity/Poste.php


namespace KGC\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entité Poste : postes téléphoniques des utilisateurs de l'application.
 *
 * @category Entity
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 *
 * @ORM\Table(name="poste")
 * @ORM\Entity()
 */
class Poste
{
    /**
     * @var int
     *
     * @ORM\Column(name="poste_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="poste_name", type="string", length=30)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(name="poste_phone", type="string", length=20)
     */
    protected $phone;

    /**
     * @var string
     * @ORM\Column(name="poste_cli", nullable=true, type="string", length=20)
     */
    protected $cli;

    /**
     * Constructeur.
     */
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
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return \KGC\UserBundle\Entity\Poste
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set phone.
     *
     * @param string $phone
     *
     * @return \KGC\UserBundle\Entity\Poste
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone.
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set cli.
     *
     * @param string $cli
     *
     * @return \KGC\UserBundle\Entity\Poste
     */
    public function setCli($cli)
    {
        $this->cli = $cli;

        return $this;
    }

    /**
     * Get cli.
     *
     * @return string
     */
    public function getCli()
    {
        return $this->cli;
    }
}
