<?php
// src/KGC/ClientBundle/Entity/Mail.php

namespace KGC\ClientBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;

/**
 * Class Mail.
 *
 * @ORM\Table(name="mail")
 * @ORM\Entity(repositoryClass="KGC\ClientBundle\Repository\MailRepository")
 */
class Mail
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
     * @var bool
     * @ORM\Column( type="boolean" )
     */
    protected $enabled = true;
    
    /**
     * @var date
     * @ORM\Column( type="date", nullable=true)
     */
    protected $disabled_date = null;

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
     * @return bool
     */
    public function getEnabled()
    {
        return $this->enabled;
    }
    
    /**
     * @param bool $actif
     *
     * @return $this
     */
    public function setEnabled($actif)
    {
        $this->enabled = $actif;
        if (!$actif) {
            $this->setDisabledDate(new \Datetime());
        } else {
            $this->setDisabledDate(null);
        }

        return $this;
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
     * @return \Datetime
     */
    public function getDisabledDate()
    {
        return $this->disabled_date;
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
        return sprintf('%s - %s', $this->code, $this->subject);
    }
}
