<?php

// src/KGC/UserBundle/Repository/SalaryParameter.php


namespace KGC\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;

/**
 * Class SalaryParameter.
 * 
 * @category Entity
 *
 * @ORM\Table(name="salary_parameter")
 * @ORM\Entity()
 */
class SalaryParameter
{
    use ORMBehaviors\Timestampable\Timestampable;

    const NATURE_EMPLOYEE = 'employee';
    const NATURE_AE = 'ae';

    const TYPE_CONSULTATION = 'consultation';
    const TYPE_FOLLOW = 'follow';
    const TYPE_BONUS = 'bonus';

    public static function getTypes()
    {
        return [
            self::TYPE_CONSULTATION,
            self::TYPE_FOLLOW,
            self::TYPE_BONUS,
        ];
    }

    public static function getNatures()
    {
        return [
            self::NATURE_EMPLOYEE,
            self::NATURE_AE,
        ];
    }

    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false, length=50)
     */
    protected $type;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false, length=50)
     */
    protected $nature;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $caMin;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $caMax;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $valueMin;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $valueMax;

    /**
     * @var float
     *
     * @ORM\Column(type="decimal", precision=5, scale=2, nullable=false)
     */
    protected $percentage;



    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return float
     */
    public function getPercentage()
    {
        return $this->percentage;
    }

    /**
     * @param float $percentage
     */
    public function setPercentage($percentage)
    {
        $this->percentage = $percentage;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getNature()
    {
        return $this->nature;
    }

    /**
     * @param string $nature
     */
    public function setNature($nature)
    {
        $this->nature = $nature;
    }

    /**
     * @return int
     */
    public function getCaMin()
    {
        return $this->caMin;
    }

    /**
     * @param int $caMin
     */
    public function setCaMin($caMin)
    {
        $this->caMin = $caMin;
    }

    /**
     * @return int
     */
    public function getCaMax()
    {
        return $this->caMax;
    }

    /**
     * @param int $caMax
     */
    public function setCaMax($caMax)
    {
        $this->caMax = $caMax;
    }

    /**
     * @return int
     */
    public function getValueMin()
    {
        return $this->valueMin;
    }

    /**
     * @param int $valueMin
     */
    public function setValueMin($valueMin)
    {
        $this->valueMin = $valueMin;
    }

    /**
     * @return int
     */
    public function getValueMax()
    {
        return $this->valueMax;
    }

    /**
     * @param int $valueMax
     */
    public function setValueMax($valueMax)
    {
        $this->valueMax = $valueMax;
    }

    public static function checkType($type)
    {
        if (!in_array($type, static::getTypes())) {
            throw new \Exception(sprintf('Type "%s" does not exist !', $type));
        }
    }

    public static function checkNature($nature)
    {
        if (!in_array($nature, static::getNatures())) {
            throw new \Exception(sprintf('Nature "%s" does not exist !', $nature));
        }
    }
}
