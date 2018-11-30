<?php

namespace KGC\ChatBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @category Entity
 *
 * @ORM\Table(name="chat_room_consumption")
 * @ORM\Entity(repositoryClass="KGC\ChatBundle\Repository\ChatRoomConsumptionRepository")
 */
class ChatRoomConsumption
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
     * @ORM\Column(name="date", type="datetime")
     */
    protected $date;

    /**
     * @var int
     * @ORM\Column(name="unit", type="integer")
     */
    protected $unit;

    /**
     * @ORM\ManyToOne(targetEntity="\KGC\ChatBundle\Entity\ChatRoomFormulaRate", inversedBy="chatRoomConsumptions")
     * @ORM\JoinColumn(name="chat_room_formula_rate_id", referencedColumnName="id", nullable=false)
     */
    protected $chatRoomFormulaRate;

    /**
     * @ORM\ManyToOne(targetEntity="\KGC\ChatBundle\Entity\ChatPayment", inversedBy="chatRoomConsumptions")
     * @ORM\JoinColumn(name="chat_payment_id", referencedColumnName="id", nullable=false)
     */
    protected $chatPayment;

    /**
     * Constructeur.
     */
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
     * Set date.
     *
     * @param \DateTime $date
     *
     * @return \KGC\ChatBundle\Entity\ChatPayment
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
     * Set chatRoomFormulaRate.
     *
     * @param ChatRoomFormulaRate $chatRoomFormulaRate
     *
     * @return this
     */
    public function setChatRoomFormulaRate(ChatRoomFormulaRate $chatRoomFormulaRate)
    {
        $this->chatRoomFormulaRate = $chatRoomFormulaRate;

        return $this;
    }

    /**
     * Get chatRoomFormulaRate.
     */
    public function getChatRoomFormulaRate()
    {
        return $this->chatRoomFormulaRate;
    }

    /**
     * Set chatPayment.
     *
     * @param ChatPayment $chatPayment
     *
     * @return this
     */
    public function setChatPayment(ChatPayment $chatPayment)
    {
        $this->chatPayment = $chatPayment;

        return $this;
    }

    /**
     * Get chatPayment.
     */
    public function getChatPayment()
    {
        return $this->chatPayment;
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
}
