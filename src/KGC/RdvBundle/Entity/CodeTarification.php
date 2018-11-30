<?php

// src/KGC/RdvBundle/Entity/CodeTarification.php


namespace KGC\RdvBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;

/**
 * Entité CodeTarification.
 *
 * @category Entity
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 *
 * @ORM\Table(name="code_tarification")
 * @ORM\Entity()
 */
class CodeTarification
{
    use ORMBehaviors\Timestampable\Timestampable;

    /**
     * @var int
     * @ORM\Column(name="cdt_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="cdt_libelle", type="string", length=20)
     * @Assert\NotBlank()
     */
    protected $libelle;

    /**
     * @var float
     * @ORM\Column(name="cdt_multiplicateur", type="integer")
     * @Assert\NotBlank()
     */
    protected $multiplicateur;

    /**
     * @var
     *
     * @ORM\OneToMany(targetEntity="ForfaitTarification", mappedBy="codeTarification")
     */
    protected $forfaitTarification;

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
     * @param $multiplicateur
     *
     * @return $this
     */
    public function setMultiplicateur($multiplicateur)
    {
        if (is_numeric($multiplicateur)) {
            $this->multiplicateur = $multiplicateur * 100;
        }

        return $this;
    }

    public function getMultiplicateurDB()
    {
        return $this->multiplicateur;
    }

    /**
     * Get multiplicateur.
     *
     * @return int
     */
    public function getMultiplicateur()
    {
        return $this->multiplicateur / 100;
    }

    public function __toString()
    {
        return $this->libelle;
    }
}
