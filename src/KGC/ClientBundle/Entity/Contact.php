<?php

namespace KGC\ClientBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;

/**
 * Class Mail.
 *
 * @ORM\Table(name="contact")
 * @ORM\Entity(repositoryClass="KGC\ClientBundle\Repository\ContactRepository")
 */
class Contact
{
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
    protected $firstname;

    /**
     * @var string
     * @ORM\Column( type="string", length=50)
     */
    protected $lastname;

    /**
     * @var string
     * @ORM\Column( type="string", length=50, nullable=false)
     */
    protected $phone;

    /**
     * @var ListContact
     * @ORM\ManyToOne(targetEntity="ListContact", inversedBy="contacts")
     * @ORM\JoinColumn(nullable=false, name="list_id", referencedColumnName="id")
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
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * @param string $firstname
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
    }

    /**
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * @param string $lastname
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
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

    public function formProperty()
    {
        return sprintf('%s - %s', $this->firstname, $this->lastname);
    }

    /**
     * @param ListContact $list
     *
     * @return $this
     */
    public function setList(ListContact $list)
    {
        if (!isset($this->list)) {  // si c'est la première initialisation du client
            $list->addContact($this);
        }
        $this->list = $list;

        return $this;
    }

    /**
     * @return ListContact
     */
    public function getList()
    {
        return $this->list;
    }

    public function fullname() {
        return $this->getFirstname().' '.$this->getLastname();
    }
}
