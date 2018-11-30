<?php

namespace KGC\ChatBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use KGC\Bundle\SharedBundle\Entity\Client;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * @category Entity
 *
 * @ORM\Table(name="chat_payment")
 * @ORM\Entity(repositoryClass="KGC\ChatBundle\Repository\ChatPaymentRepository")
 */
class ChatPayment implements \KGC\Bundle\SharedBundle\Entity\Interfaces\ChatPayment
{
    /**
     * @var int
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="\KGC\PaymentBundle\Entity\Payment")
     * @ORM\JoinColumn(name="payment_id", referencedColumnName="id", nullable=true)
     */
    protected $payment;

    /**
     * @var int
     * @ORM\Column(name="amount", type="integer")
     */
    protected $amount;

    /**
     * @var int
     * @ORM\Column(name="unit", type="integer")
     */
    protected $unit;

    /**
     * @var \DateTime
     * @ORM\Column(name="date", type="datetime")
     */
    protected $date;

    /**
     * @ORM\ManyToOne(targetEntity="\KGC\ChatBundle\Entity\ChatFormulaRate", inversedBy="chatPayments")
     * @ORM\JoinColumn(name="chat_formula_rate_id", referencedColumnName="id", nullable=false)
     */
    protected $chatFormulaRate;

    /**
     * @ORM\ManyToOne(targetEntity="\KGC\Bundle\SharedBundle\Entity\Client")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id", nullable=false)
     */
    protected $client;

    /**
     * @ORM\OneToOne(targetEntity="ChatPayment")
     * @ORM\JoinColumn(name="previous_payment_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $previousPayment;

    /**
     * @var string
     * @ORM\Column(name="commentary", type="string", length=100, nullable=true)
     * @Assert\Length(
     *      max = 100,
     *      maxMessage = "Le commentaire doit faire moins de {{ limit }} caractères"
     * )
     */
    protected $commentary;

    /**
     * @var MoyenPaiement
     *
     * @ORM\ManyToOne(targetEntity="\KGC\RdvBundle\Entity\MoyenPaiement")
     * @ORM\JoinColumn(name="payment_method_id", referencedColumnName="mpm_id", nullable=true)
     */
    protected $paymentMethod;

    /**
     * @ORM\ManyToOne(targetEntity="\KGC\RdvBundle\Entity\TPE")
     * @ORM\JoinColumn(name="tpe_id", referencedColumnName="tpe_id", nullable=true)
     */
    protected $tpe;

