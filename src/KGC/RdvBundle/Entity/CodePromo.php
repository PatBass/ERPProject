<?php

namespace KGC\RdvBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use KGC\Bundle\SharedBundle\Entity\Website;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;

/**
 * Entité CodePromo.
 *
 * @category Entity
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 *
 * @ORM\Table(name="codepromo")
 * @ORM\Entity(repositoryClass="KGC\RdvBundle\Repository\CodePromoRepository")
 */
class CodePromo
{
    use ORMBehaviors\Timestampable\Timestampable;
    /**
     * @var int
     * @ORM\Column(name="cpm_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="cpm_code", type="string", length=15)
     */
    protected $code;

    /**
     * @var string
     * @ORM\Column(name="cpm_desc", type="string", length=100)
     */
    protected $desc;

    /**
     * @var Website
     *
     * @ORM\ManyToOne(targetEntity="\KGC\Bundle\SharedBundle\Entity\Website")
     * @ORM\JoinColumn(nullable=false, name="cpm_website", referencedColumnName="web_id")
     */
    protected $website;

    /**
     * @var bool
     * @ORM\Column(name="cpm_enabled", type="boolean" )
     */
    protected $enabled = true;

    /**
     * @var date
     * @ORM\Column(name="cpm_disabled_date", type="date", nullable=true)
     */
    protected $disabled_date = null;

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
     * @param $code
     *
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param $desc
     *
     * @return $this
     */
    public function setDesc($desc)
    {
        $this->desc = $desc;

        return $this;
    }

    /**
     * Get desc.
     *
     * @return string
     */
    public function getDesc()
    {
        return $this->desc;
    }

    /**
     * @return Website
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param Website $website
     */
    public function setWebsite($website)
    {
        $this->website = $website;
    }

    /**
     * Set enabled.
     *
     * @param bool $enabled
     *
     * @return \KGC\RdvBundle\Entity\Support
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
        if (!$enabled) {
            $this->setDisabledDate(new \Datetime());
        } else {
            $this->setDisabledDate(null);
        }

        return $this;
    }

    /**
     * Get enabled.
     *
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
     * Get active.
     *
     * @return string
     */
    public function getDisabledDate()
    {
        return $this->disabled_date;
    }

    public function __toString()
    {
        return $this->code;
    }
}
