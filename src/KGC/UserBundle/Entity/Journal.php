<?php

// src/KGC/UserBundle/Entity/Journal.php


namespace KGC\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entité Journal.
 *
 * @category Entity
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 *
 * @ORM\Table(name="journal_utilisateur")
 * @ORM\Entity()
 */
class Journal
{
    /**
     * @var int
     * @ORM\Column(name="jrl_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \DateTime
     * @ORM\Column(name="jrl_date", type="date")
     */
    protected $date;

    /**
     * @var string
     * @ORM\Column(name="jrl_type", type="string", nullable=false, length=20)
     */
    protected $type;

    /**
     * @var \KGC\RdvBundle\Entity\RDV
     * @ORM\ManyToOne(targetEntity="\KGC\UserBundle\Entity\Utilisateur", inversedBy="journal")
     * @ORM\JoinColumn(nullable=false, name="jrl_utilisateur", referencedColumnName="uti_id")
     */
    protected $utilisateur;

    /**
     * @var \KGC\RdvBundle\Entity\RDV
     * @ORM\ManyToOne(targetEntity="\KGC\UserBundle\Entity\Utilisateur")
     * @ORM\JoinColumn(nullable=false, name="jrl_createur", referencedColumnName="uti_id")
     */
    protected $createur;

    /**
     * @var \DateTime
     * @ORM\Column(name="jrl_date_creation", type="date")
     */
    protected $date_creation;

    /**
     * @var string
     * @ORM\Column(name="sui_commentaire", nullable=true, type="string", length=255)
     */
    protected $commentaire = null;

    const ABSENCE = 'STATE_ABSENCE';
    const LATENESS = 'STATE_LATENESS';

    /**
     * Constructeur.
     */
    public function __construct($createur = null, $utilisateur = null)
    {
        if ($createur instanceof Utilisateur) {
            $this->createur = $createur;
        }
        if ($utilisateur instanceof Utilisateur) {
            $this->utilisateur = $utilisateur;
        }
        $this->date_creation = new \DateTime();
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
     * Set date.
     *
     * @param \DateTime $date
     *
     * @return \KGC\RdvBundle\Entity\Journal
     */
    public function setDate(\DateTime $date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date.
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get utilisateur.
     *
     * @return \KGC\UserBundle\Entity\Utilisateur
     */
    public function getUtilisateur()
    {
        return $this->utilisateur;
    }

    /**
     * Set utilisateur.
     *
     * @param \KGC\UserBundle\Entity\Utilisateur $utilisateur
     *
     * @return $this
     */
    public function setUtilisateur($utilisateur)
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    /**
     * Get createur.
     *
     * @return \KGC\UserBundle\Entity\Utilisateur
     */
    public function getCreateur()
    {
        return $this->createur;
    }

    /**
     * Set createur.
     *
     * @param \KGC\UserBundle\Entity\Utilisateur $createur
     *
     * @return $this
     */
    public function setCreateur($createur)
    {
        $this->createur = $createur;

        return $this;
    }

    /**
     * Get date.
     *
     * @return \DateTime
     */
    public function getDateCreation()
    {
        return $this->date_creation;
    }

    /**
     * @param $commentaire
     *
     * @return $this
     */
    public function setCommentaire($commentaire)
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    /**
     * Get commentaire.
     *
     * @return string
     */
    public function getCommentaire()
    {
        return $this->commentaire;
    }
}
