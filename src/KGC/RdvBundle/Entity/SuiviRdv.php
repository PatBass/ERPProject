<?php

// src/KGC/RdvBundle/Entity/SuiviRdv.php


namespace KGC\RdvBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use KGC\UserBundle\Entity\Utilisateur;

/**
 * Entité SuiviRdv : Historisation des actions sur consultation.
 *
 * @category Entity
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 *
 * @ORM\Table(name="suiviconsultation",
 *      indexes={
 *          @ORM\Index(name="date_suivi_idx", columns={"sui_date"}),
 *      }
 * )
 * @ORM\Entity(repositoryClass="KGC\RdvBundle\Repository\SuiviRdvRepository")
 */
class SuiviRdv
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="sui_id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \KGC\UserBundle\Entity\Utilisateur
     * @ORM\ManyToOne(targetEntity="\KGC\UserBundle\Entity\Utilisateur")
     * @ORM\JoinColumn(nullable=false, name="sui_utilisateur", referencedColumnName="uti_id")
     */
    protected $utilisateur;

    /**
     * @var \KGC\RdvBundle\Entity\RDV
     * @ORM\ManyToOne(targetEntity="RDV", inversedBy="historique")
     * @ORM\JoinColumn(nullable=false, name="sui_consultation", referencedColumnName="rdv_id")
     */
    protected $rdv;

    /**
     * @var \KGC\RdvBundle\Entity\Etat
     * @ORM\ManyToOne(targetEntity="Etat")
     * @ORM\JoinColumn(nullable=false, name="sui_etat", referencedColumnName="eta_id")
     */
    protected $etat;

    /**
     * @var \KGC\RdvBundle\Entity\Classement
     * @ORM\ManyToOne(targetEntity="Classement")
     * @ORM\JoinColumn(nullable=true, name="sui_classement", referencedColumnName="cla_id")
     */
    protected $classement = null;

    /**
     * @var \KGC\RdvBundle\Entity\ActionSuivi
     * @ORM\ManyToOne(targetEntity="ActionSuivi")
     * @ORM\JoinColumn(nullable=true, name="sui_mainaction", referencedColumnName="act_id")
     */
    protected $mainaction = null;

    /**
     * @var ArrayCollection(KGC\RdvBundle\Entity\ActionSuivi)
     * @ORM\ManyToMany(targetEntity="ActionSuivi")
     * @ORM\JoinTable(name="suivi_actions",
     *                                                        joinColumns={@ORM\JoinColumn(name="suivi_id", referencedColumnName="sui_id")},
     *                                                        inverseJoinColumns={@ORM\JoinColumn(name="action_id", referencedColumnName="act_id")}
     *                                                        )
     */
    protected $actions;

    /**
     * @var \DateTime
     * @ORM\Column(name="sui_date", type="datetime")
     */
    protected $date;

    /**
     * @var int
     * @ORM\Column(name="sui_donneeliee", type="integer", nullable=true)
     */
    protected $donneeLiee = null;

    /**
     * @var string
     * @ORM\Column(name="sui_commentaire", nullable=true, type="string", length=255)
     */
    protected $commentaire = null;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->date = new \DateTime();
        $this->actions = new ArrayCollection();
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
     * Set utilisateur.
     *
     * @param \KGC\UserBundle\Entity\Utilisateur $utilisateur
     *
     * @return \KGC\RdvBundle\Entity\SuiviRdv
     */
    public function setUtilisateur(Utilisateur $utilisateur)
    {
        $this->utilisateur = $utilisateur;

        return $this;
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
     * Set rdv.
     *
     * @param \KGC\RdvBundle\Entity\RDV $rdv
     *
     * @return \KGC\RdvBundle\Entity\SuiviRdv
     */
    public function setRdv(RDV $rdv)
    {
        $this->rdv = $rdv;

        return $this;
    }

    /**
     * Get rdv.
     *
     * @return \KGC\RdvBundle\Entity\RDV
     */
    public function getRdv()
    {
        return $this->rdv;
    }

    /**
     * Set etat.
     *
     * @param \KGC\RdvBundle\Entity\Etat $etat
     *
     * @return \KGC\RdvBundle\Entity\SuiviRdv
     */
    public function setEtat(Etat $etat)
    {
        $this->etat = $etat;

        return $this;
    }

    /**
     * Get etat.
     *
     * @return \KGC\RdvBundle\Entity\Etat
     */
    public function getEtat()
    {
        return $this->etat;
    }

    /**
     * Set classement.
     *
     * @param \KGC\RdvBundle\Entity\Classement $classement
     *
     * @return \KGC\RdvBundle\Entity\SuiviRdv
     */
    public function setClassement(Classement $classement)
    {
        $this->classement = $classement;

        return $this;
    }

    /**
     * Get classement.
     *
     * @return \KGC\RdvBundle\Entity\Classement
     */
    public function getClassement()
    {
        return $this->classement;
    }

    /**
     * @param ActionSuivi $action
     *
     * @return $this
     */
    public function setMainaction(ActionSuivi $action)
    {
        $this->mainaction = $action;

        return $this;
    }

    /**
     * Get mainaction.
     *
     * @return \KGC\RdvBundle\Entity\ActionSuivi
     */
    public function getMainaction()
    {
        return $this->mainaction;
    }

    /**
     * Add actions.
     *
     * @param \KGC\RdvBundle\Entity\ActionSuivi $action
     *
     * @return \KGC\UserBundle\Entity\SuiviRdv
     */
    public function addActions(ActionSuivi $action)
    {
        $this->actions[] = $action;

        return $this;
    }

    /**
     * @param ActionSuivi $action
     */
    public function removeActions(ActionSuivi $action)
    {
        $this->actions->removeElement($action);
    }

    /**
     * Get actions.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @param $date
     *
     * @return $this
     */
    public function setDate($date)
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
     * @param $donneeLiee
     *
     * @return $this
     */
    public function setDonneeLiee($donneeLiee)
    {
        $this->donneeLiee = $donneeLiee;

        return $this;
    }

    /**
     * Get donneeLiee.
     *
     * @return int
     */
    public function getDonneeLiee()
    {
        return $this->donneeLiee;
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

    /**
     * Cette ligne d'historique doit elle être cachée par défaut ?
     *
     * @return bool
     */
    public function getCompact()
    {
        $compact = $this->mainaction instanceof ActionSuivi ? $this->mainaction->getCompact() : true;
        foreach ($this->actions as $action) {
            if (!$action->getCompact()) {
                $compact = false;
            }
        }

        return $compact;
    }

    public function isEmpty()
    {
        return $this->mainaction === null && $this->actions->isEmpty() && $this->commentaire === null;
    }
}
