<?php
// src/KGC/StatBundle/Entity/BonusParameter.php

namespace KGC\StatBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use KGC\UserBundle\Entity\Utilisateur;

/**
 * Class BonusParameter.
 *
 * @ORM\Table(name="bonus_parameter")
 * @ORM\Entity(repositoryClass="KGC\StatBundle\Repository\BonusParameterRepository")
 */
class BonusParameter
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=20)
     */
    protected $code;

    /**
     * @var float
     *
     * @ORM\Column(type="decimal", precision=7, scale=2)
     */
    protected $amount;

    /**
     * @var \DateTime
     * 
     * @ORM\Column(type="date", nullable=true)
     */
    protected $date = null;

    /**
     * @var \KGC\UserBundle\Entity\Utilisateur
     * 
     * @ORM\ManyToOne(targetEntity="\KGC\UserBundle\Entity\Utilisateur")
     * @ORM\JoinColumn(nullable=true, referencedColumnName="uti_id")
     */
    protected $user = null;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $objective = null;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $sec_objective = null;

    /**
     * @var string
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    protected $color = null;


    const PHONISTE_QUANTITY = 'PHONISTE_QUANTITE';
    const PHONISTE_QUALITY = 'PHONISTE_QUALITE';
    const PHONISTE_HEBDO = 'PHONISTE_HEBDO';
    const PHONISTE_CHALLENGE = 'PHONISTE_CHALLENGE';
    const PHONISTE_PENALTY = 'PHONISTE_PENALTY';
    
    const PSYCHIC_MOYENNEPAIE = 'PSYCHIC_MOYENNEPAIE';
    const PSYCHIC_HEBDO = 'PSYCHIC_HEBDO';
    const PSYCHIC_SUIVI = 'PSYCHIC_SUIVI';
    const PSYCHIC_10MIN = 'PSYCHIC_10MIN';
    const PSYCHIC_PENALTY = 'PSYCHIC_PENALTY';
    
    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * 
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        
        return $this;
    }
    
    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }
    
    /**
     * @param $code
     *
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     * 
     * @return $this
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        
        return $this;
    }
    
    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param $date
     *
     * @return $this
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }
    
    /**
     * @return \KGC\UserBundle\Entity\Utilisateur
     */
    public function getUser()
    {
        return $this->user;
    }
    
    /**
     * @param \KGC\UserBundle\Entity\Utilisateur $user
     *
     * @return $this
     */
    public function setUser(Utilisateur $user)
    {
        $this->user = $user;

        return $this;
    }
    
    /**
     * @return int
     */
    public function getObjective()
    {
        return $this->objective;
    }

    /**
     * @param int $objective
     * 
     * @return $this
     */
    public function setObjective($objective)
    {
        $this->objective = $objective;
        
        return $this;
    }
    
    /**
     * @return int
     */
    public function getSecObjective()
    {
        return $this->sec_objective;
    }

    /**
     * @param int $objective
     * 
     * @return $this
     */
    public function setSecObjective($objective)
    {
        $this->sec_objective = $objective;
        
        return $this;
    }
        
    /**
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }
    
    /**
     * @param $color
     *
     * @return $this
     */
    public function setColor($color)
    {
        $this->color = $color;

        return $this;
    }
}