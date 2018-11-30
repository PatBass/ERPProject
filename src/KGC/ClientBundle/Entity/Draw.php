<?php

// src/KGC/ClientBundle/Entity/Draw.php


namespace KGC\ClientBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use KGC\CommonBundle\Traits as CommonTraits;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;

/**
 * Draw Entity : Tirage de carte.
 *
 * @ORM\Table(name="draw")
 * @ORM\Entity()
 */
class Draw
{
    use ORMBehaviors\Timestampable\Timestampable;
    use CommonTraits\Constantable;

    /**
     * @var int
     * @ORM\Column(type="integer", name="dra_id")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Historique
     *
     * @ORM\ManyToOne(targetEntity="Historique", inversedBy="draw")
     * @ORM\JoinColumn(name="dra_history", nullable = false, onDelete="CASCADE")
     */
    protected $history;

    /**
     * @var Option
     * @ORM\ManyToOne(targetEntity="Option")
     * @ORM\JoinColumn(name="dra_deck", nullable = false)
     */
    protected $deck;

    /**
     * @var Option
     * @ORM\ManyToOne(targetEntity="Option")
     * @ORM\JoinColumn(name="dra_card", nullable = false)
     */
    protected $card;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Historique
     */
    public function getHistory()
    {
        return $this->history;
    }

    /**
     * @param Historique $history
     */
    public function setHistory(Historique $history)
    {
        $this->history = $history;
    }

    /**
     * @return Option
     */
    public function getDeck()
    {
        return $this->deck;
    }

    /**
     * @param Option $deck
     *
     * @throws \Exception
     */
    public function setDeck($deck)
    {
        if ($deck && Option::TYPE_DRAW_DECK !== $deck->getType()) {
            throw new \Exception(
                sprintf('Option type "%s" not allowed for deck attribute', $deck->getType())
            );
        }

        $this->deck = $deck;
    }

    /**
     * @return Option
     */
    public function getCard()
    {
        return $this->card;
    }

    /**
     * @param Option $card
     *
     * @throws \Exception
     */
    public function setCard($card)
    {
        if ($card && Option::TYPE_DRAW_CARD !== $card->getType()) {
            throw new \Exception(
                sprintf('Option type "%s" not allowed for card attribute', $card->getType())
            );
        }

        $this->card = $card;
    }
}
