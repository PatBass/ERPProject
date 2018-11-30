<?php

namespace KGC\ChatBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\Bundle\SharedBundle\Entity\Website;

/**
 * @category Entity
 *
 * @ORM\Table(name="chat_subscription")
 * @ORM\Entity(repositoryClass="KGC\ChatBundle\Repository\ChatSubscriptionRepository")
 */
class ChatSubscription implements \KGC\Bundle\SharedBundle\Entity\Interfaces\ChatSubscription
{
    const SOURCE_CRON = 1;
    const SOURCE_CLIENT = 2;
    const SOURCE_ADMIN = 3;

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
     * @var int
     * @ORM\Column(name="desactivation_source", type="smallint", nullable=true)
     */
    protected $desactivationSource;

    /**
     * @ORM\ManyToOne(targetEntity="\KGC\UserBundle\Entity\Utilisateur")
     * @ORM\JoinColumn(name="desactivated_by", referencedColumnName="uti_id", nullable=true)
     */
    protected $desactivatedBy;

    /**
     * @var \DateTime
     * @ORM\Column(name="disable_date", type="datetime", nullable=true)
     */
    protected $disableDate;

    /**
     * @var int
     * @ORM\Column(name="disable_source", type="smallint", nullable=true)
     */
    protected $disableSource;

    /**
     * @ORM\ManyToOne(targetEntity="\KGC\UserBundle\Entity\Utilisateur")
     * @ORM\JoinColumn(name="disabled_by", referencedColumnName="uti_id", nullable=true)
     */
    protected $disabledBy;

