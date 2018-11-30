<?php

namespace KGC\RdvBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entité RdvState : rdv state.
 *
 * @category Entity
 *
 * @author Nicolas MENDEZ <nicolas.kgcom@gmail.com>
 *
 * @ORM\Table(name="consultation_state")
 * @ORM\Entity()
 */
class RdvState
{
    /**
     * @var int
     * @ORM\Column(name="state_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="state_name", type="string", length=255, nullable=false)
     */
    protected $name;

    /**
     * @var int
     * @ORM\Column(name="state_has_calendar_type", type="integer")
     */
    protected $hasCalendarType;

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
     * Set name.
     *
     * @param string $name
     *
     * @return \KGC\Bundle\SharedBundle\Entity\LandingState
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
     * @return int
     */
    public function getHasCalendarType()
    {
        return $this->hasCalendarType;
    }

    /**
     * @param int $hasCalendarType
     *
     * @return \KGC\Bundle\SharedBundle\Entity\LandingState
     */
    public function setHasCalendarType($hasCalendarType)
    {
        $this->hasCalendarType = $hasCalendarType;
        return $this;
    }

    public function __toString()
    {
        return $this->getName();
    }


}
