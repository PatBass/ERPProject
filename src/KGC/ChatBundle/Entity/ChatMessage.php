<?php

// src/KGC/ChatBundle/Entity/ChatMessage.php


namespace KGC\ChatBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @category Entity
 *
 * @ORM\Table(name="chat_message")
 * @ORM\Entity(repositoryClass="KGC\ChatBundle\Repository\ChatMessageRepository")
 */
class ChatMessage
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
     * @ORM\Column(name="date_created", type="datetime")
     */
    protected $dateCreated;

    /**
     * @var string
     * @ORM\Column(name="content", type="text")
     */
    protected $content;

    /**
     * @ORM\ManyToOne(targetEntity="\KGC\ChatBundle\Entity\ChatParticipant")
     * @ORM\JoinColumn(nullable=false, name="chat_participant_id", referencedColumnName="id")
     */
    protected $chatParticipant;

    /**
     * @ORM\ManyToOne(targetEntity="\KGC\ChatBundle\Entity\ChatRoom", inversedBy="chatMessages")
     * @ORM\JoinColumn(nullable=false, name="chat_room_id", referencedColumnName="id")
     */
    protected $chatRoom;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->dateCreated = new \DateTime();
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
     * Set date created.
     *
     * @param \DateTime $dateCreated
     *
     * @return \KGC\ChatBundle\Entity\ChatMessage
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
     * Set content.
     *
     * @param string $content
     *
     * @return \KGC\ChatBundle\Entity\ChatMessage
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set chat participant.
     *
     * @param \KGC\ChatBundle\Entity\ChatParticipant $chatParticipant
     *
     * @return \KGC\ChatBundle\Entity\ChatMessage
     */
    public function setChatParticipant($chatParticipant)
    {
        $this->chatParticipant = $chatParticipant;

        return $this;
    }

    /**
     * Get chat room.
     *
     * @return \KGC\ChatBundle\Entity\ChatParticipant
     */
    public function getChatParticipant()
    {
        return $this->chatParticipant;
    }

    /**
     * Set chat room.
     *
     * @param \KGC\ChatBundle\Entity\ChatRoom $chatRoom
     *
     * @return \KGC\ChatBundle\Entity\ChatMessage
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
     * Return this object convert to array, ready to be converted to json.
     *
     * @return array
     */
    public function toJsonArray()
    {
        return array(
            'id' => $this->getId(),
            'date_created' => $this->getDateCreated()->getTimestamp(),
            'content' => $this->getContent(),
        );
    }
}
