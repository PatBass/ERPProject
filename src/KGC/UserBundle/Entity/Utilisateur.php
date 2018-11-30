<?php
// src/KGC/UserBundle/Entity/Utilisateur.php

namespace KGC\UserBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use KGC\ChatBundle\Entity\ChatType;
use KGC\ChatBundle\Entity\ChatParticipant;

/**
 * Entité Utilisateur : Utilisateurs de l'application.
 *
 * @category Entity
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 *
 * @ORM\Table(name="utilisateur")
 * @ORM\Entity(repositoryClass="KGC\UserBundle\Repository\UtilisateurRepository")
 *
 * @UniqueEntity(fields="username", message="Un utilisateur ayant cet identifiant de connexion existe déjà.")
 */
class Utilisateur implements AdvancedUserInterface, \Serializable
{
    /**
     * Constants to define user's sexe.
     */
    const SEXE_MAN = 0;
    const SEXE_WOMAN = 1;

    /**
     * @var int
     * @ORM\Column(name="uti_id", type="integer", columnDefinition="SMALLINT(5) UNSIGNED NOT NULL")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="uti_username", type="string", length=255, unique=true)
     * @Assert\NotBlank()
     */
    protected $username;

    /**
     * @var string
     * @ORM\Column(name="uti_password", type="string", length=255)
     * @Assert\NotBlank()
     */
    protected $password;

    /**
     * @var string
     */
    protected $plainText;

    /**
     * @var string
     * @ORM\Column(name="uti_salt", type="string", length=255)
     */
    protected $salt = '';

    /**
     * @var \DateTime
     * @ORM\Column(name="uti_datecrea", type="datetime", nullable=false)
     * @Assert\DateTime()
     */
    protected $dateCrea;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection(Profil)
     * @ORM\ManyToMany(targetEntity="Profil", inversedBy="users", cascade={"persist"})
     * @ORM\JoinTable(name="utilisateur_profil",
     *     joinColumns={@ORM\JoinColumn(name="utilisateur_id", referencedColumnName="uti_id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="profil_id", referencedColumnName="pro_id")}
     * )
     * @Assert\NotBlank()
     */
    protected $profils;

    /**
     * @var Profil
     * @ORM\ManyToOne(targetEntity="Profil", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false, name="uti_mainprofil", referencedColumnName="pro_id")
     * @Assert\NotBlank()
     */
    protected $mainProfil;

    /**
     * @var Poste
     * @ORM\ManyToOne(targetEntity="Poste", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, name="uti_poste", referencedColumnName="poste_id")
     */
    protected $poste;

    /**
     * @var bool
     * @ORM\Column(name="uti_actif", type="boolean" )
     */
    protected $actif = true;

    /**
     * @var date
     * @ORM\Column(name="uti_disabled_date", type="date", nullable=true)
     */
    protected $disabled_date = null;

    /**
     * @var \Datetime
     * @ORM\Column(name="uti_last_active_time", type="datetime", nullable=true)
     */
    protected $lastActiveTime;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Journal", mappedBy="utilisateur", cascade={"persist"})
     */
    protected $journal;

    /**
     * @var string
     * @ORM\Column(name="uti_salary_status", type="string", nullable=true, length=50)
     */
    protected $salary_status = null;

    /**
     * @var int
     * @ORM\Column(name="uti_sexe", type="smallint", nullable=true)
     */
    protected $sexe;

    /**
     * @ORM\ManyToOne(targetEntity="\KGC\ChatBundle\Entity\ChatType", inversedBy="psychics")
     * @ORM\JoinColumn(name="chat_type_id", referencedColumnName="id", nullable=true)
     */
    protected $chatType;

    /**
     * @var bool
     * @ORM\Column(name="uti_is_chat_available", type="boolean" )
     */
    protected $isChatAvailable = false;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="KGC\ChatBundle\Entity\ChatParticipant", mappedBy="psychic")
     */
    protected $chatParticipants;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->dateCrea = new \DateTime();
        $this->journal = new ArrayCollection();
        $this->chatParticipants = new ArrayCollection();
    }

    /**
     * Get id.
     *
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
     * @param $username
     *
     * @return $this
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username.
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param $password
     *
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password.
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param $encoder
     *
     * @return $this
     */
    public function encodePassword($encoder)
    {
        $plainPassword = $this->getPlainText();
        $password = !empty($plainPassword) ? $plainPassword : $this->getPassword();
        $this->password = $encoder->encodePassword($password, $this->salt);

        return $this;
    }

    /**
     * @param $salt
     *
     * @return $this
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * Get salt.
     *
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @param $profils
     *
     * @return $this
     */
    public function setProfils($profils)
    {
        $this->profils = $profils;

        return $this;
    }

