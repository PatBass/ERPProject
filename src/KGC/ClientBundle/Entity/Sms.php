<?php

namespace KGC\ClientBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;

/**
 * Class Mail.
 *
 * @ORM\Table(name="sms")
 * @ORM\Entity(repositoryClass="KGC\ClientBundle\Repository\SmsRepository")
 */
class Sms
{
    use ORMBehaviors\Timestampable\Timestampable;

    const M1 = 'M1';
    const M2 = 'M2';
    const M3 = 'M3';
    const M4 = 'M4';
    const MA = 'MA';
    const MCD = 'MCD';

    const BILL = 'FACTURE';

    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column( type="string", length=50, nullable=false)
     */
    protected $code;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    protected $text;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $type;
    
    /**
     * @var int
     * @ORM\Column( type="integer", options={"default":0})
     */
    protected $tchat;

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
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getTchat()
    {
        return $this->tchat;
    }

    /**
     * @param int $tchat
     */
    public function setTchat($tchat)
    {
        $this->tchat = $tchat;
    }

    public function formProperty()
    {
        return sprintf('%s - %s', $this->code, $this->text);
    }
}
