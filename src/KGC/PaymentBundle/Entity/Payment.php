<?php

namespace KGC\PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Payum\Core\Model\Payment as BasePayment;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;

/**
 * @ORM\Table(name="payment")
 * @ORM\Entity(repositoryClass="KGC\PaymentBundle\Repository\PaymentRepository")
 */
class Payment extends BasePayment
{
    use ORMBehaviors\Timestampable\Timestampable;

    const TAG_CBI = 1;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="\KGC\PaymentBundle\Entity\PaymentAlias")
     * @ORM\JoinColumn(name="alias_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $paymentAlias;

    /**
     * @ORM\ManyToOne(targetEntity="\KGC\RdvBundle\Entity\TPE")
     * @ORM\JoinColumn(name="tpe_id", referencedColumnName="tpe_id", nullable=false)
     */
    protected $tpe;

    /**
     * @ORM\Column(name="tags", type="integer", options={"default" = 0})
     */
    protected $tags = 0;

    /**
     * @ORM\Column(name="exception", type="text", nullable=true)
     */
    protected $exception;

    /**
     * @ORM\Column(name="response", type="text", nullable=true)
     */
    protected $response;

    /**
     * @ORM\ManyToOne(targetEntity="\KGC\PaymentBundle\Entity\Payment")
     * @ORM\JoinColumn(name="original_payment_id", referencedColumnName="id", nullable=true)
     */
    protected $originalPayment = null;

    /**
     * @ORM\ManyToOne(targetEntity="\KGC\PaymentBundle\Entity\Payment")
     * @ORM\JoinColumn(name="last_payment_id", referencedColumnName="id", nullable=true)
     */
    protected $lastPayment = null;

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
     * Set tpe.
     *
     * @param \KGC\RdvBundle\Entity\TPE $tpe
     *
     * @return Payment
     */
    public function setTpe(\KGC\RdvBundle\Entity\TPE $tpe)
    {
        $this->tpe = $tpe;

        return $this;
    }

    /**
     * Get tpe.
     *
     * @return \KGC\RdvBundle\Entity\TPE
     */
    public function getTpe()
    {
        return $this->tpe;
    }

    /**
     * Set paymentAlias
     *
     * @param \KGC\PaymentBundle\Entity\PaymentAlias $paymentAlias
     *
     * @return Payment
     */
    public function setPaymentAlias(\KGC\PaymentBundle\Entity\PaymentAlias $paymentAlias = null)
    {
        $this->paymentAlias = $paymentAlias;

        return $this;
    }

    /**
     * Get paymentAlias
     *
     * @return \KGC\PaymentBundle\Entity\PaymentAlias
     */
    public function getPaymentAlias()
    {
        return $this->paymentAlias;
    }

    /**
     * Set tags
     *
     * @param integer $tags
     *
     * @return Payment
     */
    public function setTags($tags)
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Get tags
     *
     * @return integer
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param integer $tag
     * @return Payment
     */
    public function addTag($tag)
    {
        $this->setTags($this->getTags()|$tag);
    }

    /**
     * @param integer $tag
     * @return bool
     */
    public function hasTag($tag)
    {
        return $this->getTags() & $tag != 0;
    }

    /**
     * Set exception
     *
     * @param string $exception
     *
     * @return Payment
     */
    public function setException($exception)
    {
        $this->exception = $exception;

        return $this;
    }

    /**
     * Get exception
     *
     * @return string
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * Set response
     *
     * @param string $response
     *
     * @return Payment
     */
    public function setResponse($response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Get response
     *
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Set originalPayment
     *
     * @param \KGC\PaymentBundle\Entity\Payment $originalPayment
     *
     * @return Payment
     */
    public function setOriginalPayment(\KGC\PaymentBundle\Entity\Payment $originalPayment = null)
    {
        $this->originalPayment = $originalPayment;

        return $this;
    }

    /**
     * Get originalPayment
     *
     * @return \KGC\PaymentBundle\Entity\Payment
     */
    public function getOriginalPayment()
    {
        return $this->originalPayment;
    }

    /**
     * Set lastPayment
     *
     * @param \KGC\PaymentBundle\Entity\Payment $lastPayment
     *
     * @return Payment
     */
    public function setLastPayment(\KGC\PaymentBundle\Entity\Payment $lastPayment = null)
    {
        $this->lastPayment = $lastPayment;

        return $this;
    }

    /**
     * Get lastPayment
     *
     * @return \KGC\PaymentBundle\Entity\Payment
     */
    public function getLastPayment()
    {
        return $this->lastPayment;
    }
}
