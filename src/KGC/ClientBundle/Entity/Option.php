<?php

namespace KGC\ClientBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;
use KGC\CommonBundle\Traits as CommonTraits;

/**
 * @ORM\Table(name="historique_option", uniqueConstraints={
 *      @ORM\UniqueConstraint(name="historique_option_idx", columns={"type", "code"})
 * })
 * @ORM\Entity(repositoryClass="KGC\ClientBundle\Repository\OptionRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Option
{
    use ORMBehaviors\Timestampable\Timestampable;
    use CommonTraits\Constantable;

    const TYPE_BEHAVIOR = 'behavior';
    const TYPE_PROFILE = 'profile';
    const TYPE_SITUATION = 'situation';
    const TYPE_PRO_SITUATION = 'pro_situation';
    const TYPE_SENDING = 'sending';
    const TYPE_PRODUCT = 'product';
    const TYPE_PLAN = 'plan';
    const TYPE_PENDULUM = 'pendulum';
    const TYPE_OPINION = 'opinion';
    const TYPE_REMINDER_STATE = 'reminder_state';
    const TYPE_DRAW_DECK = 'draw_deck';
    const TYPE_DRAW_CARD = 'draw_card';

    const BEHAVIOR_CHATTY = 'chatty';
    const BEHAVIOR_LESS_CHATTY = 'less_chatty';
    const BEHAVIOR_QUIET = 'quiet';

    const PROFILE_VERY_GOOD = 'very_good';
    const PROFILE_TO_FOLLOW = 'to_follow';
    const PROFILE_RECEPTIVE = 'receptive';
    const PROFILE_NASTY = 'nasty';

    const SITUATION_MARRIED = 'married';
    const SITUATION_DIVORCED = 'divorced';
    const SITUATION_COUPLE = 'couple';
    const SITUATION_LOVER = 'lover';
    const SITUATION_WIDOWED = 'widowed';
    const SITUATION_ALONE = 'alone';
    const SITUATION_IN_LOVE = 'in_love';
    const SITUATION_SEPARATED = 'separated';

    const PRO_SITUATION_HIRED = 'hired';
    const PRO_SITUATION_JOBLESSNESS = 'joblessness';
    const PRO_SITUATION_RSA = 'rsa';
    const PRO_SITUATION_STUDENT = 'student';
    const PRO_SITUATION_RETIRED = 'retired';

    const SENDING_MAIL = 'sending_mail';
    const SENDING_BILAN = 'sending_bilan';
    const SENDING_BILAN_AGREE = 'sending_bilan_agree';
    const SENDING_BILAN_SENT = 'sending_bilan_sent';
    const SENDING_BILAN_GET = 'sending_bilan_get';
    const SENDING_PICTURES = 'sending_pictures';
    const SENDING_PICTURES_GET = 'sending_pictures_get';

    const PRODUCT_CANDLE = 'candle';
    const PRODUCT_CANDLE_GREEN = 'candle_green';
    const PRODUCT_CANDLE_PINK = 'candle_pink';
    const PRODUCT_CANDLE_BLACK = 'candle_black';
    const PRODUCT_CANDLE_WHITE = 'candle_white';
    const PRODUCT_CANDLE_YELLOW = 'candle_yellow';
    const PRODUCT_CANDLE_RED = 'candle_red';
    const PRODUCT_NOVENA = 'novena';
    const PRODUCT_NOVENA_GREEN = 'novena_green';
    const PRODUCT_NOVENA_PINK = 'novena_pink';
    const PRODUCT_NOVENA_BLACK = 'novena_black';
    const PRODUCT_NOVENA_WHITE = 'novena_white';
    const PRODUCT_NOVENA_YELLOW = 'novena_yellow';
    const PRODUCT_NOVENA_RED = 'novena_red';
    const PRODUCT_STONES = 'stones';
    const PRODUCT_NECKLACE = 'necklace';
    const PRODUCT_INCENSE = 'incense';
    const PRODUCT_PENDULUM = 'pendulum';
    const PRODUCT_PENDULUM_FULL = 'pendulum_full';
    const PRODUCT_PENDENT = 'pendent';
    const PRODUCT_SM_CRYSTAL_BALL = 'sm_crystal_ball';
    const PRODUCT_LG_CRYSTAL_BALL = 'lg_crystal_ball';
    const PRODUCT_IGNITION = 'ignition';
    const PRODUCT_IGNITION_NEGATIVE = 'ignition_negative';
    const PRODUCT_IGNITION_TRUST = 'ignition_trust';
    const PRODUCT_PACK_LOVE = 'pack_love';
    const PRODUCT_PACK_TRUST = 'pack_trust';
    const PRODUCT_PACK_POSITIVE = 'pack_positive';
    const PRODUCT_PACK_FULL = 'pack_full';

    const PLAN_50 = 'plan_50';
    const PLAN_75 = 'plan_75';
    const PLAN_100 = 'plan_100';
    const PLAN_150 = 'plan_150';
    const PLAN_200 = 'plan_200';

    const PENDULUM_1 = 'question_1';
    const PENDULUM_2 = 'question_2';
    const PENDULUM_3 = 'question_3';
    const PENDULUM_4 = 'question_4';
    const PENDULUM_5 = 'question_5';
    const PENDULUM_6 = 'question_6';
    const PENDULUM_7 = 'question_7';
    const PENDULUM_8 = 'question_8';

    const OPINION_MISSCALL = 'miss_call';
    const OPINION_DISAPPOINTED_AMOUNT = 'ammount_disappointed';
    const OPINION_DISAPPOINTED_CONSULT = 'consult_disappointed';
    const OPINION_HAPPY = 'happy';

    const REMINDER_STATE_NOTCONFIRMED = 'reminder_state_notconfirmed';
    const REMINDER_STATE_CONFIRMED = 'reminder_state_confirmed';
    const REMINDER_STATE_CANCELLED = 'reminder_state_cancelled';

    const DECK_MARSEILLE_MAJ = 'deck_marseille_maj';
    const DECK_MARSEILLE_MIN = 'deck_marseille_min';

    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=100, nullable=false)
     */
    protected $type;

    /**
     * @var string
     * @ORM\Column(type="string", length=100, nullable=false)
     */
    protected $code;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=256, nullable=false)
     */
    protected $label;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description = null;

    /**
     * @var string
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    protected $color = null;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $enabled = true;

    /**
     * @var date
     * @ORM\Column(type="date", nullable=true)
     */
    protected $disabled_date = null;

    /**
     * @var string
     * @ORM\Column(type="string", length=256, nullable=true)
     */
    protected $data_attr = null;

    /**
     * @var Option
     * @ORM\ManyToOne(targetEntity="Option")
     * @ORM\JoinColumn(nullable = true)
     */
    protected $inheritance = null;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $automatic_sending = false;

    /**
     * @var array
     */
    protected static $types = [];

    /**
     * @var array
     */
    protected static $codes = [];

    /**
     * @param $type
     * @param $code
     */
    public function __construct($type, $code)
    {
        static::$types = $this->buildByPrefixes('TYPE');
        static::$codes = $this->buildByPrefixes('TYPE', true);

        if (!in_array($type, static::$types)) {
            throw new \InvalidArgumentException(
                sprintf('Option type unknown "%s"', $type)
            );
        }

        $this->type = $type;
        $this->code = $code;
    }

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
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
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

    /**
     * Get color.
     *
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param bool $active
     *
     * @return $this
     */
    public function setEnabled($active)
    {
        $this->enabled = $active;
        if (!$active) {
            $this->setDisabledDate(new \Datetime());
        } else {
            $this->setDisabledDate(null);
        }

        return $this;
    }

    /**
     * Get boolean active.
     *
     * @return string
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param \DateTime $date
     *
     * @return $this
     */
    public function setDisabledDate($date)
    {
        $this->disabled_date = $date;

        return $this;
    }

    /**
     * Get active.
     *
     * @return string
     */
    public function getDisabledDate()
    {
        return $this->disabled_date;
    }

    /**
     * @param $data
     *
     * @return $this
     */
    public function setDataAttr($data)
    {
        $this->data_attr = $data;

        return $this;
    }

    /**
     * Get active.
     *
     * @return string
     */
    public function getDataAttr()
    {
        return $this->data_attr;
    }

    /**
     * @return Option
     */
    public function getInheritance()
    {
        return $this->inheritance;
    }

    public function getInheritanceId()
    {
        if ($this->inheritance instanceof self) {
            return $this->inheritance->getId();
        }

        return;
    }

    /**
     * @param Option $inheritance
     *
     * @throws \Exception
     */
    public function setInheritance($inheritance)
    {
        if ($inheritance === null || $inheritance instanceof self) {
            $this->inheritance = $inheritance;
        }
    }

    public function __toString()
    {
        return $this->getLabel();
    }

    public function get($property)
    {
        $method = 'get'.ucfirst($property);

        if (method_exists($this, $method)) {
            return $this->$method();
        } else {
            return;
        }
    }

    /**
     * Set automaticSending
     *
     * @param boolean $automaticSending
     *
     * @return Option
     */
    public function setAutomaticSending($automaticSending)
    {
        $this->automatic_sending = $automaticSending;

        return $this;
    }

    /**
     * Get automaticSending
     *
     * @return boolean
     */
    public function getAutomaticSending()
    {
        return $this->automatic_sending;
    }
}
