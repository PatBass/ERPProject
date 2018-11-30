<?php

namespace KGC\ClientBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use KGC\Bundle\SharedBundle\Entity\Interfaces\ClientMailSent;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;

/**
 * Class MailSent.
 *
 * @ORM\Table(name="mail_sent")
 * @ORM\Entity(repositoryClass="KGC\ClientBundle\Repository\MailRepository")
 */
class MailSent implements \KGC\Bundle\SharedBundle\Entity\Interfaces\ClientMailSent
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
     * @ORM\ManyToOne(targetEntity="Mail", cascade={"persist"})
     */
    protected $mail;

    /**
     * @var string
     * @ORM\Column(type="string", length=256, nullable=false)
     */
    protected $subject;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    protected $text;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    protected $html;

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

    protected $attachments;

    protected $file;

    public function __construct()
    {
        $this->attachments = [];
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
     * @return Mail
     */
    public function getMail()
    {
        return $this->mail;
    }

    /**
     * @param Mail $mail
     */
    public function setMail($mail)
    {
        $this->mail = $mail;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
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
    public function getHtml()
    {
        return $this->html;
    }

    /**
     * @param string $html
     */
    public function setHtml($html)
    {
        $this->html = $html;
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
     * @return mixed
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * @param array $attachment
     */
    public function setAttachments(array $attachment)
    {
        $this->attachments = $attachment;
    }

    public function addAttachment($attachment)
    {
        $this->attachments[] = $attachment;
    }

    /**
     * @return mixed
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param mixed $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }
}
