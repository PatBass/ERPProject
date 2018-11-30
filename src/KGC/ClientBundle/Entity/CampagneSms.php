<?php

namespace KGC\ClientBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;
use mageekguy\atoum\asserters\boolean;

/**
 * Class Mail.
 *
 * @ORM\Table(name="campagne_sms")
 * @ORM\Entity(repositoryClass="KGC\ClientBundle\Repository\CampagneSmsRepository")
 */
class CampagneSms
{
    use ORMBehaviors\Timestampable\Timestampable;

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
    protected $name;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    protected $text;

    /**
     * @var string
     * @ORM\Column( type="string", length=50, nullable=false)
     */
    protected $sender;

    /**
     * @var int
     * @ORM\Column( type="integer", options={"default":0})
     */
    protected $tchat;

    /**
     * @var boolean
     * @ORM\Column( type="boolean", options={"default":0})
     */
    protected $archivage;

    /**
     * @var ListContact
     * @ORM\ManyToOne(targetEntity="ListContact", inversedBy="campagnes")
     * @ORM\JoinColumn(nullable=true, name="list_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $list;

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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
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
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * @param string $sender
     */
    public function setSender($sender)
    {
        $this->sender = $sender;
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
        return sprintf('%s - %s', $this->name);
    }

    /**
     * @param ListContact $list
     *
     * @return $this
     */
    public function setList(ListContact $list)
    {
        if (!isset($this->list)) {  // si c'est la première initialisation du client
            $list->addCampagne($this);
        }
        $this->list = $list;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getArchivage()
    {
        return $this->archivage;
    }

    /**
     * @param boolean $archivage
     */
    public function setArchivage($archivage)
    {
        $this->archivage = $archivage;
    }

    /**
     * @return ListContact
     */
    public function getList()
    {
        return $this->list;
    }
}
