<?php

namespace KGC\ClientBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use KGC\CommonBundle\Traits as CommonTraits;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;

/**
 * @ORM\Table(name="pendulum_historique")
 * @ORM\Entity()
 */
class Pendulum
{
    use ORMBehaviors\Timestampable\Timestampable;
    use CommonTraits\Constantable;

    const TYPE_DEFINED = 'defined';
    const TYPE_CUSTOM = 'custom';

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
    protected $type = self::TYPE_DEFINED;

    /**
     * @var Historique
     *
     * @ORM\ManyToOne(targetEntity="Historique", inversedBy="pendulum")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $history;

    /**
     * @var Option
     *
     * @ORM\ManyToOne(targetEntity="Option")
     */
    protected $question;

    /**
     * @var @ORM\Column(type="string", length=512, nullable=true)
     */
    protected $target;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=512, nullable=true)
     */
    protected $customQuestion;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $answer;

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
        if (strlen($type) && !in_array($type, $this->buildByPrefixes('TYPE'))) {
            throw new \InvalidArgumentException(
                sprintf('Pendulum type unknown "%s"', $type)
            );
        }

        $this->type = $type;
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
    public function getQuestion()
    {
        return $this->question;
    }

    /**
     * @param Option $question
     *
     * @throws \Exception
     */
    public function setQuestion($question)
    {
        if ($question && Option::TYPE_PENDULUM !== $question->getType()) {
            throw new \Exception(
                sprintf('Option type "%s" not allowed for question attribute', $question->getType())
            );
        }

        $this->question = $question;
        $this->setType(self::TYPE_DEFINED);
    }

    /**
     * @return mixed
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @param mixed $target
     */
    public function setTarget($target)
    {
        $this->target = $target;
    }

    /**
     * @return string
     */
    public function getCustomQuestion()
    {
        return $this->customQuestion;
    }

    /**
     * @param string $customQuestion
     */
    public function setCustomQuestion($customQuestion)
    {
        $this->customQuestion = $customQuestion;
        if (!empty($customQuestion)) {
            $this->setType(self::TYPE_DEFINED);
        }
    }

    /**
     * @return bool
     */
    public function getAnswer()
    {
        return $this->answer;
    }

    /**
     * @param bool $answer
     */
    public function setAnswer($answer)
    {
        $this->answer = $answer;
    }
}
