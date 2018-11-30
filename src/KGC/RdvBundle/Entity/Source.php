<?php
// src/KGC/RdvBundle/Entity/Source.php

namespace KGC\RdvBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use KGC\Bundle\SharedBundle\Entity\Website;

/**
 * @ORM\Table(name="consultation_source")
 * @ORM\Entity(repositoryClass="KGC\RdvBundle\Repository\SourceRepository")
 */
class Source
{
    const SOURCE_ADWORDS = 'adwords';
    const SOURCE_FACEBOOK_ADDS = 'facebook_adds';
    const SOURCE_AFFILBASE = 'affilbase';
    const SOURCE_INSTAGRAM = 'instagram';
    const SOURCE_FACEBOOK = 'facebook';

    /**
     * @var int
     * @ORM\Column(name="src_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="src_code", type="string", length=50, nullable=false)
     */
    protected $code;

    /**
     * @var string
     *
     * @ORM\Column(name="src_label", type="string", length=128, nullable=false)
     */
    protected $label;

    /**
     * @var bool
     *
     * @ORM\Column(name="src_has_gclid", type="boolean", nullable=false)
     */
    protected $hasGclid = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="src_affiliate_allowed", type="boolean", nullable=false)
     */
    protected $affiliateAllowed = false;

    /**
     * @var bool
     * @ORM\Column(name="src_enabled", type="boolean")
     */
    protected $enabled = true;

    /**
     * @var date
     * @ORM\Column(name="sup_disabled_date", type="date", nullable=true)
     */
    protected $disabled_date = null;
    
    /**
     * @var ArrayCollection(\KGC\Bundle\SharedBundle\Entity\Website)
     * @ORM\ManyToMany(targetEntity="\KGC\Bundle\SharedBundle\Entity\Website")
     * @ORM\JoinTable(name="source_website",
     *   joinColumns={@ORM\JoinColumn(name="source_id", referencedColumnName="src_id")},
     *   inverseJoinColumns={@ORM\JoinColumn(name="website_id", referencedColumnName="web_id")}
     * )
     */
    protected $websites;
    
    /**
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

        return $this;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return bool
     */
    public function isHasGclid()
    {
        return $this->hasGclid;
    }

    /**
     * @param bool $hasGclid
     */
    public function setHasGclid($hasGclid)
    {
        $this->hasGclid = $hasGclid;

        return $this;
    }

    /**
     * @param bool $affiliateAllowed
     */
    public function setAffiliateAllowed($affiliateAllowed)
    {
        $this->affiliateAllowed = $affiliateAllowed;

        return $this;
    }

    /**
     * @return bool
     */
    public function getAffiliateAllowed()
    {
        return $this->affiliateAllowed;
    }
    
    /**
     * @param bool $actif
     *
     * @return $this
     */
    public function setEnabled($actif)
    {
        $this->enabled = $actif;
        if (!$actif) {
            $this->setDisabledDate(new \Datetime());
        } else {
            $this->setDisabledDate(null);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param \DateTime $date
     *
     * @return $this
     */
    public function setDisabledDate($date)
    {
        $this->disabled_date = $date;

        return $this;
    }

    /**
     * @return \Datetime
     */
    public function getDisabledDate()
    {
        return $this->disabled_date;
    }
    
    /**
     * Add websites.
     *
     * @param \KGC\Bundle\SharedBundle\Entity\Website $website
     *
     * @return \KGC\RdvBundle\Entity\Source
     */
    public function addWebsite(Website $website)
    {
        $this->websites[] = $website;

        return $this;
    }

    /**
     * Remove websites.
     *
     * @param \KGC\Bundle\SharedBundle\Entity\Website $website
     */
    public function removeWebsite(Website $website)
    {
        $this->websites->removeElement($website);
    }

    /**
     * Get websites.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getWebsites()
    {
        return $this->websites;
    }

    /**
     * Get websitesIds.
     *
     * @return string
     */
    public function getWebsitesIds()
    {
        $s = '';
        $i = 1;
        $cn = $this->websites->count();
        foreach($this->websites as $website){
            $s .= $website->getId().($i != $cn ? ',' : '');
            $i++;
        }
        
        return $s;
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

    public function getArrayAssociation()
    {
        $toCompare = [
            "adwords"       => [ "adwords" ],
            "facebook_adds" => [ "facebook adds", "facebook-adds", "facebook", "facebook_adds" ],
            "naturel"       => [ "naturelle", "naturel" ],
            "swarmiz"       => [ "affil 1 swarmiz", "affil-1-swarmiz", "affil_1_swarmiz", "affil 1", "affil-1", "affil_1" ],
            "regie_astro"   => [ "affil 2 régie astro", "affil 2 regie astro", "affil-2-regie-astro", "affil_2_regie_astro", "affil 2", "affil-2", "affil_2" ],
            "affil_base"    => [ "affil base", "affil-base", "affil_base", "affilbase" ],
            "affil_vps"     => [ "affil vps", "affil-vps", "affil_vps" ],
            "taboola"       => [ "taboola" ],
            "weedoit"       => [ "weedoit" ],
            "concours"      => [ "concours" ],
            "base_externe"  => [ "base externe", "base-externe", "base_externe", "external_base" ],
            "londres"       => [ "londres" ],
            "reflexcash"    => [ "reflexcash", "reflex cache", "reflex-cache", "reflex_cache", "reflexcache" ],
            "outbrain"      => [ "outbrain" ]
        ];
        $code = strtolower($this->getCode());
        if(empty($toCompare[$code])) {
            $toCompare[$code] = [$code];
        }
        return $toCompare[$code];
    }

    public function isSourceAvailable($label)
    {
        $toCompare = $this->getArrayAssociation();
        $label = strtolower($label);

        if (in_array($label, $toCompare)) {
            return true;
        }

        return false;
    }
}
