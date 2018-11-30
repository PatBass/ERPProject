<?php

namespace KGC\ChatBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use KGC\Bundle\SharedBundle\Entity\Website;

/**
 * @category Entity
 *
 * @ORM\Table(name="chat_promotion")
 * @ORM\Entity(repositoryClass="KGC\ChatBundle\Repository\ChatPromotionRepository")
 */
class ChatPromotion
{
    const TYPE_CODE_PROMO = 1;

    const UNIT_TYPE_BONUS = 1;
    const UNIT_TYPE_PRICE = 2;
    const UNIT_TYPE_PERCENTAGE = 3;

    const MINUTE_RATIO = 60;
    const PRICE_RATIO = 100;

    const FORMULA_FILTER_NONE = 1;
    const FORMULA_FILTER_DISCOVERY = 2;
    const FORMULA_FILTER_STANDARD = 4;
    const FORMULA_FILTER_PREMIUM = 8;

    /**
     * @var int
     * @ORM\Column(name="id", type="integer", options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="name", type="string")
     */
    protected $name;

    /**
     * @var int
     * @ORM\Column(name="type", type="smallint", options={"unsigned"=true})
     */
    protected $type = self::TYPE_CODE_PROMO;

    /**
     * @var int
     * @ORM\Column(name="enabled", type="boolean")
     */
    protected $enabled = true;

    /**
     * @var string
     * @ORM\Column(name="promotion_code", type="string", options={"unsigned"=true}, nullable=true)
     */
    protected $promotionCode;

    /**
     * @var int
     * @ORM\Column(name="unit_type", type="smallint", options={"unsigned"=true})
     */
    protected $unitType;

    /**
     * @var int
     * @ORM\Column(name="unit", type="integer")
     */
    protected $unit;

    /**
     * @var \DateTime
     * @ORM\Column(name="start_date", type="date", nullable=true)
     */
    protected $startDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="end_date", type="date", nullable=true)
     */
    protected $endDate;

    /**
     * @ORM\ManyToOne(targetEntity="\KGC\Bundle\SharedBundle\Entity\Website")
     * @ORM\JoinColumn(name="website_id", referencedColumnName="web_id", nullable=false)
     */
    protected $website;

    /**
     * @ORM\Column(name="formula_filter", type="smallint", options={"unsigned"=true})
     */
    protected $formulaFilter;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return ChatPromotion
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set type
     *
     * @param integer $type
     *
     * @return ChatPromotion
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set enabled
     *
     * @param boolean $enabled
     *
     * @return ChatPromotion
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get enabled
     *
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set promotionCode
     *
     * @param string $promotionCode
     *
     * @return ChatPromotion
     */
    public function setPromotionCode($promotionCode)
    {
        $this->promotionCode = $promotionCode;

        return $this;
    }

    /**
     * Get promotionCode
     *
     * @return string
     */
    public function getPromotionCode()
    {
        return $this->promotionCode;
    }

    /**
     * Set unitType
     *
     * @param integer $unitType
     *
     * @return ChatPromotion
     */
    public function setUnitType($unitType)
    {
        $this->unitType = $unitType;

        return $this;
    }

    /**
     * Get unitType
     *
     * @return integer
     */
    public function getUnitType()
    {
        return $this->unitType;
    }

    /**
     * Set unit
     *
     * @param integer $unit
     *
     * @return ChatPromotion
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * Get unit
     *
     * @return integer
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     *
     * @return ChatPromotion
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     *
     * @return ChatPromotion
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return ChatPromotion
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set website
     *
     * @param \KGC\Bundle\SharedBundle\Entity\Website $website
     *
     * @return ChatPromotion
     */
    public function setWebsite(\KGC\Bundle\SharedBundle\Entity\Website $website)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * Get website
     *
     * @return \KGC\Bundle\SharedBundle\Entity\Website
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * Set formulaFilter
     *
     * @param boolean $formulaFilter
     *
     * @return ChatPromotion
     */
    public function setFormulaFilter($formulaFilter)
    {
        $this->formulaFilter = $formulaFilter;

        return $this;
    }

    /**
     * Get formulaFilter
     *
     * @return integer
     */
    public function getFormulaFilter()
    {
        return $this->formulaFilter;
    }

    /**
     * @param int $filter
     *
     * @return bool
     */
    public function hasFormulaFilter($filter)
    {
        return $this->getFormulaFilter() & $filter;
    }

    /**
     * @return array
     */
    public static function getFormulaFilterLabels()
    {
        return [
            self::FORMULA_FILTER_NONE => 'Sans formule',
            self::FORMULA_FILTER_DISCOVERY => 'Formule découverte',
            self::FORMULA_FILTER_STANDARD => 'Formules standard',
            self::FORMULA_FILTER_PREMIUM => 'Formules premium'
        ];
    }
}
