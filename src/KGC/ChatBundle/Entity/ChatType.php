<?php

// src/KGC/ChatBundle/Entity/ChatType.php


namespace KGC\ChatBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use KGC\UserBundle\Entity\Utilisateur;

/**
 * @category Entity
 *
 * @ORM\Table(name="chat_type")
 * @ORM\Entity(repositoryClass="KGC\ChatBundle\Repository\ChatTypeRepository")
 */
class ChatType
{
    /**
     * Represent the minute chat.
     */
    const TYPE_MINUTE = 0;

    /**
     * Represent the question chat.
     */
    const TYPE_QUESTION = 1;

    /**
     * @var int
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var int
     * @ORM\Column(name="max_client", type="integer")
     */
    protected $maxClient;

    /**
     * @var int
     * @ORM\Column(name="type", type="smallint")
     */
    protected $type;

    /**
     * @var string
     * @ORM\Column(name="entitled", type="string")
     */
    protected $entitled;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="KGC\UserBundle\Entity\Utilisateur", mappedBy="chatType")
     */
    protected $psychics;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="KGC\ChatBundle\Entity\ChatFormula", mappedBy="chatType")
     */
    protected $chatFormulas;

    /**
     * @var int
     * @ORM\Column(name="max_chars", type="integer")
     */
    protected $maxChars;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->psychics = new ArrayCollection();
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
     * Set maxClient.
     *
     * @param int $maxClient
     *
     * @return this
     */
    public function setMaxClient($maxClient)
    {
        $this->maxClient = $maxClient;

        return $this;
    }

    /**
     * Get maxClient.
     */
    public function getMaxClient()
    {
        return $this->maxClient;
    }

    /**
     * Set type.
     *
     * @param int $type
     *
     * @return this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Return true if this is minute's room.
     */
    public function isMinute()
    {
        return $this->getType() === self::TYPE_MINUTE;
    }

    /**
     * Return true if this is minute's room.
     */
    public function isQuestion()
    {
        return $this->getType() === self::TYPE_QUESTION;
    }

    /**
     * Set entitled.
     *
     * @param string $entitled
     *
     * @return this
     */
    public function setEntitled($entitled)
    {
        $this->entitled = $entitled;

        return $this;
    }

    /**
     * Get entitled.
     */
    public function getEntitled()
    {
        return $this->entitled;
    }

    /**
     * Add psychic.
     *
     * @param Utilisateur $psychic
     *
     * @return ChatType
     */
    public function addPsychic(Utilisateur $psychic)
    {
        $this->psychics[] = $psychic;

        return $this;
    }

    /**
     * Remove psychic.
     *
     * @param Utilisateur $psychic
     */
    public function removePsychic(Utilisateur $psychic)
    {
        $this->psychics->removeElement($psychic);
    }

    /**
     * Get psychics.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPsychics()
    {
        return $this->psychics;
    }

    /**
     * Add chatFormula.
     *
     * @param ChatFormula $chatFormula
     *
     * @return ChatType
     */
    public function addChatFormula(ChatFormula $chatFormula)
    {
        $this->chatFormulas[] = $chatFormula;

        return $this;
    }

    /**
     * Remove chatFormula.
     *
     * @param ChatFormula $chatFormula
     */
    public function removeChatFormula(ChatFormula $chatFormula)
    {
        $this->chatFormulas->removeElement($chatFormula);
    }

    /**
     * Get chatFormulas.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChatFormulas()
    {
        return $this->chatFormulas;
    }

    /**
     * Set maxChars.
     *
     * @param string $maxChars
     *
     * @return this
     */
    public function setMaxChars($maxChars)
    {
        $this->maxChars = $maxChars;

        return $this;
    }

    /**
     * Return the max chars for this chat type.
     *
     * @return int
     */
    public function getMaxChars()
    {
        return $this->maxChars;
    }

    /**
     * Convert this entity to JSON array.
     */
    public function toJsonArray()
    {
        return array(
            'type' => $this->getType(),
            'entitled' => $this->getEntitled(),
            'is_minute' => $this->isMinute(),
            'is_question' => $this->isQuestion(),
            'max_chars' => $this->getMaxChars(),
        );
    }
}