    /**
     * @param Profil $profil
     *
     * @return $this
     */
    public function addProfils(Profil $profil)
    {
        $this->profils[] = $profil;

        return $this;
    }

    /**
     * @param Profil $profil
     *
     * @return $this
     */
    public function removeProfils(Profil $profil)
    {
        $this->profils->removeElement($profil);

        return $this;
    }

    /**
     * Get profils.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProfils()
    {
        return $this->profils;
    }

    /**
     * Get roles.
     *
     * @return array
     */
    public function getRoles()
    {
        $roles = array();
        foreach ($this->profils as $profil) {
            $roles[] = $profil->getRole();
        }

        return $roles;
    }

    /**
     * @param Profil $mainprofil
     *
     * @return $this
     */
    public function setMainProfil(Profil $mainprofil)
    {
        $this->mainProfil = $mainprofil;

        return $this;
    }

    /**
     * @return \KGC\UserBundle\Entity\Profil
     */
    public function getMainProfil()
    {
        return $this->mainProfil;
    }

    /**
     * Erase crendetials.
     */
    public function eraseCredentials()
    {
    }

    /**
     * @return \DateTime
     */
    public function getDateCrea()
    {
        return $this->dateCrea;
    }

    /**
     * @return mixed
     */
    public function getPlainText()
    {
        return $this->plainText;
    }

    /**
     * @param mixed $plainText
     */
    public function setPlainText($plainText)
    {
        $this->plainText = $plainText;
    }

    /**
     * Set actif.
     *
     * @param int $actif
     *
     * @return \KGC\RdvBundle\Entity\Support
     */
    public function setActif($actif)
    {
        $this->actif = $actif;
        if (!$actif) {
            $this->setDisabledDate(new \Datetime());
        } else {
            $this->setDisabledDate(null);
        }

        return $this;
    }