    /**
     * @ORM\ManyToOne(targetEntity="\KGC\ChatBundle\Entity\ChatPromotion")
     * @ORM\JoinColumn(name="promotion_id", referencedColumnName="id", nullable=true)
     */
    protected $promotion;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="\KGC\ChatBundle\Entity\ChatRoomConsumption", mappedBy="chatPayment")
     */
    protected $chatRoomConsumptions;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false, length=50)
     */
    protected $state = self::STATE_ERROR;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $opposedDate = null;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->date = new \DateTime();
        $this->chatRoomConsumptions = new ArrayCollection();
    }

    public static function getStates($onlyBasic = false)
    {
        $states = [
            self::STATE_DONE => 'chat.payment.state.done',
            self::STATE_ERROR => 'chat.payment.state.error',
        ];

        if (!$onlyBasic) {
            $states[self::STATE_OPPOSED] = 'chat.payment.state.opposed';
            $states[self::STATE_REFUNDED] = 'chat.payment.state.refunded';
        }

        return $states;
    }

    public static function getRefusedStates()
    {
        return [self::STATE_ERROR];
    }

    public static function getOpposedStates()
    {
        return [self::STATE_OPPOSED, self::STATE_REFUNDED];
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
     * Get ChatFormulaRate.
     */
    public function getChatFormulaRate()
    {
        return $this->chatFormulaRate;
    }

    /**
     * Set client.
     *
     * @param \KGC\Bundle\SharedBundle\Entity\Client $client
     *
     * @return this
     */
    public function setClient(Client $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get client.
     *
     * @return \KGC\Bundle\SharedBundle\Entity\Client
     */
    public function getClient()
    {
        return $this->client;
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
     * Calculate the remaining units availables for this payment, based on chat room consumptions and chat formula rate.
     *
     * @return int
     */
    public function getRemainingUnits()
    {
        $remaining = $this->getUnit();
        foreach ($this->getChatRoomConsumptions() as $crc) {
            $remaining -= $crc->getUnit();
        }
        if ($remaining < 0) {
            $remaining = 0;
        }

        return $remaining;
    }

    /**
     * Set payment.
     *
     * @param \KGC\PaymentBundle\Entity\Payment $payment
     *
     * @return ChatPayment
     */
    public function setPayment(\KGC\PaymentBundle\Entity\Payment $payment = null)
    {
        $this->payment = $payment;

        return $this;
    }

    /**
     * Get payment.
     *
     * @return \KGC\PaymentBundle\Entity\Payment
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param $state
     *
     * @return $this
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getOpposedDate()
    {
        return $this->opposedDate;
    }

    /**
     * @param $opposedDate
     *
     * @return $this
     */
    public function setOpposedDate($opposedDate)
    {
        $this->opposedDate = $opposedDate;

        return $this;
    }

    public function isDone()
    {
        return self::STATE_DONE === $this->state;
    }

    public function isError()
    {
        return self::STATE_ERROR === $this->state;
    }

    public function isOpposed()
    {
        return self::STATE_OPPOSED === $this->state;
    }

    public function getStateLabel()
    {
        $states = static::getStates();

        return $states[$this->state];
    }

    public function getOpposedDateFormatted()
    {
        return $this->opposedDate && $this->state === self::STATE_OPPOSED ? $this->opposedDate->format('d/m/Y') : '';
    }

    /**
     * Set amount
     *
     * @param integer $amount
     *
     * @return ChatPayment
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return integer
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set previousPayment
     *
     * @param ChatPayment $previousPayment
     *
     * @return ChatPayment
     */
    public function setPreviousPayment(ChatPayment $previousPayment = null)
    {
        $this->previousPayment = $previousPayment;

        return $this;
    }

    /**
     * Get previousPayment
     *
     * @return ChatPayment
     */
    public function getPreviousPayment()
    {
        return $this->previousPayment;
    }

    /**
     * Set unit
     *
     * @param integer $unit
     *
     * @return ChatPayment
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
     * Set commentary
     *
     * @param string $commentary
     *
     * @return ChatPayment
     */
    public function setCommentary($commentary)
    {
        $this->commentary = $commentary;

        return $this;
    }

    /**
     * Get commentary
     *
     * @return string
     */
    public function getCommentary()
    {
        return $this->commentary;
    }

    /**
     * @return bool
     */
    public function isEditable()
    {
        return $this->getPaymentMethod() !== null || $this->getChatFormulaRate()->getFlexible();
    }

    /**
     * Set paymentMethod
     *
     * @param \KGC\RdvBundle\Entity\MoyenPaiement $paymentMethod
     *
     * @return ChatPayment
     */
    public function setPaymentMethod(\KGC\RdvBundle\Entity\MoyenPaiement $paymentMethod = null)
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    /**
     * Get paymentMethod
     *
     * @return \KGC\RdvBundle\Entity\MoyenPaiement
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * Set tpe
     *
     * @param \KGC\RdvBundle\Entity\TPE $tpe
     *
     * @return ChatPayment
     */
    public function setTpe(\KGC\RdvBundle\Entity\TPE $tpe = null)
    {
        $this->tpe = $tpe;

        return $this;
    }

    /**
     * Get tpe
     *
     * @return \KGC\RdvBundle\Entity\TPE
     */
    public function getTpe()
    {
        return $this->tpe;
    }

    /**
     * Set promotion
     *
     * @param \KGC\ChatBundle\Entity\ChatPromotion $promotion
     *
     * @return ChatPayment
     */
    public function setPromotion(\KGC\ChatBundle\Entity\ChatPromotion $promotion = null)
    {
        $this->promotion = $promotion;

        return $this;
    }

    /**
     * Get promotion
     *
     * @return \KGC\ChatBundle\Entity\ChatPromotion
     */
    public function getPromotion()
    {
        return $this->promotion;
    }
}
