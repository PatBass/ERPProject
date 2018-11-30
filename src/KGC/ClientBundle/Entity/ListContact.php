<?php

namespace KGC\ClientBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;

/**
 * Class ListContact.
 *
 * @ORM\Table(name="list_contact")
 * @ORM\Entity(repositoryClass="KGC\ClientBundle\Repository\ListContactRepository")
 */
class ListContact
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
     * @var int
     * @ORM\Column( type="integer", options={"default":0})
     */
    protected $tchat;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Contact", mappedBy="list", cascade={"remove"})
     * @ORM\OrderBy({"firstname" = "ASC"})
     */
    protected $contacts;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="CampagneSms", mappedBy="list")
     * @ORM\OrderBy({"name" = "ASC"})
     */
    protected $campagnes;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->contacts = new ArrayCollection();
        $this->campagnes = new ArrayCollection();
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

    /**
     * @param Contact $contact
     * @return $this
     */
    public function addContact(Contact $contact)
    {
        $this->contacts[] = $contact;

        return $this;
    }

    /**
     * @param Contact $contact
     */
    public function removeContact(Contact $contact)
    {
        $this->contacts->removeElement($contact);
    }

    /**
     * Get contacts.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getContacts()
    {
        return $this->contacts;
    }

    /**
     * @param CampagneSms $campagne
     * @return $this
     */
    public function addCampagne(CampagneSms $campagne)
    {
        $this->campagnes[] = $campagne;

        return $this;
    }

    /**
     * @param CampagneSms $campagne
     */
    public function removeCampagne(CampagneSms $campagne)
    {
        $this->campagnes->removeElement($campagne);
    }

    /**
     * Get campagnes.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCampagnes()
    {
        return $this->campagnes;
    }

    public function formProperty()
    {
        return sprintf('%s (%s contacts)', $this->name, $this->contacts->count());
    }

    /**
     * @return int
     */
    public function getSubmitFile()
    {
        return '';
    }

    /**
     */
    public function setSubmitFile($submitFile)
    {
    }
}
