<?php

namespace KGC\ChatBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use KGC\Bundle\SharedBundle\Entity\Website;

/**
 * @category Entity
 *
 * @ORM\Table(name="chat_room")
 * @ORM\Entity(repositoryClass="KGC\ChatBundle\Repository\ChatRoomRepository")
 */
class ChatRoom implements \KGC\Bundle\SharedBundle\Entity\Interfaces\ChatRoom
{
    /**
     * Constants on room's status.
     */
    const STATUS_NOT_STARTED = 0;
    const STATUS_ON_GOING = 1;
    const STATUS_CLOSED = 2;
    const STATUS_REFUSED = 3;

    /**
     * @var int
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="entitled", type="string")
     */
    protected $entitled;

    /**
     * @var int
     * @ORM\Column(name="status", type="smallint")
     */
    protected $status;

    /**
     * @var \DateTime
     * @ORM\Column(name="date_created", type="datetime")
     */
    protected $dateCreated;

    /**
     * @var \DateTime
     * @ORM\Column(name="start_date", type="datetime", nullable=true)
     */
    protected $startDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="end_date", type="datetime", nullable=true)
     */
    protected $endDate;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="\KGC\ChatBundle\Entity\ChatMessage", mappedBy="chatRoom")
     */
    protected $chatMessages;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="\KGC\ChatBundle\Entity\ChatParticipant", mappedBy="chatRoom")
     */
    protected $chatParticipants;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="\KGC\ChatBundle\Entity\ChatRoomFormulaRate", mappedBy="chatRoom")
     */
    protected $chatRoomFormulaRates;

    /**
     * @ORM\ManyToOne(targetEntity="\KGC\Bundle\SharedBundle\Entity\Website", inversedBy="chatRooms")
     * @ORM\JoinColumn(name="website_id", referencedColumnName="web_id", nullable=false)
     */
    protected $website;

    /**
     * @ORM\ManyToOne(targetEntity="\KGC\ChatBundle\Entity\ChatType")
     * @ORM\JoinColumn(name="chat_type_id", referencedColumnName="id", nullable=false)
     */
    protected $chatType;

    /**
     * @var Source
     *
     * @ORM\ManyToOne(targetEntity="\KGC\Bundle\SharedBundle\Entity\Source", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, name="source", referencedColumnName="id")
     */
    protected $source;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->dateCreated = new \DateTime();
        $this->chatMessages = new ArrayCollection();
        $this->chatParticipants = new ArrayCollection();
        $this->chatRoomFormulaRates = new ArrayCollection();

        $this->status = self::STATUS_NOT_STARTED;
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
     * Set entitled.
     *
     * @param string $entitled
     *
     * @return \KGC\ChatBundle\Entity\ChatRoom
     */
    public function setEntitled($entitled)
    {
        $this->entitled = $entitled;

        return $this;
    }

    /**
     * Get entitled.
     *
     * @return string
     */
    public function getEntitled()
    {
        return $this->entitled;
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return \KGC\ChatBundle\Entity\ChatRoom
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    public function isNotStarted()
    {
        return $this->status === self::STATUS_NOT_STARTED;
    }

    public function isOnGoing()
    {
        return $this->status === self::STATUS_ON_GOING;
    }

    public function isClosed()
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function isRefused()
    {
        return $this->status === self::STATUS_REFUSED;
    }

    /**
     * Set date created.
     *
     * @param \DateTime $dateCreated
     *
     * @return \KGC\ChatBundle\Entity\ChatRoom
     */
    public function setDateCreated(\DateTime $dateCreated)
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * Get date created.
     *
     * @return \DateTime
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * Set start date.
     *
     * @param \DateTime $startDate
     *
     * @return \KGC\ChatBundle\Entity\ChatRoom
     */
    public function setStartDate(\DateTime $startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get start date.
     *
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set end date.
     *
     * @param \DateTime $endDate
     *
     * @return \KGC\ChatBundle\Entity\ChatRoom
     */
    public function setEndDate(\DateTime $endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get end date.
     *
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Return this object convert to array, ready to be converted to json.
     *
     * @return array
     */
    public function toJsonArray()
    {
        return array(
            'id' => $this->getId(),
            'entitled' => $this->getEntitled(),
            'status' => $this->getStatus(),
            'date_created' => $this->getDateCreated()->getTimestamp(),
            'start_date' => ($this->getStartDate() ? $this->getStartDate()->getTimestamp() : null),
            'end_date' => ($this->getEndDate() ? $this->getEndDate()->getTimestamp() : null),
        );
    }

    /**
     * Set chatMessages.
     *
     * @param ArrayCollection
     *
     * @return \KGC\ChatBundle\Entity\ChatRoom
     */
    public function setChatMessages($chatMessages)
    {
        $this->chatMessages = $chatMessages;

        return $this;
    }

    /**
     * Get chatMessages.
     *
     * @return ArrayCollection
     */
    public function getChatMessages()
    {
        return $this->chatMessages;
    }

    /**
     * Add chatMessage.
     *
     * @return \KGC\ChatBundle\Entity\ChatRoom
     */
    public function addChatMessage(ChatMessage $chatMessage)
    {
        $this->chatMessages[] = $chatMessage;

        return $this;
    }

    /**
     * Remove chatMessage.
     *
     * @return \KGC\ChatBundle\Entity\ChatRoom
     */
    public function removeChatMessage(ChatMessage $chatMessage)
    {
        $this->chatMessages->removeElement($chatMessage);

        return $this;
    }

    /**
     * Set chatParticipants.
     *
     * @param ArrayCollection
     *
     * @return \KGC\ChatBundle\Entity\ChatRoom
     */
    public function setChatParticipants($chatParticipants)
    {
        $this->chatParticipants = $chatParticipants;

        return $this;
    }

    /**
     * Get chatParticipants.
     *
     * @return ArrayCollection
     */
    public function getChatParticipants()
    {
        return $this->chatParticipants;
    }

    /**
     * Add chatParticipant.
     *
     * @return \KGC\ChatBundle\Entity\ChatRoom
     */
    public function addChatParticipant(ChatParticipant $chatParticipant)
    {
        $this->chatParticipants[] = $chatParticipant;

        return $this;
    }

    /**
     * Remove chatParticipant.
     *
     * @return \KGC\ChatBundle\Entity\ChatRoom
     */
    public function removeChatParticipant(ChatParticipant $chatParticipant)
    {
        $this->chatParticipants->removeElement($chatParticipant);

        return $this;
    }

    /**
     * Set chatRoomFormulaRates.
     *
     * @param ArrayCollection
     *
     * @return \KGC\ChatBundle\Entity\ChatRoom
     */
    public function setChatRoomFormulaRates($chatRoomFormulaRates)
    {
        $this->chatRoomFormulaRates = $chatRoomFormulaRates;

        return $this;
    }

    /**
     * Get chatRoomFormulaRates.
     *
     * @return ArrayCollection
     */
    public function getChatRoomFormulaRates()
    {
        return $this->chatRoomFormulaRates;
    }

    /**
     * Add chatRoomFormulaRate.
     *
     * @return \KGC\ChatBundle\Entity\ChatRoom
     */
    public function addChatRoomFormulaRate(ChatRoomFormulaRate $chatRoomFormulaRate)
    {
        $this->chatRoomFormulaRates[] = $chatRoomFormulaRate;

        return $this;
    }

    /**
     * Remove chatRoomFormulaRate.
     *
     * @return \KGC\ChatBundle\Entity\ChatRoom
     */
    public function removeChatRoomFormulaRate(ChatRoomFormulaRate $chatRoomFormulaRate)
    {
        $this->chatRoomFormulaRates->removeElement($chatRoomFormulaRate);

        return $this;
    }

    /**
     * Get the client if there is one in this room.
     *
     * @return Client | null
     */
    public function getClient()
    {
        $client = null;
        foreach ($this->getChatParticipants() as $roomChatParticipant) {
            if (($clientChatParticipant = $roomChatParticipant->getClient()) !== null) {
                $client = $clientChatParticipant;
                break;
            }
        }

        return $client;
    }

    /**
     * Get participant related to main room psychic
     *
     * @return ChatParticipant
     */
    public function getPsychicParticipant()
    {
        $result = null;
        foreach ($this->getChatParticipants() as $roomChatParticipant) {
            if ($roomChatParticipant->getPsychic() !== null && ($result === null || $roomChatParticipant->getId() > $result->getId())) {
                $result = $roomChatParticipant;
            }
        }

        return $result;
    }

    /**
     * Get last psychic of the room
     *
     * @return Utilisateur | null
     */
    public function getPsychic()
    {
        $participant = $this->getPsychicParticipant();
        return $participant ? $participant->getPsychic() : null;
    }

    /**
     * Get virtual psychic of the room
     *
     * @return Voyant | null
     */
    public function getVirtualPsychic()
    {
        $participant = $this->getPsychicParticipant();
        return $participant ? $participant->getVirtualPsychic() : null;
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
     * Set source
     *
     * @param string $source
     *
     * @return ChatRoom
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    public function canBeReopened()
    {
        if ($this->getStatus() !== self::STATUS_ON_GOING) {
            return false;
        }

        $clientParticipant = $psychicParticipant = null;
        foreach ($this->getChatParticipants() as $participant) {
            if ($participant->getClient() !== null) {
                $clientParticipant = $participant;
            } else if ($psychicParticipant === null || $participant->getId() > $psychicParticipant->getId()) {
                $psychicParticipant = $participant;
            }
        }

        return $psychicParticipant !== null && $psychicParticipant->getLeaveDate() === null && $clientParticipant !== null && $clientParticipant->getLeaveDate() !== null;
    }

    public function getPsychicFilter(){
        return $this->chatParticipants->filter(function($element) {
            return !is_null($element->getPsychic()); // filter based on status.
        });
    }
}
