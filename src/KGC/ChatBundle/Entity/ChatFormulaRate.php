<?php

// src/KGC/ChatBundle/Entity/ChatFormulaRate.php


namespace KGC\ChatBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @category Entity
 *
 * @ORM\Table(name="chat_formula_rate")
 * @ORM\Entity(repositoryClass="KGC\ChatBundle\Repository\ChatFormulaRateRepository")
 * @Gedmo\Loggable(logEntryClass="KGC\CommonBundle\Entity\Log")
 */
class ChatFormulaRate
{
    /**
     * Represent the discovery rate.
     */
    const TYPE_DISCOVERY = 0;

    /**
     * Represent the standard rate.
     */
    const TYPE_STANDARD = 1;

    /**
     * Represent the premium rate.
     */
    const TYPE_PREMIUM = 2;

    /**
     * Represent the subscription rate.
     */
    const TYPE_SUBSCRIPTION = 3;

    /**
     * Represent the free offer rate.
     */
    const TYPE_FREE_OFFER = 4;

    /**
     * @var int
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var int
     * @ORM\Column(name="unit", type="integer")
     * @Gedmo\Versioned
     */
    protected $unit = 0;

    /**
     * @var int
     * @ORM\Column(name="bonus", type="integer")
     * @Gedmo\Versioned
     */
    protected $bonus = 0;

    /**
     * @var string
     * @ORM\Column(name="price", type="decimal", precision=5, scale=2)
     * @Gedmo\Versioned
     */
    protected $price;

    /**
     * @var int
     * @ORM\Column(name="type", type="smallint")
     */
    protected $type;

    /**
     * @var \DateTime
     * @ORM\Column(name="desactivation_date", type="datetime", nullable=true)
     */
    protected $desactivationDate;

    /**
     * @ORM\ManyToOne(targetEntity="\KGC\ChatBundle\Entity\ChatFormula", inversedBy="chatFormulaRates")
     * @ORM\JoinColumn(name="chat_formula_id", referencedColumnName="id", nullable=false)
     */
    protected $chatFormula;

    /**
     * @var bool
     * @ORM\Column(name="flexible", type="boolean")
     */
    protected $flexible;

    /**
     * @ORM\OneToMany(targetEntity="\KGC\ChatBundle\Entity\ChatPayment", mappedBy="chatFormulaRate")
     */
    protected $chatPayments;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->type = self::TYPE_STANDARD;
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
     * Set unit.
     *
     * @param int $unit
     *
     * @return this
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * Get unit.
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * Set bonus.
     *
     * @param int $bonus
     *
     * @return this
     */
    public function setBonus($bonus)
    {
        $this->bonus = $bonus;

        return $this;
    }

    /**
     * Get bonus.
     */
    public function getBonus()
    {
        return $this->bonus;
    }

    /**
     * Set price.
     *
     * @param decimal $price
     *
     * @return this
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price.
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set type.
     *
     * @param int $type
     *
     * @return this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set desactivationDate.
     *
     * @param \DateTime $desactivationDate
     *
     * @return this
     */
    public function setDesactivationDate(\DateTime $desactivationDate)
    {
        $this->desactivationDate = $desactivationDate;

        return $this;
    }

    /**
     * Get desactivationDate.
     */
    public function getDesactivationDate()
    {
        return $this->desactivationDate;
    }

    /**
     * Set chatFormula.
     *
     * @param ChatFormula $chatFormula
     *
     * @return this
     */
    public function setChatFormula(ChatFormula $chatFormula)
    {
        $this->chatFormula = $chatFormula;

        return $this;
    }

    /**
     * Get chatFormula.
     */
    public function getChatFormula()
    {
        return $this->chatFormula;
    }

    /**
     * Custom accessor to get unit + bonus.
     */
    public function getUnits()
    {
        return $this->unit + $this->bonus;
    }

