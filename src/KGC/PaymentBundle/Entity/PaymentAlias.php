<?php

namespace KGC\PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

use KGC\RdvBundle\Entity\CarteBancaire;

/**
 * @ORM\Table(name="payment_alias")
 * @ORM\Entity(repositoryClass="KGC\PaymentBundle\Repository\PaymentAliasRepository")
 */
class PaymentAlias
{
    /**
     * @var int
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \KGC\Bundle\SharedBundle\Entity\Client
     * @ORM\ManyToOne(targetEntity="\KGC\Bundle\SharedBundle\Entity\Client", inversedBy="paymentAliases")
     * @ORM\JoinColumn(nullable=false, name="client_id", referencedColumnName="id")
     */
    protected $client;

    /**
     * @var string
     * @ORM\Column(name="gateway", type="string", length=50, nullable=false)
     */
    protected $gateway;

    /**
     * @var string
     * @ORM\Column(name="name", type="string", length=50, nullable=true)
     */
    protected $name;

    /**
     * @var array
     * @ORM\Column(name="details", type="json_array", nullable=true)
     */
    protected $details;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expired_at", type="date")
     */
    protected $expiredAt;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="\KGC\RdvBundle\Entity\CarteBancaire", mappedBy = "paymentAliases")
     */
    protected $cartebancaires;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->cartebancaires = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set gateway.
     *
     * @param string $gateway
     *
     * @return PaymentAlias
     */
    public function setGateway($gateway)
    {
        $this->gateway = $gateway;

        return $this;
    }

    /**
     * Get gateway.
     *
     * @return string
     */
    public function getGateway()
    {
        return $this->gateway;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return PaymentAlias
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set details.
     *
     * @param array $details
     *
     * @return PaymentAlias
     */
    public function setDetails($details)
    {
        $this->details = $details;

        return $this;
    }

    /**
     * Get details.
     *
     * @return array
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime $createdAt
     *
     * @return PaymentAlias
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set expiredAt.
     *
     * @param \DateTime $expiredAt
     *
     * @return PaymentAlias
     */
    public function setExpiredAt($expiredAt)
    {
        $this->expiredAt = $expiredAt;

        return $this;
    }

    /**
     * Get expiredAt.
     *
     * @return \DateTime
     */
    public function getExpiredAt()
    {
        return $this->expiredAt;
    }

    /**
     * Add cartebancaire
     *
     * @param CarteBancaire $cartebancaire
     *
     * @return PaymentAlias
     */
    public function addCartebancaire(CarteBancaire $cartebancaire)
    {
        $this->cartebancaires[] = $cartebancaire;

        return $this;
    }

    /**
     * Remove cartebancaire
     *
     * @param CarteBancaire $cartebancaire
     */
    public function removeCartebancaire(CarteBancaire $cartebancaire)
    {
        $this->cartebancaires->removeElement($cartebancaire);
    }

    /**
     * Get cartebancaires
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCartebancaires()
    {
        return $this->cartebancaires;
    }
}
