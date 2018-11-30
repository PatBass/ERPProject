<?php

// src/KGCRdvBundle/Entity/Forfait.php


namespace KGC\RdvBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\ClientBundle\Entity\Historique;
use KGC\ClientBundle\Entity\Option;
use KGC\UserBundle\Entity\Voyant;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Entité Forfait.
 *
 * @category Entity
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 *
 * @ORM\Table(name="forfaits")
 * @ORM\Entity(repositoryClass="KGC\RdvBundle\Repository\ForfaitRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Forfait
{
    /**
     * @var int
     * @ORM\Column(name="frf_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var int
     * @ORM\ManyToOne(targetEntity="\KGC\ClientBundle\Entity\Option")
     * @ORM\JoinColumn(name="frf_nom", referencedColumnName="id", nullable=false)
     */
    protected $nom;

    /**
     * @var \KGC\UserbUndle\Entity\Voyant
     * @ORM\ManyToOne(targetEntity="\KGC\UserBundle\Entity\Voyant")
     * @ORM\JoinColumn(nullable=false, name="frf_voyant", referencedColumnName="voy_id")
     */
    protected $voyant;

    /**
     * @var int
     * @ORM\Column(name="frf_tempstotal", type="integer")
     * @Assert\NotBlank()
     */
    protected $tps_total;

    /**
     * @var int
     * @ORM\Column(name="frf_prix", type="integer")
     * @Assert\NotBlank()
     */
    protected $prix;

    /**
     * @var Tarification
     * @ORM\OneToOne(targetEntity="\KGC\RdvBundle\Entity\Tarification", mappedBy="forfait_vendu")
     */
    protected $tar_origine;

    /**
     * @var int
     * @ORM\Column(name="frf_tempsconsomme", type="integer")
     */
    protected $tps_consomme = 0;

    /**
     * @var ArrayCollection(ConsommationForfait)
     * @ORM\OneToMany(targetEntity="\KGC\RdvBundle\Entity\ConsommationForfait", mappedBy="forfait", cascade={"persist"})
     */
    protected $consommations;

    /**
     * @var int
     * @ORM\Column(name="frf_epuise", type="boolean")
     */
    protected $epuise = false;

    /**
     * @var \KGC\Bundle\SharedBundle\Entity\Client
     * @ORM\ManyToOne(targetEntity="\KGC\Bundle\SharedBundle\Entity\Client", inversedBy="forfaits")
     * @ORM\JoinColumn(nullable=false, name="frf_client", referencedColumnName="id")
     */
    protected $client;

    public function __construct()
    {
        $this->consommations = new ArrayCollection();
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
     * Get nom.
     *
     * @return Option
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * @param Option $nom
     *
     * @return $this
     */
    public function setNom($nom)
    {
        if ($nom !== null && $nom instanceof Option) {
            if ($nom->getType() == Historique::TYPE_PLAN) {
                $this->nom = $nom;
                $this->tps_total = $nom->getDataAttr();
            }
        }

        return $this;
    }

    /**
     * Get voyant.
     *
     * @return Voyant
     */
    public function getVoyant()
    {
        return $this->voyant;
    }

    /**
     * @param Voyant $voyant
     *
     * @return $this
     */
    public function setVoyant(Voyant $voyant)
    {
        $this->voyant = $voyant;

        return $this;
    }

    /**
     * Get TempsTotal.
     *
     * @return int
     */
    public function getTempsTotal()
    {
        return $this->tps_total;
    }

    /**
     * @param $prix
     *
     * @return $this
     */
    public function setPrix($prix)
    {
        if (is_numeric($prix)) {
            $this->prix = $prix * 100;
        }

        return $this;
    }

    /**
     * Get prix.
     *
     * @return int
     */
    public function getPrix()
    {
        return $this->prix / 100;
    }

    /**
     * Get tar_origine.
     *
     * @return Tarification
     */
    public function getTarOrigine()
    {
        return $this->tar_origine;
    }

    /**
     * @param Tarification $origine
     *
     * @return $this
     */
    public function setTarOrigine(Tarification $origine)
    {
        $this->tar_origine = $origine;
        if ($this->tps_consomme > 0 && $this->consommations->isEmpty()) {
            $conso = new ConsommationForfait();
            $conso->setForfait($this)
                  ->setTarification($origine)
                  ->setTemps($this->tps_consomme);
        }

        return $this;
    }

    /**
     * @param $tempsConsomme
     *
     * @return $this
     */
    public function setTempsConsomme($tempsConsomme)
    {
        $this->tps_consomme = $tempsConsomme;

        return $this;
    }

    /**
     * Get TempsConsomme.
     *
     * @return int
     */
    public function getTempsConsomme()
    {
        return $this->tps_consomme;
    }

    /**
     * Get Epuise.
     *
     * @return bool
     */
    public function getTempsRestant()
    {
        return $this->tps_total - $this->tps_consomme;
    }

    /**
     * Get Epuise.
     *
     * @return bool
     */
    public function getEpuise()
    {
        return $this->epuise;
    }

    /**
     * @param Client $client
     *
     * @return $this
     */
    public function setClient(Client $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    public function getIdentifierLabel()
    {
        $l = $this->nom;
        if ($this->voyant instanceof Voyant) {
            $l .= ' - '.$this->voyant->getNom();
        }

        return $l;
    }

    public function getLabel()
    {
        $lbl = $this->getIdentifierLabel();
        if (!$this->epuise) {
            $lbl .= ' (reste '.$this->getTempsRestant().'min)';
        } else {
            $lbl .= ' (épuisé)';
        }

        return $lbl;
    }

    /**
     * @param $consos
     *
     * @return $this
     */
    public function setConsommations($consos)
    {
        $this->consommations = new ArrayCollection();
        foreach ($consos as $conso) {
            $this->addConsommations($conso);
        }

        return $this;
    }

    /**
     * Add consommations.
     *
     * @param \KGC\RdvBundle\Entity\ConsommationForfait $conso
     *
     * @return \KGC\RdvBundle\Entity\Forfait
     */
    public function addConsommations(ConsommationForfait $conso)
    {
        $this->consommations[] = $conso;

        return $this;
    }

    /**
     * @param ConsommationForfait $conso
     *
     * @return $this
     */
    public function removeConsommations(ConsommationForfait $conso)
    {
        $this->consommations->removeElement($conso);

        return $this;
    }

    /**
     * Get consommations.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getConsommations()
    {
        return $this->consommations;
    }

    public function calcTempsConsomme()
    {
        $tps_consomme = 0;
        foreach ($this->consommations as $conso) {
            $tps_consomme += $conso->getTemps();
        }
        $this->tps_consomme = $tps_consomme;
        $this->epuise = $this->tps_consomme == $this->tps_total;
    }

    /**
     * @Assert\Callback
     */
    public function isPlanValid(ExecutionContextInterface $context)
    {
        $this->calcTempsConsomme();
        if ($this->getTempsConsomme() > $this->getTempsTotal()) {
            $message = 'Le temps consommé du forfait « %forfait% » dépasse le temps restant.';
            $context
                 ->buildViolation($message)
                 ->setParameters(array('%forfait%' => $this->getIdentifierLabel()))
                 ->addViolation();
        }
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersistDefinitions()
    {
        $this->client = $this->tar_origine->getRdv()->getClient();
        $this->voyant = $this->tar_origine->getRdv()->getVoyant();
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdateDefinitions()
    {
        $this->calcTempsConsomme();
    }
}
