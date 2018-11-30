<?php

// src/KGC/RdvBundle/Entity/ConsommationForfait.php


namespace KGC\RdvBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité ConsommationForfait.
 *
 * @category Entity
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 *
 * @ORM\Table(name="consommation_forfait")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class ConsommationForfait
{
    /**
     * @var int
     * @ORM\Column(name="csf_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \KGC\RdvBundle\Entity\Forfait
     * @ORM\ManyToOne(targetEntity="\KGC\RdvBundle\Entity\Forfait", inversedBy="consommations")
     * @ORM\JoinColumn(nullable=false, name="csf_forfait", referencedColumnName="frf_id", onDelete="CASCADE")
     * @Assert\NotBlank()
     * @Assert\Valid()
     */
    protected $forfait;

    /**
     * @var \DateTime
     * @ORM\Column(name="csf_date", type="date")
     */
    protected $date;

    /**
     * @var \KGC\RdvBundle\Entity\Tarification
     * @ORM\ManyToOne(targetEntity="\KGC\RdvBundle\Entity\Tarification", inversedBy="consommations_forfaits")
     * @ORM\JoinColumn(nullable=false, name="csf_tarification", referencedColumnName="tar_id", onDelete="CASCADE")
     */
    protected $tarification;

    /**
     * @var int
     * @ORM\Column(name="csf_temps", type="integer", nullable=false)
     */
    protected $temps;

    public function __construct()
    {
        $this->date = new \DateTime();
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
     * @param Forfait $forfait
     *
     * @return $this
     */
    public function setForfait(Forfait $forfait)
    {
        $this->forfait = $forfait;
        $forfait->addConsommations($this);

        return $this;
    }

    /**
     * Get forfait.
     *
     * @return Forfait
     */
    public function getForfait()
    {
        return $this->forfait;
    }

    /**
     * Set date.
     *
     * @param \DateTime $date
     *
     * @return \KGC\RdvBundle\Entity\ConsommationForfait
     */
    public function setDate(\DateTime $date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date.
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param $tarification
     *
     * @return $this
     */
    public function setTarification(Tarification $tarification)
    {
        $this->tarification = $tarification;

        return $this;
    }

    /**
     * Get tarification.
     *
     * @return Tarification
     */
    public function getTarification()
    {
        return $this->tarification;
    }

    /**
     * @param $temps
     *
     * @return $this
     */
    public function setTemps($temps)
    {
        $this->temps = $temps;

        return $this;
    }

    /**
     * Get temps.
     *
     * @return string
     */
    public function getTemps()
    {
        return $this->temps;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersistDefinitions()
    {
        $this->date = $this->tarification->getRdv()->getDateConsultation();
    }

    /**
     * @ORM\PreRemove
     */
    public function preRemoveDefinitions()
    {
        $this->tarification->removeConsommationsForfaits($this);
        $this->forfait->removeConsommations($this);
        $this->forfait->calcTempsConsomme();
    }
}
