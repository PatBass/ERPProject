<?php

// src/KGCRdvBundle/Entity/Cartebancaire.php


namespace KGC\RdvBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use KGC\Bundle\SharedBundle\Model\CreditCard;
use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\PaymentBundle\Exception\Payment\InvalidCardDataException;

/**
 * Entité CarteBancaire : coordonnées bancaires.
 *
 * @category Entity
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 *
 * @ORM\Table(name="cartebancaire")
 * @ORM\Entity(repositoryClass="KGC\RdvBundle\Repository\CarteBancaireRepository")
 */
class CarteBancaire implements \KGC\Bundle\SharedBundle\Entity\Interfaces\CarteBancaire
{
    /**
     * @var int
     * @ORM\Column(name="cb_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="cb_nom", type="string", nullable=false)
     */
    protected $nom = 'auto';

    /**
     * @var string
     * @ORM\Column(name="cb_firstName", type="string", length=255, nullable=true)
     */
    protected $firstName = null;

    /**
     * @var string
     * @ORM\Column(name="cb_lastName", type="string", length=255, nullable=true)
     */
    protected $lastName = null;

    /**
     * @var string
     * @ORM\Column(name="cb_numero", type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     */
    protected $numero;

    /**
     * @var string
     * @ORM\Column(name="cb_expiration", type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     */
    protected $expiration;

    /**
     * @var bool
     * @ORM\Column(name="cb_interdite", type="boolean", options={"default"=0})
     */
    protected $interdite = 0;

