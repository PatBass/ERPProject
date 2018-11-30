<?php
// src/KGC/RdvBundle/Entity/FormUrl.php

namespace KGC\RdvBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use KGC\Bundle\SharedBundle\Entity\Website;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité FormUrl.
 *
 * @category Entity
 *
 * @author Laurène Dourdin
 *
 * @ORM\Table(name="form_url")
 * @ORM\Entity(repositoryClass="KGC\RdvBundle\Repository\FormUrlRepository")
 */
class FormUrl
{
    /**
     * @var int
     * @ORM\Column(name="url_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="url_label", type="string")
     * @Assert\NotBlank()
     */
    protected $label;

    /**
     * @var \KGC\Bundle\SharedBundle\Entity\Website
     * @ORM\ManyToOne(targetEntity="\KGC\Bundle\SharedBundle\Entity\Website", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="web_id", name="url_website", nullable = false)
     * @Assert\NotBlank()
     */
    protected $website;

    /**
     * @var \KGC\RdvBundle\Entity\Source | null
     * @ORM\ManyToOne(targetEntity="\KGC\RdvBundle\Entity\Source", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="src_id", name="url_source", nullable = true)
     */
    protected $source = null;
    
    /**
     * @var ArrayCollection(KGC\RdvBundle\Entity\RDV)
     * @ORM\OneToMany(targetEntity="KGC\RdvBundle\Entity\RDV", mappedBy="form_url")
     */
    protected $consultations;

    public function __construct()
    {
        $this->consultations = new ArrayCollection();
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
     * Get label.
     *
     * @return int
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set label.
     *
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Set source.
     *
     * @param string $source
     *
     * @return $this
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source.
     *
     * @return \KGC\RdvBundle\Entity\Source | null
     */
    public function getSource()
    {
        return $this->source;
    }

    public function getSourceId()
    {
        if(is_object($this->source)){
            return $this->source->getId();
        }
    }

    /**
     * @param Website $website
     *
     * @return $this
     */
    public function setWebsite(Website $website)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * Get website.
     *
     * @return \KGC\Bundle\SharedBundle\Entity\Website
     */
    public function getWebsite()
    {
        return $this->website;
    }

    public function getWebsiteId()
    {
        return $this->website->getId();
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

    public function get($property)
    {
        $method = 'get'.ucfirst($property);

        if (method_exists($this, $method)) {
            return $this->$method();
        } else {
            return;
        }
    }

    public function __toString()
    {
        return $this->label;
    }
}
