<?php

// src/KGC/UserBundle/Entity/Profil.php


namespace KGC\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use KGC\RdvBundle\Entity\Support;
use Symfony\Component\Security\Core\Role\RoleInterface;

/**
 * Entité Profil : profils des accès utilisateurs à l'application.
 *
 * @category Entity
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 *
 * @ORM\Table(name="profil")
 * @ORM\Entity(repositoryClass="KGC\UserBundle\Repository\ProfilRepository")
 */
class Profil implements RoleInterface
{
    const ADMIN = 'ROLE_ADMIN';
    const ADMIN_PHONE = 'ROLE_ADMIN_PHONE';
    const STANDARD = 'ROLE_STANDARD';
    const ADMIN_CHAT = 'ROLE_ADMIN_CHAT';
    const MANAGER_CHAT = 'ROLE_MANAGER_CHAT';
    const MANAGER_PHONE = 'ROLE_MANAGER_PHONE';
    const VOYANT = 'ROLE_VOYANT';
    const MANAGER_VOYANT = 'ROLE_MANAGER_VOYANT';
    const PHONISTE = 'ROLE_PHONISTE';
    const QUALITE = 'ROLE_QUALITE';
    const VALIDATION = 'ROLE_VALIDATION';
    const UNPAID = 'ROLE_UNPAID_SERVICE';
    const MANAGER_PHONIST = 'ROLE_MANAGER_PHONIST';
    const STANDARD_DRI_J1 = 'ROLE_STD_DRI_J1';
    const AFFILIATE = 'ROLE_AFFILIATE';

    /**
     * @var int
     *
     * @ORM\Column(name="pro_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="pro_name", type="string", length=30)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(name="pro_role", type="string", length=20)
     */
    protected $role;

    /**
     * @var ArrayCollection(Utilisateur)
     * @ORM\ManyToMany(targetEntity="Utilisateur", mappedBy="profils")
     */
    protected $users;

    /**
     * @var ArrayCollection(KGC\RdvBundle\Entity\Support)
     * @ORM\ManyToMany(targetEntity="KGC\RdvBundle\Entity\Support", mappedBy="profils")
     */
    protected $supports;

    /**
     * @var ArrayCollection(KGC\RdvBundle\Entity\Etiquette)
     * @ORM\ManyToMany(targetEntity="KGC\RdvBundle\Entity\Etiquette", mappedBy="profils", cascade="persist")
     */
    protected $etiquettes;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->supports = new ArrayCollection();
        $this->etiquettes = new ArrayCollection();
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
     * Set name.
     *
     * @param string $name
     *
     * @return \KGC\UserBundle\Entity\Profil
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set role.
     *
     * @param string $role
     *
     * @return \KGC\UserBundle\Entity\Profil
     */
    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role.
     *
     * @see RoleInterface
     *
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Get roleKey.
     *
     * @return string
     */
    public function getRoleKey()
    {
        // on retourne la chaine du role après "ROLE_" (donc au 5e char) en minuscules
        return strtolower(substr($this->role, 5));
    }

    /**
     * Add users.
     *
     * @param \KGC\UserBundle\Entity\Utilisateur $users
     *
     * @return \KGC\UserBundle\Entity\Profil
     */
    public function addUser(Utilisateur $users)
    {
        $this->users[] = $users;

        return $this;
    }

    /**
     * Remove users.
     *
     * @param \KGC\UserBundle\Entity\Utilisateur $users
     */
    public function removeUser(Utilisateur $users)
    {
        $this->users->removeElement($users);
    }

    /**
     * Get users.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Set supports.
     *
     * @param ArrayCollection $supports
     *
     * @return \KGC\UserBundle\Entity\Profil
     */
    public function setSupports($supports)
    {
        $this->supports = $supports;

        return $this;
    }

    /**
     * Add supports.
     *
     * @param \KGC\RdvBundle\Entity\Support $support
     *
     * @return \KGC\UserBundle\Entity\Profil
     */
    public function addSupports(Support $support)
    {
        $this->supports[] = $support;

        return $this;
    }

    /**
     * Remove supports.
     *
     * @param \KGC\RdvBundle\Entity\Support $supports
     */
    public function removeSupports(Support $supports)
    {
        $this->supports->removeElement($supports);
    }

    /**
     * Get supports.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSupports()
    {
        return $this->supports;
    }

    /**
     * Set etiquettes.
     *
     * @param ArrayCollection $etiquettes
     *
     * @return \KGC\UserBundle\Entity\Profil
     */
    public function setEtiquettes($etiquettes)
    {
        $this->etiquettes = new ArrayCollection();
        foreach ($etiquettes as $etiquette) {
            $etiquette->addProfil($this);
            $this->etiquettes[] = $etiquette;
        }

        return $this;
    }

    /**
     * Get etiquettes.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEtiquettes()
    {
        return $this->etiquettes;
    }
}
