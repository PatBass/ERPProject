<?php

namespace KGC\ChatBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @category Entity
 *
 * @ORM\Table(name="chat_room_formula_rate")
 * @ORM\Entity(repositoryClass="KGC\ChatBundle\Repository\ChatRoomFormulaRateRepository")
 */
class ChatRoomFormulaRate
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
     * @ORM\Column(name="start_date", type="datetime")
     */
    protected $startDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="end_date", type="datetime", nullable=true)
     */
    protected $endDate;

    /**
     * @ORM\ManyToOne(targetEntity="\KGC\ChatBundle\Entity\ChatFormulaRate")
     * @ORM\JoinColumn(name="chat_formula_rate_id", referencedColumnName="id", nullable=false)
     */
    protected $chatFormulaRate;

    /**
     * @ORM\ManyToOne(targetEntity="\KGC\ChatBundle\Entity\ChatRoom", inversedBy="chatRoomFormulaRates")
     * @ORM\JoinColumn(name="chat_room_id", referencedColumnName="id", nullable=false)
     */
    protected $chatRoom;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="\KGC\ChatBundle\Entity\ChatRoomConsumption", mappedBy="chatRoomFormulaRate")
     */
    protected $chatRoomConsumptions;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->startDate = new \DateTime();
        $this->chatRoomConsumptions = new ArrayCollection();
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
     * Set startDate.
     *
     * @param \DateTime $startDate
     *
     * @return this
     */
    public function setStartDate(\DateTime $startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate.
     *
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set endDate.
     *
     * @param \DateTime $endDate
     *
     * @return this
     */
    public function setEndDate(\DateTime $endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate.
     *
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set chatFormulaRate.
     *
     * @param ChatFormulaRate $chatFormulaRate
     *
     * @return this
     */
    public function setChatFormulaRate(ChatFormulaRate $chatFormulaRate)
    {
        $this->chatFormulaRate = $chatFormulaRate;

        return $this;
    }

    /**
     * Get chatFormulaRate.
     */
    public function getChatFormulaRate()
    {
        return $this->chatFormulaRate;
    }

    /**
     * Set chat room.
     *
     * @param ChatRoom $chatRoom
     *
     * @return this
     */
    public function setChatRoom(ChatRoom $chatRoom)
    {
        $this->chatRoom = $chatRoom;

        return $this;
    }

    /**
     * Get chat room.
     */
    public function getChatRoom()
    {
        return $this->chatRoom;
    }

    /**
     * Convert to JSON like array.
     */
    public function toJsonArray()
    {
        return array(
            'start_date' => $this->startDate->getTimestamp(),
            'end_date' => ($this->endDate !== null ? $this->endDate->getTimestamp() : null),
        );
    }

    /**
     * Set chatRoomConsumptions.
     *
     * @param ArrayCollection
     *
     * @return this
     */
    public function setChatRoomConsumptions($chatRoomConsumptions)
    {
        $this->chatRoomConsumptions = $chatRoomConsumptions;

        return $this;
    }

    /**
     * Get chatRoomConsumptions.
     *
     * @return ArrayCollection
     */
    public function getChatRoomConsumptions()
    {
        return $this->chatRoomConsumptions;
    }

    /**
     * Add chatRoomConsumption.
     *
     * @return this
     */
    public function addChatRoomConsumption(ChatRoomConsumption $chatRoomConsumption)
    {
        $this->chatRoomConsumptions[] = $chatRoomConsumption;

        return $this;
    }

    /**
     * Remove chatRoomConsumption.
     *
     * @return this
     */
    public function removeChatRoomConsumption(ChatRoomConsumption $chatRoomConsumption)
    {
        $this->chatRoomConsumptions->removeElement($chatRoomConsumption);

        return $this;
    }

    /**
     * Get amount of already consumed units.
     */
    public function getConsumedUnits()
    {
        $total = 0;
        foreach ($this->getChatRoomConsumptions() as $chatRoomConsumption) {
            $total += $chatRoomConsumption->getUnit();
        }

        return $total;
    }
}
