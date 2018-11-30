<?php

namespace KGC\StatBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;

/**
 * Class PhonisteParameter.
 *
 * @ORM\Table(name="phoniste_parameter")
 * @ORM\Entity(repositoryClass="KGC\StatBundle\Repository\PhonisteParameterRepository")
 */
class PhonisteParameter
{
    use ORMBehaviors\Timestampable\Timestampable;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    protected $maxObjectivePerDay;

    /**
     * @var float
     *
     * @ORM\Column(type="decimal", precision=5, scale=2)
     */
    protected $bonusSimple;

    /**
     * @var float
     *
     * @ORM\Column(type="decimal", precision=5, scale=2)
     */
    protected $bonusFirstThreshold;

    /**
     * @var float
     *
     * @ORM\Column(type="decimal", precision=5, scale=2)
     */
    protected $bonusSecondThreshold;

    /**
     * @var float
     *
     * @ORM\Column(type="decimal", precision=5, scale=2)
     */
    protected $bonusThirdThreshold;

    /**
     * @var float
     *
     * @ORM\Column(type="decimal", precision=5, scale=2)
     */
    protected $bonusFourthThreshold;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    protected $firstThreshold;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    protected $secondThreshold;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    protected $thirdThreshold;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    protected $fourthThreshold;

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
     * @return int
     */
    public function getThirdThreshold()
    {
        return $this->thirdThreshold;
    }

    /**
     * @param int $thirdThreshold
     */
    public function setThirdThreshold($thirdThreshold)
    {
        $this->thirdThreshold = $thirdThreshold;
    }

    /**
     * @return int
     */
    public function getMaxObjectivePerDay()
    {
        return $this->maxObjectivePerDay;
    }

    /**
     * @param int $maxObjectivePerDay
     */
    public function setMaxObjectivePerDay($maxObjectivePerDay)
    {
        $this->maxObjectivePerDay = $maxObjectivePerDay;
    }

    /**
     * @return float
     */
    public function getBonusSimple()
    {
        return $this->bonusSimple;
    }

    /**
     * @param float $bonusSimple
     */
    public function setBonusSimple($bonusSimple)
    {
        $this->bonusSimple = $bonusSimple;
    }

    /**
     * @return float
     */
    public function getBonusFirstThreshold()
    {
        return $this->bonusFirstThreshold;
    }

    /**
     * @param float $bonusFirstThreshold
     */
    public function setBonusFirstThreshold($bonusFirstThreshold)
    {
        $this->bonusFirstThreshold = $bonusFirstThreshold;
    }

    /**
     * @return float
     */
    public function getBonusSecondThreshold()
    {
        return $this->bonusSecondThreshold;
    }

    /**
     * @param float $bonusSecondThreshold
     */
    public function setBonusSecondThreshold($bonusSecondThreshold)
    {
        $this->bonusSecondThreshold = $bonusSecondThreshold;
    }

    /**
     * @return float
     */
    public function getBonusThirdThreshold()
    {
        return $this->bonusThirdThreshold;
    }

    /**
     * @param float $bonusThirdThreshold
     */
    public function setBonusThirdThreshold($bonusThirdThreshold)
    {
        $this->bonusThirdThreshold = $bonusThirdThreshold;
    }

    /**
     * @return int
     */
    public function getFirstThreshold()
    {
        return $this->firstThreshold;
    }

    /**
     * @param int $firstThreshold
     */
    public function setFirstThreshold($firstThreshold)
    {
        $this->firstThreshold = $firstThreshold;
    }

    /**
     * @return int
     */
    public function getSecondThreshold()
    {
        return $this->secondThreshold;
    }

    /**
     * @param int $secondThreshold
     */
    public function setSecondThreshold($secondThreshold)
    {
        $this->secondThreshold = $secondThreshold;
    }

    /**
     * @return float
     */
    public function getBonusFourthThreshold()
    {
        return $this->bonusFourthThreshold;
    }

    /**
     * @param float $bonusFourthThreshold
     */
    public function setBonusFourthThreshold($bonusFourthThreshold)
    {
        $this->bonusFourthThreshold = $bonusFourthThreshold;
    }

    /**
     * @return int
     */
    public function getFourthThreshold()
    {
        return $this->fourthThreshold;
    }

    /**
     * @param int $fourthThreshold
     */
    public function setFourthThreshold($fourthThreshold)
    {
        $this->fourthThreshold = $fourthThreshold;
    }
}
