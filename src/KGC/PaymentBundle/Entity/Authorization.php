<?php

namespace KGC\PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model\Timestampable\Timestampable;

/**
 * @ORM\Table(name="payment_authorization")
 * @ORM\Entity(repositoryClass="KGC\PaymentBundle\Repository\AuthorizationRepository")
 */
class Authorization
{
    use Timestampable;

    /**
     * @var int
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \KGC\Bundle\SharedBundle\Entity\Client
     * @ORM\ManyToOne(targetEntity="\KGC\Bundle\SharedBundle\Entity\Client", cascade={"persist"}))
     * @ORM\JoinColumn(nullable=false, name="client_id", referencedColumnName="id")
     */
    protected $client;

    /**
     * @var Payment
     * @ORM\ManyToOne(targetEntity="\KGC\PaymentBundle\Entity\Payment")
     * @ORM\JoinColumn(nullable=false, name="authorize_payment_id", referencedColumnName="id")
     */
    protected $authorizePayment;

    /**
     * @var float
     * @ORM\Column(name="authorized_amount", type="decimal", precision=5, scale=2, nullable=false)
     */
    protected $authorizedAmount;

    /**
     * @var Payment
     * @ORM\ManyToOne(targetEntity="\KGC\PaymentBundle\Entity\Payment")
     * @ORM\JoinColumn(nullable=true, name="capture_payment_id", referencedColumnName="id", nullable=true)
     */
    protected $capturePayment;

    /**
     * @var float
     * @ORM\Column(name="captured_amount", type="decimal", precision=5, scale=2, nullable=true)
     */
    protected $capturedAmount;

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
     * Set client.
     *
     * @param \KGC\Bundle\SharedBundle\Entity\Client $client
     *
     * @return Adresse
     */
    public function setClient(\KGC\Bundle\SharedBundle\Entity\Client $client)
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
     * Set authorizePayment
     *
     * @param \KGC\PaymentBundle\Entity\Payment $authorizePayment
     *
     * @return Authorization
     */
    public function setAuthorizePayment(\KGC\PaymentBundle\Entity\Payment $authorizePayment)
    {
        $this->authorizePayment = $authorizePayment;

        return $this;
    }

    /**
     * Get authorizePayment
     *
     * @return \KGC\PaymentBundle\Entity\Payment
     */
    public function getAuthorizePayment()
    {
        return $this->authorizePayment;
    }

    /**
     * Set authorizedAmount
     *
     * @param float $authorizedAmount
     *
     * @return Authorization
     */
    public function setAuthorizedAmount($authorizedAmount)
    {
        $this->authorizedAmount = $authorizedAmount;

        return $this;
    }

    /**
     * Get authorizedAmount
     *
     * @return float
     */
    public function getAuthorizedAmount()
    {
        return $this->authorizedAmount;
    }

    /**
     * Set capturePayment
     *
     * @param \KGC\PaymentBundle\Entity\Payment $capturePayment
     *
     * @return Authorization
     */
    public function setCapturePayment(\KGC\PaymentBundle\Entity\Payment $capturePayment)
    {
        $this->capturePayment = $capturePayment;

        return $this;
    }

    /**
     * Get capturePayment
     *
     * @return \KGC\PaymentBundle\Entity\Payment
     */
    public function getCapturePayment()
    {
        return $this->capturePayment;
    }

    /**
     * Set capturedAmount
     *
     * @param float $capturedAmount
     *
     * @return Authorization
     */
    public function setCapturedAmount($capturedAmount)
    {
        $this->capturedAmount = $capturedAmount;

        return $this;
    }

    /**
     * Get capturedAmount
     *
     * @return float
     */
    public function getCapturedAmount()
    {
        return $this->capturedAmount;
    }

    /**
     * Get authorization tpe
     *
     * @return \KGC\RdvBundle\Entity\TPE
     */
    public function getTpe()
    {
        return $this->authorizePayment->getTpe();
    }

    /**
     * @return bool
     */
    public function isCancelled()
    {
        return $this->getCapturePayment() !== null && $this->getCapturedAmount() == 0;
    }
}
