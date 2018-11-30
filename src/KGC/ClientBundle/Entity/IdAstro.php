<?php

// src/KGC/ClientBundle/Entity/IdAstro.php


namespace KGC\ClientBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\Bundle\SharedBundle\Entity\Website;
use KGC\RdvBundle\Entity\RDV;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité IdAstro.
 *
 * @category Entity
 *
 * @author Laurène Dourdin
 *
 * @ORM\Table(name="idastro")
 * @ORM\Entity(repositoryClass="KGC\ClientBundle\Repository\IdAstroRepository")
 */
class IdAstro
{
    /**
     * @var int
     * @ORM\Column(name="ida_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="ida_valeur", type="integer")
     * @Assert\NotBlank()
     */
    protected $valeur;

    /**
     * @var string
     * @ORM\ManyToOne(targetEntity="\KGC\Bundle\SharedBundle\Entity\Client", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", name="ida_client", nullable = false)
     * @Assert\NotBlank()
     */
    protected $client;

    /**
     * @var string
     * @ORM\ManyToOne(targetEntity="\KGC\Bundle\SharedBundle\Entity\Website")
     * @ORM\JoinColumn(referencedColumnName="web_id", name="ida_website", nullable = false)
     * @Assert\NotBlank()
     */
    protected $website;

    /**
     * @var ArrayCollection(KGC\RdvBundle\Entity\RDV)
     * @ORM\OneToMany(targetEntity="KGC\RdvBundle\Entity\RDV", mappedBy="idAstro")
     */
    protected $consultations;

    public function __construct()
    {
        $this->consultations = new ArrayCollection();
    }

    public function create(Client $client, Website $website, $valeur)
    {
        $this->setClient($client);
        $this->setWebsite($website);
        $this->setValeur($valeur);
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
     * Get valeur.
     *
     * @return int
     */
    public function getValeur()
    {
        return $this->valeur;
    }

    /**
     * Set valeur.
     *
     * @param int $idastro
     *
     * @return KGC\RdvBundle\Entity\Client
     */
    public function setValeur($idastro)
    {
        if ($idastro === null) {
            $idastro = 0;
        }
        $this->valeur = $idastro;

        return $this;
    }

    /**
     * Set client.
     *
     * @param string $client
     *
     * @return \KGC\Bundle\SharedBundle\Entity\Client
     */
    public function setClient(Client $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get client.
     *
     * @return string
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Set website.
     *
     * @param string $website
     *
     * @return \KGC\Bundle\SharedBundle\Entity\Website
     */
    public function setWebsite(Website $website)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * Get website.
     *
     * @return string
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * Add consultation.
     *
     * @param \KGC\RdvBundle\Entity\RDV $consultation
     *
     * @return \KGC\RdvBundle\Entity\Support
     */
    public function addConsultation(RDV $consultation)
    {
        $this->consultations[] = $consultation;

        return $this;
    }

    /**
     * Remove consultation.
     *
     * @param \KGC\RdvBundle\Entity\RDV $consultation
     */
    public function removeConsultation(RDV $consultation)
    {
        $this->consultations->removeElement($consultation);
    }

    /**
     * Get consultations.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getConsultations()
    {
        return $this->consultations;
    }

    public function __toString()
    {
        return sprintf('%s', $this->valeur);
    }
}