    /**
     * Convert to JSON like array.
     */
    public function toJsonArray($deep = false)
    {
        $json = [
            'id' => (int) $this->getId(),
            'unit' => (int) $this->getUnit(),
            'bonus' => (int) $this->getBonus(),
            'price' => (float) $this->getPrice(),
            'is_discovery' => $this->isDiscovery(),
            'is_standard' => $this->isStandard(),
            'is_premium' => $this->isPremium(),
            'is_subscription' => $this->isSubscription(),
            'is_free_offer' => $this->isFreeOffer()
        ];

        if ($deep) {
            $json['chat_type'] = $this->getChatFormula()->getChatType()->toJsonArray();
        }

        return $json;
    }

    /**
     * Check the type of rate by price and bonus.
     */
    public function isDiscovery()
    {
        return $this->getType() === self::TYPE_DISCOVERY;
    }

    public function isStandard()
    {
        return $this->getType() === self::TYPE_STANDARD;
    }

    public function isPremium()
    {
        return $this->getType() === self::TYPE_PREMIUM;
    }

    public function isSubscription()
    {
        return $this->getType() === self::TYPE_SUBSCRIPTION;
    }

    public function isFreeOffer()
    {
        return $this->getType() === self::TYPE_FREE_OFFER;
    }

    public static function getTypes()
    {
        return array(
            self::TYPE_DISCOVERY => 'discovery',
            self::TYPE_STANDARD => 'standard',
            self::TYPE_PREMIUM => 'premium',
            self::TYPE_SUBSCRIPTION => 'subscription',
        );
    }

    /**
     * Add chatPayment.
     *
     * @param \KGC\ChatBundle\Entity\ChatPayment $chatPayment
     *
     * @return ChatFormulaRate
     */
    public function addChatPayment(\KGC\ChatBundle\Entity\ChatPayment $chatPayment)
    {
        $this->chatPayments[] = $chatPayment;

        return $this;
    }

    /**
     * Remove chatPayment.
     *
     * @param \KGC\ChatBundle\Entity\ChatPayment $chatPayment
     */
    public function removeChatPayment(\KGC\ChatBundle\Entity\ChatPayment $chatPayment)
    {
        $this->chatPayments->removeElement($chatPayment);
    }

    /**
     * Get chatPayments.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChatPayments()
    {
        return $this->chatPayments;
    }

    /**
     * @return string
     */
    public function getLibelleRecherche($withOffre = true)
    {
        $chatType = $this->getChatFormula()->getChatType()->getType();
        if ($this->getChatFormula()->getChatType()->getType() == ChatType::TYPE_QUESTION) {
            $unitLabel = 'questions';
            $ratio = 1;
        } else {
            $unitLabel = 'minutes';
            $ratio = 60;
        }

        switch ($this->getType()) {
            case self::TYPE_DISCOVERY:
                $typeLabel = $withOffre ? 'Offre découverte' : 'Découverte';
                break;
            case self::TYPE_STANDARD:
                $typeLabel = $withOffre ? 'Offre standard' : 'Standard';
                break;
            case self::TYPE_PREMIUM:
                $typeLabel = $withOffre ? 'Offre premium' : 'Premium';
                break;
            case self::TYPE_SUBSCRIPTION:
                $typeLabel = 'Abonnement';
                break;
            case self::TYPE_FREE_OFFER:
                $typeLabel = 'Offre gratuite';
                break;
            default:
                $typeLabel = 'Offre';
        }

        $bonusSuffix = $this->getBonus() ? ' (+'.$this->getBonus()/$ratio.')' : '';

        if ($this->getType() == self::TYPE_FREE_OFFER) {
            return sprintf('%s pour %d%s %s', $typeLabel, $this->getUnit() / $ratio, $bonusSuffix, $unitLabel);
        } else {
            return sprintf('%s %.2f€ pour %d%s %s', $typeLabel, $this->getPrice(), $this->getUnit() / $ratio, $bonusSuffix, $unitLabel);
        }
    }

    /**
     * @return string
     */
    public function getAdvancedLibelle()
    {
        return $this->getLibelleRecherche(false);
    }

    /**
     * Set flexible
     *
     * @param boolean $flexible
     *
     * @return ChatFormulaRate
     */
    public function setFlexible($flexible)
    {
        $this->flexible = $flexible;

        return $this;
    }

    /**
     * Get flexible
     *
     * @return boolean
     */
    public function getFlexible()
    {
        return $this->flexible;
    }
}