    /**
     * @var string
     * @ORM\Column(name="cb_cryptogramme", type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     */
    protected $cryptogramme;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="RDV", mappedBy = "cartebancaires")
     */
    protected $rdvs;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="KGC\Bundle\SharedBundle\Entity\Client", mappedBy = "cartebancaires")
     */
    protected $clients;

    /**
     * @ORM\ManyToMany(targetEntity="\KGC\PaymentBundle\Entity\PaymentAlias")
     * @ORM\JoinTable(name="cartebancaire_alias",
     *     joinColumns={@ORM\JoinColumn(name="cartebancaire_id", referencedColumnName="cb_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="alias_id", referencedColumnName="id", unique=true, onDelete="CASCADE")}
     * )
     * @Assert\Valid()
     */
    protected $paymentAliases;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->rdvs = new ArrayCollection();
        $this->clients = new ArrayCollection();
        $this->paymentAliases = new ArrayCollection();
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
     * Set nom.
     *
     * @param string $nom
     *
     * @return \KGC\RdvBundle\Entity\CarteBancaire
     */
    public function setNom($nom)
    {
        $this->nom = isset($nom) ? $nom : 'auto';

        return $this;
    }

    /**
     * Get nom.
     *
     * @return string
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * Set numero.
     *
     * @param string $numero
     *
     * @return \KGC\RdvBundle\Entity\CarteBancaire
     */
    public function setNumero($numero)
    {
        $this->numero = $numero;

        return $this;
    }

    /**
     * Get numero.
     *
     * @return string
     */
    public function getNumero()
    {
        return $this->numero;
    }

    /**
     * Set expiration.
     *
     * @param string $expiration
     *
     * @return \KGC\RdvBundle\Entity\CarteBancaire
     */
    public function setExpiration($expiration)
    {
        $this->expiration = $expiration;

        return $this;
    }

    /**
     * Get expiration.
     *
     * @return string
     */
    public function getExpiration()
    {
        return $this->expiration;
    }

    /**
     * Set cryptogramme.
     *
     * @param string $cryptogramme
     *
     * @return \KGC\RdvBundle\Entity\CarteBancaire
     */
    public function setCryptogramme($cryptogramme)
    {
        $this->cryptogramme = $cryptogramme;

        return $this;
    }

    /**
     * Get cryptogramme.
     *
     * @return string
     */
    public function getCryptogramme()
    {
        return $this->cryptogramme;
    }

    /**
     * Set rdvs.
     *
     * @param ArrayCollection $rdvs
     *
     * @return \KGC\RdvBundle\Entity\CarteBancaire
     */
    public function setRdvs($rdvs)
    {
        $this->rdvs = $rdvs;

        return $this;
    }

    /**
     * Add rdvs.
     *
     * @param \KGC\RdvBundle\Entity\RDV $rdv
     *
     * @return \KGC\RdvBundle\Entity\CarteBancaire
     */
    public function addRdvs(RDV $rdv)
    {
        if (!$this->rdvs->contains($rdv)) {
            $this->rdvs[] = $rdv;
        }

        return $this;
    }

    /**
     * Remove rdvs.
     *
     * @param \KGC\RdvBundle\Entity\RDV $rdv
     *
     * @return \KGC\RdvBundle\Entity\CarteBancaire
     */
    public function removeRdvs(RDV $rdv)
    {
        $this->rdvs->removeElement($rdv);

        return $this;
    }

    /**
     * Get rdvs.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRdvs()
    {
        return $this->rdvs;
    }

    /**
     * Set Client.
     *
     * @param ArrayCollection $client
     *
     * @return Client
     */
    public function setClients($client)
    {
        $this->clients = $client;

        return $this;
    }

    /**
     * Add Client.
     *
     * @param \KGC\Bundle\SharedBundle\Entity\Client $client
     *
     * @return \KGC\RdvBundle\Entity\CarteBancaire
     */
    public function addClients(\KGC\Bundle\SharedBundle\Entity\Client $client)
    {
        if (!$this->clients->contains($client)) {
            $this->clients[] = $client;
        }

        return $this;
    }

    /**
     * Remove Client.
     *
     * @param \KGC\Bundle\SharedBundle\Entity\Client $client
     *
     * @return \KGC\RdvBundle\Entity\CarteBancaire
     */
    public function removeClients(\KGC\Bundle\SharedBundle\Entity\Client $client)
    {
        $this->clients->removeElement($client);

        return $this;
    }

    /**
     * Get Client.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getClients()
    {
        return $this->clients;
    }

    public function __toString()
    {
        return $this->getNumero();
    }

    /**
     * Add rdv
     *
     * @param \KGC\RdvBundle\Entity\RDV $rdv
     *
     * @return CarteBancaire
     */
    public function addRdv(\KGC\RdvBundle\Entity\RDV $rdv)
    {
        $this->rdvs[] = $rdv;

        return $this;
    }

    /**
     * Remove rdv
     *
     * @param \KGC\RdvBundle\Entity\RDV $rdv
     */
    public function removeRdv(\KGC\RdvBundle\Entity\RDV $rdv)
    {
        $this->rdvs->removeElement($rdv);
    }

    /**
     * Add paymentAlias
     *
     * @param \KGC\PaymentBundle\Entity\PaymentAlias $paymentAlias
     *
     * @return CarteBancaire
     */
    public function addPaymentAlias(\KGC\PaymentBundle\Entity\PaymentAlias $paymentAlias)
    {
        $this->paymentAliases[] = $paymentAlias;

        return $this;
    }

    /**
     * Remove paymentAlias
     *
     * @param \KGC\PaymentBundle\Entity\PaymentAlias $paymentAlias
     */
    public function removePaymentAlias(\KGC\PaymentBundle\Entity\PaymentAlias $paymentAlias)
    {
        $this->paymentAliases->removeElement($paymentAlias);
    }

    /**
     * Get paymentAliases
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPaymentAliases()
    {
        return $this->paymentAliases;
    }

    /**
     * Set interdite
     *
     * @param boolean $interdite
     *
     * @return CarteBancaire
     */
    public function setInterdite($interdite)
    {
        $this->interdite = $interdite;

        return $this;
    }

    /**
     * Get interdite
     *
     * @return boolean
     */
    public function getInterdite()
    {
        return $this->interdite;
    }

    /**
     * Set firstName
     *
     * @param string $firstName
     *
     * @return CarteBancaire
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get firstName
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set lastName
     *
     * @param string $lastName
     *
     * @return CarteBancaire
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get lastName
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * return creditCard object equivalent to CarteBancaire
     *
     * @param Client $client
     *
     * @return CreditCard
     */
    public function toCreditCard(Client $client = null)
    {
        $firstName = $this->getFirstName();
        $lastName = $this->getLastName();
        if ($client) {
            $firstName = $firstName ?: $client->getPrenom();
            $lastName = $lastName ?: $client->getNom();
        }

        if ($firstName === null || $lastName === null) {
            throw new \Exception('Missing first and/or last name');
        }

        $creditCard = new CreditCard;
        if ($dt = \DateTime::createFromFormat('m/y', $this->getExpiration())) {
            $creditCard->setExpireAt($dt);
        } else {
            throw new InvalidCardDataException('Invalid expiration date : '.$this->getExpiration());
        }
        $creditCard->setFirstName($firstName);
        $creditCard->setLastName($lastName);
        $creditCard->setNumber($this->getNumero());
        $creditCard->setSecurityCode($this->getCryptogramme());

        return $creditCard;
    }

    /**
     * return CarteBancaire object equivalent to creditCard
     *
     * @param bool $withName
     *
     * @return CarteBancaire
     */
    public static function createFromCreditCard(CreditCard $card, $withName = true)
    {
        if (!($dt = $card->getExpireAt())) {
            throw new InvalidCardDataException('Invalid expiration date : '.$card->getExpireAt());
        }

        return (new CarteBancaire)
            ->setNom($card->getMaskedNumber())
            ->setFirstName($withName ? $card->getFirstName() : null)
            ->setLastName($withName ? $card->getLastName() : null)
            ->setNumero($card->getNumber())
            ->setExpiration($dt->format('m/y'))
            ->setCryptogramme($card->getSecurityCode());
    }

    /**
     * return last payment alias with the chosen gateway
     *
     * @param string $gateway
     *
     * @return Payment
     */
    public function getLastGatewayAlias($gateway)
    {
        $lastAlias = null;

        foreach ($this->getPaymentAliases() as $alias) {
            if ($alias->getGateway() == $gateway) {
                $lastAlias = $alias;
            }
        }

        return $lastAlias;
    }

    public function getMaskedNumber()
    {
        $first = substr($this->getNumero(), 0, 4);
        $end = substr($this->getNumero(), -4);
        return $first.'-****-****-'.$end;
    }

    /**
     * Set string.
     *
     * @param string $maskedNumber
     *
     * @return CarteBancaire
     */
    public function setMaskedNumber($maskedNumber)
    {
        return $this;
    }

    /**
     * @param Client
     *
     * @return bool
     */
    public function belongsTo(Client $client)
    {
        foreach ($this->getClients() as $cbClient) {
            if ($cbClient->getId() == $client->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        $dt = \DateTime::createFromFormat('m/y', $this->getExpiration());
        return $dt ? !$this->getInterdite() && ($dt->modify('last day of this month, 23:59:59') > new \DateTime) : false;
    }
}
