<?php

namespace KGC\ClientBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use KGC\Bundle\SharedBundle\Entity\Interfaces\ClientSmsSent;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;

/**
 * Class MailSent.
 *
 * @ORM\Table(name="sms_sent")
 * @ORM\Entity(repositoryClass="KGC\ClientBundle\Repository\SmsRepository")
 */
class SmsSent implements \KGC\Bundle\SharedBundle\Entity\Interfaces\ClientSmsSent
{
    use ORMBehaviors\Timestampable\Timestampable;

    const STATUS_ERROR = 'error';
    const STATUS_SUCCESS = 'success';

    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Mail
     *
     * @ORM\ManyToOne(targetEntity="Sms", cascade={"persist"})
     */
    protected $sms;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    protected $text;

    /**
     * @var string
     * @ORM\Column( type="string", length=50, nullable=false)
     */
    protected $phone;

    /**
     * @var string
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    protected $status = self::STATUS_ERROR;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    protected $errorMsg;

    public function __construct()
    {
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
     * @return Sms
     */
    public function getSms()
    {
        return $this->sms;
    }

    /**
     * @param Sms $sms
     */
    public function setSms($sms)
    {
        $this->sms = $sms;
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
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getErrorMsg()
    {
        return $this->errorMsg;
    }

    /**
     * @param string $errorMsg
     */
    public function setErrorMsg($errorMsg)
    {
        $this->errorMsg = $errorMsg;
    }


    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }
}
