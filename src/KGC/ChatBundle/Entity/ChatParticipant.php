<?php

namespace KGC\ChatBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @category Entity
 *
 * @ORM\Table(name="chat_participant")
 * @ORM\Entity(repositoryClass="KGC\ChatBundle\Repository\ChatParticipantRepository")
 */
class ChatParticipant implements \KGC\Bundle\SharedBundle\Entity\Interfaces\ChatParticipant
{
    const LEAVE_REASON_HEARTBEAT = 'heartbeat';

    /**
     * @var int
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \DateTime
     * @ORM\Column(name="join_date", type="datetime")
     */
    protected $joinDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="leave_date", type="datetime", nullable=true)
     */
    protected $leaveDate;

    /**
     * @var string
     * @ORM\Column(name="leave_reason", type="string", length=25, nullable=true)
     */
    protected $leaveReason;

    /**
     * @ORM\ManyToOne(targetEntity="\KGC\UserBundle\Entity\Utilisateur", inversedBy="chatParticipants")
     * @ORM\JoinColumn(name="psychic_id", referencedColumnName="uti_id")
     */
    protected $psychic;

    /**
     * @ORM\ManyToOne(targetEntity="\KGC\UserBundle\Entity\Voyant")
     * @ORM\JoinColumn(name="virtual_psychic_id", referencedColumnName="voy_id", nullable=false)
     */
    protected $virtualPsychic;

    /**
     * @ORM\ManyToOne(targetEntity="\KGC\Bundle\SharedBundle\Entity\Client", inversedBy="chatParticipants")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     */
    protected $client;

    /**
     * @ORM\ManyToOne(targetEntity="\KGC\ChatBundle\Entity\ChatRoom", inversedBy="chatParticipants")
     * @ORM\JoinColumn(name="chat_room_id", referencedColumnName="id", nullable=false)
     */
    protected $chatRoom;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->joinDate = new \DateTime();
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
     * Set join date.
     *
     * @param \DateTime $joinDate
     *
     * @return \KGC\ChatBundle\Entity\ChatParticipant
     */
    public function setJoinDate(\DateTime $joinDate)
    {
        $this->joinDate = $joinDate;

        return $this;
    }

    /**
     * Get join date.
     *
     * @return \DateTime
     */
    public function getJoinDate()
    {
        return $this->joinDate;
    }

    /**
     * Set leave date.
     *
     * @param \DateTime $leaveDate
     *
     * @return \KGC\ChatBundle\Entity\ChatParticipant
     */
    public function setLeaveDate(\DateTime $leaveDate = null)
    {
        $this->leaveDate = $leaveDate;

        return $this;
    }

    /**
     * Get leave date.
     *
     * @return \DateTime
     */
    public function getLeaveDate()
    {
        return $this->leaveDate;
    }

    /**
     * Set leaveReason
     *
     * @param string $leaveReason
     *
     * @return ChatParticipant
     */
    public function setLeaveReason($leaveReason = null)
    {
        $this->leaveReason = $leaveReason;

        return $this;
    }

    /**
     * Get leaveReason
     *
     * @return string
     */
    public function getLeaveReason()
    {
        return $this->leaveReason;
    }

    /**
     * Get leave reasons list
     *
     * @return array
     */
    public static function getLeaveReasonsList()
    {
        return [
            self::LEAVE_REASON_HEARTBEAT
        ];
    }

    /**
     * Set client.
     *
     * @param \KGC\ClientBundle\Entity\Client $client
     *
     * @return \KGC\ChatBundle\Entity\ChatParticipant
     */
    public function setClient($client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get client.
     *
     * @return \KGC\ClientBundle\Entity\Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Set psychic.
     *
     * @param \KGC\UserBundle\Entity\Utilisateur $psychic
     *
     * @return \KGC\ChatBundle\Entity\ChatParticipant
     */
    public function setPsychic($psychic)
    {
        $this->psychic = $psychic;

        return $this;
    }

    /**
     * Get psychic.
     *
     * @return \KGC\UserBundle\Entity\Utilisateur
     */
    public function getPsychic()
    {
        return $this->psychic;
    }

    /**
     * Set virtualPsychic.
     *
     * @param \KGC\UserBundle\Entity\Voyant $virtualPsychic
     *
     * @return \KGC\ChatBundle\Entity\ChatParticipant
     */
    public function setVirtualPsychic($virtualPsychic)
    {
        $this->virtualPsychic = $virtualPsychic;

        return $this;
    }

    /**
     * Get virtualPsychic.
     *
     * @return \KGC\UserBundle\Entity\Voyant
     */
    public function getVirtualPsychic()
    {
        return $this->virtualPsychic;
    }

    /**
     * Set chat room.
     *
     * @param \KGC\ChatBundle\Entity\ChatRoom $chatRoom
     *
     * @return \KGC\ChatBundle\Entity\ChatParticipant
     */
    public function setChatRoom($chatRoom)
    {
        $this->chatRoom = $chatRoom;

        return $this;
    }

    /**
     * Get chat room.
     *
     * @return \KGC\ChatBundle\Entity\ChatRoom
     */
    public function getChatRoom()
    {
        return $this->chatRoom;
    }

    /**
     * Utility method to directly get the user related.
     *
     * @return Utilisateur, Client or null if no one is set
     */
    public function getUser()
    {
        return $this->psychic !== null ? $this->psychic : $this->client;
    }

    public function isPsychic()
    {
        return $this->psychic !== null;
    }

    public function getParticipantName()
    {
        return $this->psychic !== null ? $this->virtualPsychic->getNom() : $this->client->getPrenom();
    }

    public function getPhysicalPsychicName()
    {
        return $this->psychic !== null ? $this->psychic->getUsername() : null;
    }

    public function getVirtualPsychicName()
    {
        return $this->psychic !== null ? $this->virtualPsychic->getNom() : null;
    }
}
