<?php

// src/KGC/ChatBundle/Entity/ChatFormula.php


namespace KGC\ChatBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use KGC\Bundle\SharedBundle\Entity\Website;

/**
 * @category Entity
 *
 * @ORM\Table(name="chat_formula")
 * @ORM\Entity(repositoryClass="KGC\ChatBundle\Repository\ChatFormulaRepository")
 */
class ChatFormula implements \KGC\Bundle\SharedBundle\Entity\Interfaces\ChatFormula
{
    /**
     * @var int
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \DateTime
     * @ORM\Column(name="desactivation_date", type="datetime", nullable=true)
     */
    protected $desactivationDate;

    /**
     * @ORM\ManyToOne(targetEntity="\KGC\Bundle\SharedBundle\Entity\Website", inversedBy="chatFormulas")
     * @ORM\JoinColumn(name="website_id", referencedColumnName="web_id", nullable=false)
     */
    protected $website;

    /**
     * @ORM\ManyToOne(targetEntity="\KGC\ChatBundle\Entity\ChatType", inversedBy="chatFormulas")
     * @ORM\JoinColumn(name="chat_type_id", referencedColumnName="id", nullable=false)
     */
    protected $chatType;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="KGC\ChatBundle\Entity\ChatFormulaRate", mappedBy="chatFormula")
     */
    protected $chatFormulaRates;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->chatFormulaRates = new ArrayCollection();
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
     * Set website.
     *
     * @param Website $website
     *
     * @return this
     */
    public function setWebsite(Website $website)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * Get website.
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * Set chatType.
     *
     * @param int $chatType
     *
     * @return this
     */
    public function setChatType(ChatType $chatType)
    {
        $this->chatType = $chatType;

        return $this;
    }

    /**
     * Get chatType.
     */
    public function getChatType()
    {
        return $this->chatType;
    }

    /**
     * Add chatFormulaRate.
     *
     * @param ChatFormulaRate $chatFormulaRate
     *
     * @return ChatType
     */
    public function addChatFormulaRate(ChatFormulaRate $chatFormulaRate)
    {
        $this->chatFormulaRates[] = $chatFormulaRate;

        return $this;
    }

    /**
     * Remove chatFormulaRate.
     *
     * @param ChatFormulaRate $chatFormulaRate
     */
    public function removeChatFormulaRate(ChatFormulaRate $chatFormulaRate)
    {
        $this->chatFormulaRates->removeElement($chatFormulaRate);
    }

    /**
     * Get chatFormulaRates.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChatFormulaRates()
    {
        return $this->chatFormulaRates;
    }

    /**
     * Set chatFormulaRates.
     *
     * @param ArrayCollection
     *
     * @return this
     */
    public function setChatFormulaRates($chatFormulaRates)
    {
        $this->chatFormulaRates = $chatFormulaRates;

        return $this;
    }

    /**
     * Check if this formula has discovery offer.
     */
    public function hasDiscoveryOffer()
    {
        $has_discovery_offer = false;
        foreach ($this->getChatFormulaRates() as $chatFormulaRate) {
            if ($chatFormulaRate->isDiscovery()) {
                $has_discovery_offer = true;
                break;
            }
        }

        return $has_discovery_offer;
    }

    /**
     * Apply filter on chat formula rates
     * This methods does not update any values but simply return a filtered ArrayCollection.
     *
     * @param mixed $filter The filter to apply. Could be a string ('discovery', 'standard' or 'premium', 'subsciption') or an array of string
     *
     * @return filtered array
     */
    public function filterChatFormulaRates($filters)
    {
        if (!(is_array($filters) || is_string($filters))) {
            return;
        }

        $filtered = new ArrayCollection();

        if (is_array($filters)) {
            foreach ($filters as $filter) {
                $filtered = new ArrayCollection(array_merge($filtered->toArray(), $this->filterChatFormulaRates($filter)->toArray()));
            }
        } elseif (is_string($filters)) {
            if (in_array($filters, ChatFormulaRate::getTypes())) {
                $temp_chat_formula_rates = clone $this->getChatFormulaRates();
                foreach ($temp_chat_formula_rates as $chatFormulaRate) {
                    $method = 'is'.ucfirst($filters);
                    if (method_exists($chatFormulaRate, $method) && $chatFormulaRate->$method()) {
                        $filtered->add($chatFormulaRate);
                    }
                }
            }
        }

        return $filtered;
    }

    /**
     * Accessibility methods for better lisibility.
     */
    public function keepOnlyDiscoveryOffers()
    {
        $this->setChatFormulaRates($this->filterChatFormulaRates('discovery'));

        return $this;
    }

    public function keepOnlyStandardOffers()
    {
        $this->setChatFormulaRates($this->filterChatFormulaRates('standard'));

        return $this;
    }

    public function removeDiscoveryOffers()
    {
        $types = ChatFormulaRate::getTypes();
        unset($types[ChatFormulaRate::TYPE_DISCOVERY]);
        $this->setChatFormulaRates($this->filterChatFormulaRates($types));

        return $this;
    }

    public function removeDiscoveryAndSubscriptionOffers()
    {
        $types = ChatFormulaRate::getTypes();
        unset($types[ChatFormulaRate::TYPE_DISCOVERY]);
        unset($types[ChatFormulaRate::TYPE_SUBSCRIPTION]);
        $this->setChatFormulaRates($this->filterChatFormulaRates($types));

        return $this;
    }
}