    /**
     * @var \DateTime
     * @ORM\Column(name="last_resubscription_date", type="datetime", nullable=true)
     */
    protected $lastResubscriptionDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="subscription_date", type="datetime", nullable=false)
     */
    protected $subscriptionDate;

    /**
     * @ORM\ManyToOne(targetEntity="\KGC\ChatBundle\Entity\ChatFormulaRate")
     * @ORM\JoinColumn(name="chat_formula_rate_id", referencedColumnName="id", nullable=false)
     */
    protected $chatFormulaRate;

    /**
     * @ORM\ManyToOne(targetEntity="\KGC\Bundle\SharedBundle\Entity\Client", inversedBy="chatSubscriptions")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id", nullable=false)
     */
    protected $client;

    /**
     * @ORM\ManyToOne(targetEntity="\KGC\Bundle\SharedBundle\Entity\Website", inversedBy="chatSubscriptions")
     * @ORM\JoinColumn(name="website_id", referencedColumnName="web_id", nullable=false)
     */
    protected $website;

    /**
     * @var \DateTime
     * @ORM\Column(name="next_payment_date", type="datetime", nullable=true)
     */
    protected $nextPaymentDate;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->subscriptionDate = new \DateTime();
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
     *
     * @return \DateTime
     */
    public function getDesactivationDate()
    {
        return $this->desactivationDate;
    }

    /**
     * Set lastResubscriptionDate.
     *
     * @param \DateTime $lastResubscriptionDate
     *
     * @return this
     */
    public function setLastResubscriptionDate(\DateTime $lastResubscriptionDate)
    {
        $this->lastResubscriptionDate = $lastResubscriptionDate;

        return $this;
    }

    /**
     * Get lastResubscriptionDate.
     *
     * @return \DateTime
     */
    public function getLastResubscriptionDate()
    {
        return $this->lastResubscriptionDate;
    }

    /**
     * Set subscriptionDate.
     *
     * @param \DateTime $subscriptionDate
     *
     * @return this
     */
    public function setSubscriptionDate(\DateTime $subscriptionDate)
    {
        $this->subscriptionDate = $subscriptionDate;

        return $this;
    }

    /**
     * Get subscriptionDate.
     *
     * @return \DateTime
     */
    public function getSubscriptionDate()
    {
        return $this->subscriptionDate;
    }

    public function getCommitmentEndDate()
    {
        if ($this->getNextPaymentDate() === null) {
            return null;
        }

        $date = clone $this->getNextPaymentDate();
        $currentDt = new \DateTime;
        if ($date < $currentDt) {
            $date->modify('this');
        }
        if ($date < $currentDt) {
            $date->modify('+1 month');
        }
        return $date;
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
     * Set website.
     *
     * @param \KGC\Bundle\SharedBundle\Entity\Website $website
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
     *
     * @return \KGC\Bundle\SharedBundle\Entity\Website
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * Set next payment date
     *
     * @param \DateTime $nextPaymentDate
     *
     * @return this
     */
    public function setNextPaymentDate(\DateTime $nextPaymentDate = null)
    {
        $this->nextPaymentDate = $nextPaymentDate;

        return $this;
    }

    /**
     * Get next payment date
     *
     * @return \DateTime
     */
    public function getNextPaymentDate()
    {
        return $this->nextPaymentDate;
    }

    /**
     * Convert to JSON like array.
     */
    public function toJsonArray()
    {
        $commitmentEndDate = $this->getCommitmentEndDate();

        return array(
            'id' => $this->getId(),
            'subscription_date' => $this->getSubscriptionDate()->getTimestamp(),
            'commitment_end_date' => $commitmentEndDate ? $commitmentEndDate->getTimestamp() : null,
            'last_resubscription_date' => ($this->getLastResubscriptionDate() ? $this->getLastResubscriptionDate()->getTimestamp() : null),
            'desactivation_date' => ($this->getDesactivationDate() ? $this->getDesactivationDate()->getTimestamp() : null),
        );
    }

    /**
     * Set desactivationSource
     *
     * @param integer $desactivationSource
     *
     * @return ChatSubscription
     */
    public function setDesactivationSource($desactivationSource = null)
    {
        $this->desactivationSource = $desactivationSource;

        return $this;
    }

    /**
     * Get desactivationSource
     *
     * @return integer
     */
    public function getDesactivationSource()
    {
        return $this->desactivationSource;
    }

    /**
     * Set disableDate
     *
     * @param \DateTime $disableDate
     *
     * @return ChatSubscription
     */
    public function setDisableDate($disableDate = null)
    {
        $this->disableDate = $disableDate;

        return $this;
    }

    /**
     * Get disableDate
     *
     * @return \DateTime
     */
    public function getDisableDate()
    {
        return $this->disableDate;
    }

    /**
     * Set disableSource
     *
     * @param integer $disableSource
     *
     * @return ChatSubscription
     */
    public function setDisableSource($disableSource = null)
    {
        $this->disableSource = $disableSource;

        return $this;
    }

    /**
     * Get disableSource
     *
     * @return integer
     */
    public function getDisableSource()
    {
        return $this->disableSource;
    }

    /**
     * Set desactivatedBy
     *
     * @param \KGC\UserBundle\Entity\Utilisateur $desactivatedBy
     *
     * @return ChatSubscription
     */
    public function setDesactivatedBy(\KGC\UserBundle\Entity\Utilisateur $desactivatedBy = null)
    {
        $this->desactivatedBy = $desactivatedBy;

        return $this;
    }

    /**
     * Get desactivatedBy
     *
     * @return \KGC\UserBundle\Entity\Utilisateur
     */
    public function getDesactivatedBy()
    {
        return $this->desactivatedBy;
    }

    /**
     * Set disabledBy
     *
     * @param \KGC\UserBundle\Entity\Utilisateur $disabledBy
     *
     * @return ChatSubscription
     */
    public function setDisabledBy(\KGC\UserBundle\Entity\Utilisateur $disabledBy = null)
    {
        $this->disabledBy = $disabledBy;

        return $this;
    }

    /**
     * Get disabledBy
     *
     * @return \KGC\UserBundle\Entity\Utilisateur
     */
    public function getDisabledBy()
    {
        return $this->disabledBy;
    }
}
