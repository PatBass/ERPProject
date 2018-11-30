<?php

namespace KGC\StatBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use KGC\StatBundle\Form\StatisticColumnType;

/**
 * StatisticRenderingRule
 *
 * @ORM\Table(name="statistic_rendering_rule")
 * @ORM\Entity(repositoryClass="KGC\StatBundle\Repository\StatisticRenderingRuleRepository")
 */
class StatisticRenderingRule
{
    /**
     * @var integer
     *
     * @ORM\Column(name="srr_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="srr_column_code", type="string", length=50)
     */
    private $columnCode;

    /**
     * @var boolean
     *
     * @ORM\Column(name="srr_is_ratio", type="boolean")
     */
    private $isRatio;

    /**
     * @var float
     *
     * @ORM\Column(name="srr_value", type="float")
     */
    private $value;

    /**
     * @var string
     *
     * @ORM\Column(name="srr_operator", type="string", length=2)
     */
    private $operator;

    /**
     * @var string
     *
     * @ORM\Column(name="srr_color", type="string", length=7)
     */
    private $color;


    /**
     * @var boolean
     *
     * @ORM\Column(name="srr_active", type="boolean")
     */
    private $enabled;

    const OPERATOR_EQ = "=";
    const OPERATOR_GT = ">";
    const OPERATOR_GT_EQ = ">=";
    const OPERATOR_LS = "<";
    const OPERATOR_LS_EQ = "<=";

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set columnCode
     *
     * @param string $columnCode
     *
     * @return StatisticRenderingRule
     */
    public function setColumnCode($columnCode)
    {
        $this->columnCode = $columnCode;

        return $this;
    }

    /**
     * Get columnCode
     *
     * @return string
     */
    public function getColumnCode()
    {
        return $this->columnCode;
    }

    /**
     * Set isRatio
     *
     * @param boolean $isRatio
     *
     * @return StatisticRenderingRule
     */
    public function setIsRatio($isRatio)
    {
        $this->isRatio = $isRatio;

        return $this;
    }

    /**
     * Get isRatio
     *
     * @return boolean
     */
    public function getIsRatio()
    {
        return $this->isRatio;
    }

    /**
     * Set operator
     *
     * @param string $operator
     *
     * @return StatisticRenderingRule
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;

        return $this;
    }

    /**
     * Get operator
     *
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * Set value
     *
     * @param float $value
     *
     * @return StatisticRenderingRule
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set enabled
     *
     * @param boolean $enabled
     *
     * @return StatisticRenderingRule
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get enabled
     *
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Get color
     *
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Set color
     *
     * @param $color
     * @return StatisticRenderingRule
     */
    public function setColor($color)
    {
        $this->color = $color;

        return $this;
    }

    /**
     * @param $column
     * @param $value
     * @param $ratio
     * @return bool
     */
    public function isConcerned($column, $value, $ratio) {
        if($column == $this->columnCode) {
            $val = $this->isRatio ? floatval($ratio) : floatval($value);
            switch($this->getOperator()) {
                case self::OPERATOR_EQ:
                    return ($val == $this->getValue());
                    break;
                case self::OPERATOR_GT:
                    return ($val > $this->getValue());
                    break;
                case self::OPERATOR_GT_EQ:
                    return ($val >= $this->getValue());
                    break;
                case self::OPERATOR_LS:
                    return ($val < $this->getValue());
                    break;
                case self::OPERATOR_LS_EQ:
                    return ($val <= $this->getValue());
                    break;
                default:
                    return false;
            }
        }
        return false;
    }

    /**
     * @return string
     */
    public function getColumnLibelle() {
        return array_search($this->getColumnCode(), array_merge(StatisticColumnType::$RDV_CHOICES, StatisticColumnType::$CA_CHOICES), true);
    }
}