    /**
     * Get actif.
     *
     * @return int
     */
    public function getActif()
    {
        return $this->actif;
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
     * @return \Datetime
     */
    public function getLastActiveTime()
    {
        return $this->lastActiveTime;
    }

    /**
     * @param \Datetime $lastActiveTime
     */
    public function setLastActiveTime($lastActiveTime)
    {
        $this->lastActiveTime = $lastActiveTime;
    }

    /**
     * @param Journal $journal
     *
     * @return $this
     */
    public function addJournal(Journal $journal)
    {
        $this->journal[] = $journal;

        return $this;
    }

    /**
     * @param Journal $journal
     *
     * @return $this
     */
    public function removeJournal(Journal $journal)
    {
        $this->journal->removeElement($journal);

        return $this;
    }

    /**
     * Get journal.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getJournal()
    {
        return $this->journal;
    }

    /**
     * @param $salary_status
     *
     * @return $this
     */
    public function setSalaryStatus($salary_status)
    {
        if ($salary_status !== null) {
            SalaryParameter::checkNature($salary_status);
        }

        $this->salary_status = $salary_status;

        return $this;
    }

    /**
     * Get salary_status.
     *
     * @return string
     */
    public function getSalaryStatus()
    {
        return $this->salary_status;
    }

    /**
     * Get salary_status.
     *
     * @return string
     */
    public function getStatut()
    {
        switch ($this->salary_status) {
            case SalaryParameter::NATURE_AE : return 'Auto-entrepreneur';
            case SalaryParameter::NATURE_EMPLOYEE : return 'Employé';
        }

        return;
    }

    /**
     * @return bool
     */
    public function isVoyant()
    {
        return !empty($this->mainProfil) && Profil::VOYANT === $this->getMainProfil()->getRole();
    }

    /**
     * @return bool
     */
    public function isManagerVoyant()
    {
        return !empty($this->getRoles()) && in_array( Profil::MANAGER_VOYANT, $this->getRoles());
    }

    /**
     * @return bool
     */
    public function isStandard()
    {
        return !empty($this->mainProfil) && in_array($this->getMainProfil()->getRole(), [Profil::STANDARD, Profil::STANDARD_DRI_J1]);
    }

    /**
     * @return bool
     */
    public function isPhoniste()
    {
        return !empty($this->mainProfil) && Profil::PHONISTE === $this->getMainProfil()->getRole();
    }

    /**
     * @return bool
     */
    public function isQualite()
    {
        return !empty($this->mainProfil) && Profil::QUALITE === $this->getMainProfil()->getRole();
    }

    /**
     * @return bool
     */
    public function isAdmin()
    {
        return !empty($this->mainProfil) && in_array($this->getMainProfil()->getRole(), [Profil::ADMIN, Profil::ADMIN_PHONE, Profil::ADMIN_CHAT]);
    }

    /**
     * @return bool
     */
    public function isValidation()
    {
        return !empty($this->mainProfil) && in_array($this->getMainProfil()->getRole(), [Profil::VALIDATION]);
    }

    /**
     * @return bool
     */
    public function isManagerTel()
    {
        return !empty($this->mainProfil) && in_array($this->getMainProfil()->getRole(), [Profil::MANAGER_PHONE]);
    }

    /**
     * @return bool
     */
    public function isManagerChat()
    {
        return !empty($this->mainProfil) && in_array($this->getMainProfil()->getRole(), [Profil::MANAGER_CHAT]);
    }

    /**
     * @return bool
     */
    public function isManagerPhoning()
    {
        return !empty($this->mainProfil) && in_array($this->getMainProfil()->getRole(), [Profil::MANAGER_PHONIST]);
    }

    /**
     * @return bool
     */
    public function isUnpaid()
    {
        return !empty($this->mainProfil) && in_array($this->getMainProfil()->getRole(), [Profil::UNPAID]);
    }

    public function __toString()
    {
        return $this->username;
    }

    public function isAccountNonExpired()
    {
        return true;
    }
    public function isAccountNonLocked()
    {
        return true;
    }
    public function isCredentialsNonExpired()
    {
        return true;
    }
    public function isEnabled()
    {
        return !!$this->actif;
    }

    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->username,
            $this->password,
            $this->salt,
            $this->mainProfil,
        ));
    }

    public function unserialize($serialized)
    {
        list(
            $this->id,
            $this->username,
            $this->password,
            $this->salt,
            $this->mainProfil
        ) = unserialize($serialized);
    }

    /**
     * Set sexe.
     *
     * @param int $sexe
     *
     * @return Utilisateur
     */
    public function setSexe($sexe)
    {
        $this->sexe = $sexe;

        return $this;
    }

    /**
     * Get sexe.
     *
     * @return Utilisateur
     */
    public function getSexe()
    {
        return $this->sexe;
    }

    /**
     * @param $poste
     *
     * @return $this
     */
    public function setPoste($poste)
    {
        $this->poste = $poste;

        return $this;
    }

    /**
     * Get poste.
     */
    public function getPoste()
    {
        return $this->poste;
    }

    /**
     * @param $chatType
     *
     * @return $this
     */
    public function setChatType($chatType)
    {
        $this->chatType = $chatType;

        return $this;
    }

    /**
     * Get chatType.
     */
    public function getChatType()
    {
        return $this->chatType;
    }

    /**
     * Set isChatAvailable.
     *
     * @param bool $isChatAvailable
     *
     * @return \KGC\UserBundle\Entity\Utilisateur
     */
    public function setIsChatAvailable($isChatAvailable)
    {
        $this->isChatAvailable = $isChatAvailable;

        return $this;
    }

    /**
     * Get isChatAvailable.
     *
     * @return bool
     */
    public function getIsChatAvailable()
    {
        return $this->isChatAvailable;
    }

    /**
     * Get isDecryptAvailable.
     *
     * @return bool
     */
    public function getIsDecryptAvailable()
    {
        return $this->isAdmin() || $this->getMainProfil()->getRole() == Profil::UNPAID || $this->getMainProfil()->getRole() == Profil::STANDARD;
    }

    /**
     * Add chatParticipant.
     *
     * @param ChatParticipant $chatParticipant
     *
     * @return Utilisateur
     */
    public function addChatParticipant(ChatParticipant $chatParticipant)
    {
        $this->chatParticipants[] = $chatParticipant;

        return $this;
    }

    /**
     * Remove chatParticipant.
     *
     * @param ChatParticipant $chatParticipant
     */
    public function removeChatParticipant(ChatParticipant $chatParticipant)
    {
        $this->chatParticipants->removeElement($chatParticipant);
    }

    /**
     * Get chatParticipants.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChatParticipants()
    {
        return $this->chatParticipants;
    }

    public function getVoyantEditLibelle()
    {
        return $this->getUsername().' ('.($this->getChatType() ? $this->getChatType()->getEntitled() : '-').')';
    }

    public function isAllowedToMakeCall() {
        return !in_array($this->getMainProfil()->getRole(), [Profil::STANDARD, Profil::PHONISTE, Profil::ADMIN, Profil::ADMIN_PHONE, Profil::ADMIN_CHAT, Profil::MANAGER_CHAT, Profil::AFFILIATE]);
    }

    public function duplicationMaskCB() {
        return !in_array($this->getMainProfil()->getRole(), [Profil::PHONISTE]);
    }

    public function duplicationMaskPhone() {
        return !in_array($this->getMainProfil()->getRole(), [Profil::STANDARD, Profil::PHONISTE, Profil::QUALITE]);
    }
}