<?php
// src/KGC/RdvBundle/Entity/RDV.php

namespace KGC\RdvBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use KGC\Bundle\SharedBundle\Entity\Adresse;
use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\Bundle\SharedBundle\Entity\LandingUser;
use KGC\Bundle\SharedBundle\Entity\Website;
use KGC\ClientBundle\Entity\Historique;
use KGC\CommonBundle\Upgrade\UpgradeDate;
use KGC\RdvBundle\Validator\DateConsultation;
use KGC\UserBundle\Entity\Utilisateur;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ExecutionContextInterface;

/**
 * Entité RDV : Rendez-vous de consulation.
 *
 * @category Entity
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 *
 * @ORM\Table(name="consultation",
 *      indexes={@ORM\Index(name="phone_idx", columns={"rdv_numtel"})},
 *      indexes={@ORM\Index(name="date_consult_idx", columns={"rdv_date_consultation"})},
 *      indexes={@ORM\Index(name="date_contact_idx", columns={"rdv_date_contact"})},
 * )
 * @ORM\Entity(repositoryClass="KGC\RdvBundle\Repository\RDVRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class RDV implements \KGC\Bundle\SharedBundle\Entity\Interfaces\RDV
{
    const RDV_FREE_TIME_LIMIT = 10;

    const BATCH_PROCESSING = 'processing';

    const SECU_PENDING = 'PENDING';
    const SECU_DENIED = 'DENIED';
    const SECU_DONE = 'DONE';
    const SECU_SKIPPED = 'SKIPPED';

    /**
     * @var int
     * @ORM\Column(name="rdv_id", type="integer", columnDefinition="MEDIUMINT(8) UNSIGNED NOT NULL")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \KGC\Bundle\SharedBundle\Entity\Website
     * @ORM\ManyToOne(targetEntity="\KGC\Bundle\SharedBundle\Entity\Website")
     * @ORM\JoinColumn(nullable=false, name="rdv_website", referencedColumnName="web_id")
     * @Assert\NotBlank()
     * @Assert\Valid()
     */
    protected $website;

    /**
     * @var \KGC\ClientBundle\Entity\IdAstro
     * @ORM\ManyToOne(targetEntity="\KGC\ClientBundle\Entity\IdAstro", inversedBy="consultations", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, name="rdv_idastro", referencedColumnName="ida_id")
     */
    protected $idAstro = null;

    /**
     * @var int
     * @ORM\Column(name="rdv_idtransactionmyastro", type="integer", nullable=true)
     */
    protected $idtransactionmyastro = null;

    /**
     * @var \DateTime
     * @ORM\Column(name="rdv_date_contact", type="datetime", nullable=false)
     * @Assert\DateTime()
     */
    protected $dateContact;

    /**
     * @var \DateTime
     * @ORM\Column(name="rdv_date_consultation", type="datetime", nullable=false)
     * @Assert\NotBlank(message="Aucune date de consultation nʼa été saisie.")
     * @Assert\DateTime()
     * @DateConsultation()
     */
    protected $dateConsultation;

    /**
     * @var string
     * @ORM\Column(name="rdv_securisation", type="string", length=20, nullable=false)
     */
    protected $securisation = self::SECU_PENDING;

    /**
     * @var tpe
     * @ORM\ManyToOne(targetEntity="TPE")
     * @ORM\JoinColumn(nullable=true, name="rdv_tpe", referencedColumnName="tpe_id")
     */
    protected $tpe;

    /**
     * @var bool
     * @ORM\Column(name="rdv_miserelation", type="boolean", nullable=true)
     */
    protected $miserelation = null;

    /**
     * @var bool
     * @ORM\Column(name="rdv_priseencharge", type="boolean", nullable=true)
     */
    protected $priseencharge = null;

    /**
     * @var bool
     * @ORM\Column(name="rdv_consultation", type="boolean", nullable=true)
     */
    protected $consultation = null;

    /**
     * @var bool
     * @ORM\Column(name="rdv_paiement", type="boolean", nullable=true)
     */
    protected $paiement = null;

    /**
     * @var bool
     * @ORM\Column(name="rdv_cloture", type="boolean", nullable=true)
     */
    protected $cloture = null;

    /**
     * @var bool
     * @ORM\Column(name="rdv_balance", type="boolean", nullable=true)
     */
    protected $balance = null;

    /**
     * @var \KGC\Bundle\SharedBundle\Entity\Client
     * @ORM\ManyToOne(targetEntity="\KGC\Bundle\SharedBundle\Entity\Client", inversedBy="consultations")
     * @ORM\JoinColumn(nullable=false, name="rdv_client", referencedColumnName="id")
     * @Assert\Valid()
     */
    protected $client;

    /**
     * @var string
     * @ORM\Column(name="rdv_numtel", type="string", length=20, nullable=false)
     * @Assert\NotBlank()
     */
    protected $numtel1;

    /**
     * @var string
     * @ORM\Column(name="rdv_numtel2", type="string", length=20, nullable=true)
     */
    protected $numtel2;

    /**
     * @var \KGC\Bundle\SharedBundle\Entity\Adresse
     * @ORM\ManyToOne(targetEntity="\KGC\Bundle\SharedBundle\Entity\Adresse", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false, name="rdv_adresse", referencedColumnName="adr_id")
     * @Assert\Valid()
     */
    protected $adresse;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $questionCode;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $questionText;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $questionSubject;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $questionContent;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $spouseName;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $spouseSign;

    /**
     * @var date
     * @ORM\Column(type="date", nullable=true)
     */
    protected $spouseBirthday;

    /**
     * @var Source
     *
     * @ORM\ManyToOne(targetEntity="Source", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, name="rdv_source", referencedColumnName="src_id")
     */
    protected $source;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="CarteBancaire", cascade={"persist"}, inversedBy="rdvs")
     * @ORM\OrderBy({"id" = "ASC"})
     * @ORM\JoinTable(name="consultation_cartebancaire",
     *     joinColumns={@ORM\JoinColumn(name="consultation_id", referencedColumnName="rdv_id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="cartebancaire_id", referencedColumnName="cb_id")}
     * )
     * @Assert\Valid()
     */
    protected $cartebancaires;

    /**
     * @var \KGC\RdvBundle\Entity\Etat
     * @ORM\ManyToOne(targetEntity="Etat")
     * @ORM\JoinColumn(nullable=false, name="rdv_etat", referencedColumnName="eta_id")
     */
    protected $etat;

    /**
     * @var \KGC\RdvBundle\Entity\Classement
     * @ORM\ManyToOne(targetEntity="Classement")
     * @ORM\JoinColumn(nullable=false, name="rdv_classement", referencedColumnName="cla_id")
     */
    protected $classement;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Etiquette")
     * @ORM\JoinTable(name="consultation_etiquette",
     *                                                   joinColumns={@ORM\JoinColumn(name="consultation_id", referencedColumnName="rdv_id")},
     *                                                   inverseJoinColumns={@ORM\JoinColumn(name="etiquette_id", referencedColumnName="eti_id")}
     *                                                   )
     */
    protected $etiquettes;

    /**
     * @var \KGC\RdvBundle\Entity\Support
     * @ORM\ManyToOne(targetEntity="Support")
     * @ORM\JoinColumn(nullable=false, name="rdv_support", referencedColumnName="sup_id")
     * @Assert\NotBlank()
     * @Assert\Valid()
     */
    protected $support;

    /**
     * @var \KGC\RdvBundle\Entity\CodePromo
     * @ORM\ManyToOne(targetEntity="CodePromo")
     * @ORM\JoinColumn(nullable=true, name="rdv_codepromo", referencedColumnName="cpm_id")
     * @Assert\Valid()
     */
    protected $codepromo = null;

    /**
     * @var \KGC\UserBundle\Entity\Utilisateur
     * @ORM\ManyToOne(targetEntity="\KGC\UserBundle\Entity\Utilisateur")
     * @ORM\JoinColumn(nullable=true, name="rdv_consultant", referencedColumnName="uti_id")
     * @Assert\Valid()
     */
    protected $consultant = null;

    /**
     * @var \KGC\UserbUndle\Entity\Voyant
     * @ORM\ManyToOne(targetEntity="\KGC\UserBundle\Entity\Voyant")
     * @ORM\JoinColumn(nullable=true, name="rdv_voyant", referencedColumnName="voy_id")
     * @Assert\Valid()
     */
    protected $voyant = null;

    /**
     * @var \KGC\UserBundle\Entity\Utilisateur
     * @ORM\ManyToOne(targetEntity="\KGC\UserBundle\Entity\Utilisateur")
     * @ORM\JoinColumn(nullable=false, name="rdv_proprio", referencedColumnName="uti_id")
     * @Assert\Valid()
     */
    protected $proprio;

    /**
     * @var string
     * @ORM\Column(nullable=true, name="rdv_noteslibres", type="text")
     */
    protected $notesLibres = null;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="SuiviRdv", mappedBy="rdv")
     * @ORM\OrderBy({"date" = "DESC", "id" = "DESC"})
     */
    protected $historique;

    /**
     * @var \KGC\RdvBundle\Entity\Tarification
     * @ORM\OneToOne(targetEntity="Tarification", inversedBy="rdv", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, name="rdv_tarification", referencedColumnName="tar_id")
     * @Assert\Valid()
     */
    protected $tarification = null;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="Encaissement", mappedBy="consultation", cascade={"persist"})
     * @ORM\OrderBy({"date" = "ASC", "id" = "ASC"})
     * @Assert\Valid()
     */
    protected $encaissements;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="\KGC\ClientBundle\Entity\Historique", mappedBy="rdv", cascade={"persist", "detach", "remove"})
     */
    protected $notesVoyant;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="EnvoiProduit", mappedBy="consultation")
     */
    protected $envoisProduits;

    /**
     * @var EnvoiProduit
     * @ORM\OneToOne(targetEntity="EnvoiProduit", mappedBy="allumage_consultation")
     */
    protected $allumage = null;

    /**
     * @var array of strings
     */
    protected $warnings = array();

    /**
     * @var string
     *
     * @ORM\Column(name="rdv_gclid", type="string", length=2048, nullable=true)
     */
    protected $gclid;

    /**
     * @var string
     * @ORM\Column(name="rdv_montant_encaisse", type="integer")
     */
    protected $montantEncaisse = 0;

    /**
     * @var \KGC\ClientBundle\Entity\FormUrl
     * @ORM\ManyToOne(targetEntity="\KGC\RdvBundle\Entity\FormUrl", inversedBy="consultations", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, name="rdv_formurl", referencedColumnName="url_id")
     */
    protected $form_url = null;

    /**
     * @var string
     * @ORM\Column(name="rdv_batch_status", type="string", nullable=true, length=20)
     */
    protected $batchStatus;

    /**
     * @var string
     * @ORM\Column(name="rdv_batch_end_datetime", type="datetime", nullable=true)
     */
    protected $batchEndDate;

    /**
     * @var \KGC\PaymentBundle\Entity\Authorization
     * @ORM\ManyToOne(targetEntity="\KGC\PaymentBundle\Entity\Authorization", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, name="rdv_preauthorization_id", referencedColumnName="id")
     */
    protected $preAuthorization = null;

    /**
     * @var string
     * @ORM\Column(name="rdv_new_card_hash", type="string", nullable=true, length=50)
     */
    protected $newCardHash;

    /**
     * @var datetime
     * @ORM\Column(name="rdv_new_card_hash_created_at", type="datetime", nullable=true)
     */
    protected $newCardHashCreatedAt;

    /**
     * @var \KGC\Bundle\SharedBundle\Entity\LandingUser
     * @ORM\ManyToOne(targetEntity="\KGC\Bundle\SharedBundle\Entity\LandingUser", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, name="prospect", referencedColumnName="id")
     */
    protected $prospect;

    /**
     * @var datetime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $dateState;

    /**
     * @var \KGC\RdvBundle\Entity\RdvState
     * @ORM\ManyToOne(targetEntity="\KGC\RdvBundle\Entity\RdvState", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, name="state", referencedColumnName="state_id")
     */
    protected $state;

    /**
     * Constructeur.
     */
    public function __construct(Utilisateur $proprio)
    {
        $this->setProprio($proprio);
        $this->dateContact = new \DateTime();
        $this->dateConsultation = new \DateTime();
        $this->cartebancaires = new ArrayCollection();
        $this->etiquettes = new ArrayCollection();
        $this->historique = new ArrayCollection();
        $this->encaissements = new ArrayCollection();
        $this->notesVoyant = new ArrayCollection();
        $this->envoisProduits = new ArrayCollection();
    }

    public function __clone()
    {
        if ($this->tarification instanceof Tarification) {
            $this->tarification = clone $this->tarification;
        }
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
     * @return $this
     */
    public function resetId()
    {
        $this->id = null;

        return $this;
    }

    /**
     * @param Website $website
     *
     * @return $this
     */
    public function setWebsite(Website $website)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * @return Website
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * Set idastro.
     *
     * @param \KGC\ClientBundle\Entity\IdAstro|null $idastro
     *
     * @return \KGC\RdvBundle\Entity\RDV
     */
    public function setIdAstro($idastro)
    {
        if (!isset($this->idAstro) and is_object($idastro)) {  // si c'est la première initialisation
            $idastro->addConsultation($this);
        }
        $this->idAstro = $idastro;

        return $this;
    }

    /**
     * @return \KGC\ClientBundle\Entity\IdAstro
     */
    public function getIdAstro()
    {
        return $this->idAstro;
    }

    /**
     * Get idtransactionmyastro.
     *
     * @return int
     */
    public function getIdtransactionmyastro()
    {
        return $this->idtransactionmyastro;
    }

    /**
     * @param $idtransactionmyastro
     *
     * @return $this
     */
    public function setIdtransactionmyastro($idtransactionmyastro)
    {
        $this->idtransactionmyastro = $idtransactionmyastro;

        return $this;
    }

    /**
     * @param $dateContact
     *
     * @return $this
     */
    public function setDateContact($dateContact)
    {
        $this->dateContact = $dateContact;

        return $this;
    }

    /**
     * Get dateContact.
     *
     * @return \DateTime
     */
    public function getDateContact()
    {
        return $this->dateContact;
    }

    /**
     * @param $dateConsultation
     *
     * @return $this
     */
    public function setDateConsultation($dateConsultation)
    {
        if (null !== $dateConsultation) {
            $h = $dateConsultation->format('H');
            $m = $dateConsultation->format('i') >= 30 ? '30' : '00';
            $dateConsultation->setTime($h, $m);
        }

        $this->dateConsultation = $dateConsultation;

        return $this;
    }

    /**
     * Get dateConsultation.
     *
     * @return \DateTime
     */
    public function getDateConsultation()
    {
        return $this->dateConsultation;
    }

    /**
     * Get heure consultation.
     *
     * @return string
     */
    public function getHeureConsultation()
    {
        return $this->dateConsultation->format('H:i');
    }

    /**
     * @param $securisation
     *
     * @return $this
     */
    public function setSecurisation($securisation)
    {
        $this->securisation = $securisation;

        return $this;
    }

    /**
     * Get securisation.
     *
     * @return string
     */
    public function getSecurisation()
    {
        return $this->securisation;
    }

    /**
     * @param $tpe
     *
     * @return $this
     */
    public function setTpe($tpe)
    {
        $this->tpe = $tpe;

        return $this;
    }

    /**
     * Get tpe.
     *
     * @return \KGC\RdvBundle\Entity\TPE
     */
    public function getTpe()
    {
        return $this->tpe;
    }

    /**
     * @param $miserelation
     *
     * @return $this
     */
    public function setMiserelation($miserelation)
    {
        $this->miserelation = $miserelation;

        return $this;
    }

    /**
     * Get miserelation.
     *
     * @return bool
     */
    public function getMiserelation()
    {
        return $this->miserelation;
    }

    /**
     * @param $priseencharge
     *
     * @return $this
     */
    public function setPriseencharge($priseencharge)
    {
        $this->priseencharge = $priseencharge;

        return $this;
    }

    /**
     * Get priseencharge.
     *
     * @return bool
     */
    public function getPriseencharge()
    {
        return $this->priseencharge;
    }

    /**
     * @param $consultation
     *
     * @return $this
     */
    public function setConsultation($consultation)
    {
        $this->consultation = $consultation;

        return $this;
    }

    /**
     * Get consultation.
     *
     * @return bool
     */
    public function getConsultation()
    {
        return $this->consultation;
    }

    /**
     * @param $paiement
     *
     * @return $this
     */
    public function setPaiement($paiement)
    {
        $this->paiement = $paiement;

        return $this;
    }

    /**
     * Get paiement.
     *
     * @return bool
     */
    public function getPaiement()
    {
        return $this->paiement;
    }

    /**
     * @param $cloture
     *
     * @return $this
     */
    public function setCloture($cloture)
    {
        $this->cloture = $cloture;

        return $this;
    }

    /**
     * Get cloture.
     *
     * @return bool
     */
    public function getCloture()
    {
        return $this->cloture;
    }

    /**
     * @param $balance
     *
     * @return $this
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * Get balance.
     *
     * @return bool
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * @param Client $client
     *
     * @return $this
     */
    public function setClient(Client $client)
    {
        if (!isset($this->client)) {  // si c'est la première initialisation du client
            $client->addConsultation($this);
        }
        $this->client = $client;

        return $this;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param $numtel
     *
     * @return $this
     */
    public function setNumtel1($numtel)
    {
        $this->numtel1 = $numtel;

        return $this;
    }

    /**
     * Get numtel.
     *
     * @return string
     */
    public function getNumtel1()
    {
        return $this->numtel1;
    }

    /**
     * @param $numtel
     *
     * @return $this
     */
    public function setNumtel2($numtel)
    {
        $this->numtel2 = $numtel;

        return $this;
    }

    /**
     * Get numtel.
     *
     * @return string
     */
    public function getNumtel2()
    {
        return $this->numtel2;
    }

    /**
     * @param Adresse $adresse
     *
     * @return $this
     */
    public function setAdresse(Adresse $adresse)
    {
        $this->adresse = $adresse;

        return $this;
    }

    /**
     * Get adresse.
     *
     * @return \KGC\Bundle\SharedBundle\Entity\Adresse
     */
    public function getAdresse()
    {
        return $this->adresse;
    }

    /**
     * @return Source
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param $source
     *
     * @return $this
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * @param $cartebancaires
     *
     * @return $this
     */
    public function setCartebancaires($cartebancaires)
    {
        $this->cartebancaires = new ArrayCollection();
        foreach ($cartebancaires as $cb) {
            $this->addCartebancaires($cb);
        }

        return $this;
    }

    /**
     * @param CarteBancaire $cb
     *
     * @return $this
     */
    public function addCartebancaires(CarteBancaire $cb)
    {
        $base = count($this->cartebancaires);
        $cb->setNom('CB' . ($base + 1));
        $cb->addRdvs($this);
        $this->cartebancaires[] = $cb;

        return $this;
    }

    /**
     * @param CarteBancaire $cartebancaires
     *
     * @return $this
     */
    public function removeCartebancaires(CarteBancaire $cartebancaires)
    {
        $this->cartebancaires->removeElement($cartebancaires);

        return $this;
    }

    /**
     * Get cartebancaires.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCartebancaires()
    {
        return $this->cartebancaires;
    }

    /**
     * @return Etat
     */
    public function getEtat()
    {
        return $this->etat;
    }

    /**
     * Set etat.
     *
     * @param \KGC\RdvBundle\Entity\Etat $etat
     *
     * @return \KGC\RdvBundle\Entity\RDV
     */
    public function setEtat($etat)
    {
        $this->etat = $etat;

        return $this;
    }

    /**
     * @return Classement
     */
    public function getClassement()
    {
        return $this->classement;
    }

    /**
     * @param $classement
     *
     * @return $this
     */
    public function setClassement($classement)
    {
        $this->classement = $classement;

        return $this;
    }

    /**
     * @param $etiquettes
     *
     * @return $this
     */
    public function setEtiquettes($etiquettes)
    {
        $this->etiquettes = new ArrayCollection();
        foreach ($etiquettes as $etiquette) {
            $this->addEtiquettes($etiquette);
        }

        return $this;
    }

    /**
     * @param Etiquette $etiquette
     *
     * @return $this
     */
    public function addEtiquettes(Etiquette $etiquette)
    {
        if (!$this->etiquettes->contains($etiquette)) {
            $this->etiquettes[] = $etiquette;
        }

        return $this;
    }

    /**
     * @param Etiquette $etiquettes
     *
     * @return $this
     */
    public function removeEtiquettes(Etiquette $etiquettes)
    {
        $this->etiquettes->removeElement($etiquettes);

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

    /**
     * Get support.
     *
     * @return \KGC\RdvBundle\Entity\Support
     */
    public function getSupport()
    {
        return $this->support;
    }

    /**
     * @param Support $support
     *
     * @return $this
     */
    public function setSupport($support)
    {
        $this->support = $support;

        return $this;
    }

    /**
     * Get codepromo.
     *
     * @return \KGC\RdvBundle\Entity\CodePromo
     */
    public function getCodePromo()
    {
        return $this->codepromo;
    }

    /**
     * @param CodePromo $codepromo
     *
     * @return $this
     */
    public function setCodePromo(CodePromo $codepromo)
    {
        $this->codepromo = $codepromo;

        return $this;
    }

    /**
     * Get voyant.
     *
     * @return \KGC\UserBundle\Entity\Voyant
     */
    public function getVoyant()
    {
        return $this->voyant;
    }

    /**
     * Set voyant.
     *
     * @param \KGC\UserBundle\Entity\Voyant $voyant
     *
     * @return \KGC\RdvBundle\Entity\RDV
     */
    public function setVoyant($voyant)
    {
        $this->voyant = $voyant;

        return $this;
    }

    /**
     * Get consultant.
     *
     * @return \KGC\UserBundle\Entity\Utilisateur
     */
    public function getConsultant()
    {
        return $this->consultant;
    }

    /**
     * Set consultant.
     *
     * @param \KGC\UserBundle\Entity\Utilisateur $consultant
     *
     * @return \KGC\RdvBundle\Entity\RDV
     */
    public function setConsultant($consultant)
    {
        $this->consultant = $consultant;

        return $this;
    }

    /**
     * Get proprio.
     *
     * @return \KGC\UserBundle\Entity\Utilisateur
     */
    public function getProprio()
    {
        return $this->proprio;
    }

    /**
     * @param Utilisateur $proprio
     *
     * @return $this
     */
    public function setProprio(Utilisateur $proprio)
    {
        if ($proprio !== null) {
            $this->proprio = $proprio;
        }

        return $this;
    }

    /**
     * Set notesLibres.
     *
     * @param string $notesLibres
     *
     * @return \KGC\RdvBundle\Entity\RDV
     */
    public function setNotesLibres($notesLibres)
    {
        $this->notesLibres = $notesLibres;

        return $this;
    }

    /**
     * Get notesLibres.
     *
     * @return string
     */
    public function getNotesLibres()
    {
        return $this->notesLibres;
    }

    /**
     * @param SuiviRdv $historique
     *
     * @return $this
     */
    public function addHistorique(SuiviRdv $historique)
    {
        $this->historique[] = $historique;

        return $this;
    }

    /**
     * @param SuiviRdv $historique
     */
    public function removeHistorique(SuiviRdv $historique)
    {
        $this->historique->removeElement($historique);
    }

    /**
     * Get historique.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getHistorique()
    {
        return $this->historique;
    }

    /**
     * Set tarification.
     *
     * @param \KGC\RdvBundle\Entity\Tarification $tarification
     *
     * @return \KGC\RdvBundle\Entity\RDV
     */
    public function setTarification($tarification)
    {
        $this->tarification = $tarification;
        if ($tarification instanceof Tarification) {
            $tarification->setRdv($this);
        }

        return $this;
    }

    /**
     * Get tarification.
     *
     * @return \KGC\RdvBundle\Entity\Tarification
     */
    public function getTarification()
    {
        return $this->tarification;
    }

    /**
     * @param $encaissements
     *
     * @return $this
     */
    public function setEncaissements($encaissements)
    {
        $this->encaissements = new ArrayCollection();
        foreach ($encaissements as $encaissement) {
            $this->addEncaissements($encaissement);
        }

        return $this;
    }

    /**
     * Add encaissements.
     *
     * @param \KGC\RdvBundle\Entity\Encaissement $encaissements
     *
     * @return \KGC\RdvBundle\Entity\RDV
     */
    public function addEncaissements(Encaissement $encaissements)
    {
        if ($encaissements->getMontant() != 0) {
            $encaissements->setConsultation($this);
            $this->encaissements[] = $encaissements;
        }

        return $this;
    }

    /**
     * @param Encaissement $encaissements
     *
     * @return $this
     */
    public function removeEncaissements(Encaissement $encaissements)
    {
        $this->encaissements->removeElement($encaissements);

        return $this;
    }

    /**
     * Get encaissements.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEncaissements()
    {
        return $this->encaissements;
    }

    /**
     * getLast Encaissement : retourne le dernier encaissement traité.
     *
     * @return \KGC\RdvBundle\Entity\Encaissement|null
     */
    public function getLastEncaissement()
    {
        $last_enc = null;

        $encaissements = $this->encaissements->filter(function ($element) {
            return ($element instanceof Encaissement) && ($element->getEtat() !== Encaissement::PLANNED);
        });
        foreach ($encaissements as $enc) {
            if ($last_enc === null) {
                $last_enc = $enc;
            } else {
                if ($enc->getDate() > $last_enc->getDate()) {
                    $last_enc = $enc;
                }
            }
        }

        return $last_enc;
    }

    /**
     * getNext Encaissement : retourne le prochain encaissement à traiter.
     *
     * @return \KGC\RdvBundle\Entity\Encaissement|null
     */
    public function getNextEncaissement()
    {
        $next_enc = null;
        $encaissements = $this->encaissements->filter(function ($element) {
            return ($element instanceof Encaissement) && ($element->getEtat() == Encaissement::PLANNED);
        });
        foreach ($encaissements as $enc) {
            if ($next_enc === null) {
                $next_enc = $enc;
            } else {
                if ($enc->getDate() < $next_enc->getDate()) {
                    $next_enc = $enc;
                }
            }
        }

        return $next_enc;
    }

    /**
     * @return ArrayCollection
     */
    public function getNotesVoyant()
    {
        return $this->notesVoyant;
    }

    /**
     * @return ArrayCollection
     */
    public function getNotesVoyantByType($type)
    {
        if ($this->notesVoyant->first()) {
            do {
                if ($this->notesVoyant->current()->getType() === $type) {
                    return $this->notesVoyant->current();
                }
            } while ($this->notesVoyant->next());
        }

        return;
    }

    public function getReminderDate()
    {
        $reminder = $this->getNotesVoyantByType(Historique::TYPE_REMINDER);
        if ($reminder instanceof Historique) {
            return $reminder->getDatetime();
        }

        return;
    }

    public function getReminderState()
    {
        $reminder_state = $this->getNotesVoyantByType(Historique::TYPE_REMINDER_STATE);
        if ($reminder_state instanceof Historique) {
            return $reminder_state->getOption();
        }

        return;
    }

    /**
     * @param NotesVoyant $historique
     */
    public function addNotesVoyant(Historique $historique)
    {
        $this->notesVoyant->add($historique);
    }

    /**
     * @param NotesVoyant $historique
     */
    public function removeNotesVoyant(Historique $historique)
    {
        $this->notesVoyant->removeElement($historique);
    }

    /**
     * Set envoisProduits.
     *
     * @param $envoisProduits
     *
     * @return \KGC\RdvBundle\Entity\RDV
     */
    public function setEnvoisProduits($envoisProduits)
    {
        $this->envoisProduits = new ArrayCollection();
        foreach ($envoisProduits as $envoi) {
            $this->addEnvoisProduits($envoi);
        }

        return $this;
    }

    /**
     * Add envoisProduits.
     *
     * @param \KGC\RdvBundle\Entity\EnvoiProduit $envoi
     *
     * @return \KGC\RdvBundle\Entity\RDV
     */
    public function addEnvoisProduits(EnvoiProduit $envoi)
    {
        $this->envoisproduits[] = $envoi;

        return $this;
    }

    /**
     * @param EnvoiProduit $envoi
     *
     * @return $this
     */
    public function removeEnvoisProduits(EnvoiProduit $envoi)
    {
        $this->envoisProduits->removeElement($envoi);

        return $this;
    }

    /**
     * Get envoisProduits.
     *
     * @return ArrayCollection
     */
    public function getEnvoisProduits()
    {
        return $this->envoisProduits;
    }

    /**
     * getWarnings
     * lance les vérifications et renvoie les alertes.
     *
     * @return array
     */
    public function getWarnings()
    {
        // vérifications & remplissage de warnings
        if (empty($this->warnings)) {
            $this->isMontantEncaissementsValid();
        }

        return $this->warnings;
    }

    /**
     * @return string
     */
    public function getGclid()
    {
        return $this->gclid;
    }

    /**
     * @param $gclid
     *
     * @return $this
     */
    public function setGclid($gclid)
    {
        $this->gclid = $gclid;

        return $this;
    }

    /**
     * @return FormUrl
     */
    public function getFormUrl()
    {
        return $this->form_url;
    }

    /**
     * @param $formurl
     *
     * @return $this
     */
    public function setFormUrl($formurl)
    {
        $this->form_url = $formurl;

        return $this;
    }

    /**
     * @return EnvoiProduit
     */
    public function getAllumage()
    {
        return $this->allumage;
    }

    /**
     * @param EnvoiProduit|null $allumage
     *
     * @return $this
     */
    public function setAllumage($allumage)
    {
        if ($allumage instanceof EnvoiProduit) {
            $allumage->setAllumageConsultation($this);
        }
        $this->allumage = $allumage;

        return $this;
    }

    /**
     * @param $montant
     *
     * @return $this
     */
    public function setMontantEncaisse($montant)
    {
        if (is_numeric($montant)) {
            $this->montantEncaisse = $montant * 100;
        }

        return $this;
    }

    /**
     * Get montant_encaisse.
     *
     * @return int
     */
    public function getMontantEncaisse()
    {
        return $this->montantEncaisse / 100;
    }

    /**
     * @return int
     */
    public function getMontantPlanifie()
    {
        $somme = 0;
        foreach ($this->getEncaissements() as $encaissement) {
            if ($encaissement->getEtat() == Encaissement::PLANNED || $encaissement->getEtat() == Encaissement::DONE) {
                $somme = $somme + $encaissement->getMontant();
            }
        }

        return $somme;
    }

    public function getMontantImpaye(\DateTime $beginDate = null, \DateTime $endDate = null)
    {
        $montant = $this->getTarification()?$this->getTarification()->getMontantTotal():0;

        foreach ($this->getEncaissements() as $encaissement) {
            if (
                ($encaissement->getEtat() == Encaissement::DONE OR $encaissement->getEtat() == Encaissement::CANCELLED OR $encaissement->getEtat() == Encaissement::REFUNDED)
                && (isset($beginDate) ? $encaissement->getDate() >= $beginDate : true)
                && (isset($endDate) ? $encaissement->getDate() < $endDate : true)
                && $encaissement->getDate()->format('Y-m-d') == $encaissement->getConsultation()->getDateConsultation()->format('Y-m-d')
            ) {
                $montant -= $encaissement->getMontant();
            }
        }

        return $montant;
    }

    /**
     * isMontantEncaissementsValid
     * Vérification de la correspondance entre somme des encaissements et montant de la consultation.
     */
    public function isMontantEncaissementsValid()
    {
        if(is_object($this->tarification)){
            $planned = round($this->getMontantPlanifie(), 2);
            $amount = round($this->getTarification()->getMontantTotal(), 2);
            if($planned != $amount){
                $this->warnings[] = [
                    'style' => 'warning',
                    'msg' => 'La somme des encaissements faits et prévus ne correspond pas au montant de la consultation.',
                ];

                return false;
            }

            return true;
        }
    }

    /**
     * validationSource.
     *
     * @param \KGC\RdvBundle\Entity\ExecutionContextInterface $context
     * @Assert\Callback(groups={"ajout"})
     */
    public function validationSource(ExecutionContextInterface $context)
    {
        if ($this->dateConsultation >= UpgradeDate::getDate('url_source')) {
            if ($this->support->getIdcode() != Support::SUIVI_CLIENT) {
                if ($this->getWebsite()->isHasSource() && ($this->getSource() === null || $this->getFormUrl() === null)) {
                    $context->buildViolation('Pour ce site web, vous devez renseigner la source et lʼurl du formulaire.')
                        ->addViolation();
                }
            }
        }
    }

    /**
     * validationMontantEncaissements
     * Contrainte de validation de la correspondance entre somme des encaissements et montant de la consultation.
     *
     * @param \KGC\RdvBundle\Entity\ExecutionContextInterface $context
     * @Assert\Callback(groups={"facturation"})
     */
    public function validationMontantEncaissements(ExecutionContextInterface $context)
    {
        if (!$this->isMontantEncaissementsValid() && !in_array($this->getClassement()->getIdcode(), [Dossier::ABANDON, Dossier::LITIGE])) {
            $context->buildViolation('La somme des encaissements faits et prévus ne correspond pas au montant de la consultation ; mais la fiche nʼest pas classée en « Abandon » ou en « Litige ». Vous devez modifier la facturation afin de prévoir le montant manquant ou classer la fiche.')
                ->atPath('encaissements')
                ->addViolation();
        }
    }

    /**
     * @param null $proprio
     *
     * @return $this
     *
     * réinitialise la consult, on garde les coordonnées.
     */
    public function resetRdv($proprio = null)
    {
        $this
            ->resetId()
            ->setIdtransactionmyastro(null)
            ->setDateContact(new \DateTime())
            ->setDateConsultation(null)
            ->setEtat(null)
            ->setSupport(null)
            ->setSecurisation(self::SECU_PENDING)
            ->setMiserelation(null)
            ->setPriseencharge(null)
            ->setConsultation(null)
            ->setPaiement(null)
            ->setCloture(null)
            ->setNotesLibres(null)
            ->setTarification(null)
            ->setAllumage(null)
            ->setNewCardHash(null)
            ->setNewCardHashCreatedAt(null);

        if ($authorization = $this->getPreAuthorization()) {
            // remove pre-auth only if already used
            if ($authorization->getCapturePayment()) {
                $this->setPreAuthorization(null);
                $this->setTpe(null);
            } else { // if we keep unused pre-auth, we considered securisation as done
                $this->setSecurisation(self::SECU_DONE);
            }
        }

        $this->historique = new ArrayCollection();
        $this->encaissements = new ArrayCollection();
        $this->etiquettes = new ArrayCollection();
        $this->envoisProduits = new ArrayCollection();

        if ($proprio instanceof Utilisateur) {
            $this->setProprio($proprio);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function is10MIN()
    {
        if (isset($this->tarification)) {
            if ($this->tarification->getTemps() <= self::RDV_FREE_TIME_LIMIT && $this->tarification->getMontantTotal() == 0) {
                return true;
            }
        }

        return false;
    }

    public function getDateConsultationES()
    {
        return date_format($this->dateConsultation, 'Y-m-d');
    }

    public function getDateContactES()
    {
        return date_format($this->dateContact, 'Y-m-d');
    }

    public function getDateReminderES()
    {
        $date = $this->getReminderDate();

        return $date ? date_format($date, 'Y-m-d') : null;
    }

    public function getDateLastEncaissementES()
    {
        $encaissement = $this->getLastEncaissement();
        if ($encaissement) {
            return date_format($encaissement->getDate(), 'Y-m-d');
        }

        return null;
    }

    public function getDateNextEncaissementES()
    {
        $encaissement = $this->getNextEncaissement();
        if ($encaissement) {
            return date_format($encaissement->getDate(), 'Y-m-d');
        }

        return null;
    }

    public function supportES()
    {
        return $this->support ? $this->support->getLibelle() : null;
    }

    public function tpeES()
    {
        return $this->tpe ? $this->tpe->getLibelle() : null;
    }

    public function voyantES()
    {
        return $this->voyant ? $this->voyant->getNom() : null;
    }

    public function codepromoES()
    {
        return $this->codepromo ? $this->codepromo->getCode() : null;
    }

    public function consultantES()
    {
        return $this->consultant ? $this->consultant->getUsername() : null;
    }

    public function websiteES()
    {
        return $this->website ? $this->website->getLibelle() : null;
    }

    public function formUrlES()
    {
        return $this->form_url ? $this->form_url->getLabel() : null;
    }

    public function etatES()
    {
        return $this->etat ? $this->etat->getLibelle() : null;
    }

    public function sourceES()
    {
        return $this->source ? $this->source->getLabel() : null;
    }

    public function classementES()
    {
        return $this->classement ? $this->classement->getLibelle() : null;
    }

    public function numtel1ES()
    {
        if ($this->numtel1) {
            $n = preg_replace('/[^a-z0-9]+/i', '', $this->numtel1);

            return $n;
        }

        return;
    }

    public function numtel2ES()
    {
        if ($this->numtel2) {
            $n = preg_replace('/[^a-z0-9]+/i', '', $this->numtel2);

            return $n;
        }

        return;
    }

    public function etiquettesES()
    {
        $etiquettes = $this->etiquettes;
        if (!empty($etiquettes)) {
            return $etiquettes->toArray();
        }
        return [];
    }

    public function forfaitsES()
    {
        if($this->client){
            $forfaits = $this->client->getForfaits();
            if (!empty($forfaits)) {
                $forfaits = $forfaits->map(function ($f) {
                    return $f->getNom()->getLabel();
                }, $forfaits);

                return $forfaits->toArray();
            }
        }
        return [];
    }

    public function cbsES()
    {
        $cbs = $this->cartebancaires;
        $aCb = [];
        foreach ($cbs as $cb) {
            if (!in_array($cb->getNumero(), $aCb)) {
                $aCb[] = $cb->getNumero();
            }
        }
        return implode('-', $aCb);
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersistDefinitions()
    {
        $this->dateContact = new \DateTime();
    }

    /**
     * @return string
     */
    public function getBatchStatus()
    {
        return $this->batchStatus;
    }

    /**
     * @param string $batchStatus
     */
    public function setBatchStatus($batchStatus)
    {
        $this->batchStatus = $batchStatus;
    }

    /**
     * @return string
     */
    public function getBatchEndDate()
    {
        return $this->batchEndDate;
    }

    /**
     * @param string $batchEndDate
     */
    public function setBatchEndDate($batchEndDate)
    {
        $this->batchEndDate = $batchEndDate;
    }

    public function isConfirmedSecurisationEditable()
    {
        return !($this->getSecurisation() == self::SECU_DONE && $this->getTpe() !== null && $this->getTpe()->getPaymentGateway() !== null);
    }

    /**
     * Set preAuthorization
     *
     * @param \KGC\PaymentBundle\Entity\Authorization $preAuthorization
     *
     * @return RDV
     */
    public function setPreAuthorization(\KGC\PaymentBundle\Entity\Authorization $preAuthorization = null)
    {
        $this->preAuthorization = $preAuthorization;

        return $this;
    }

    /**
     * Get preAuthorization
     *
     * @return \KGC\PaymentBundle\Entity\Authorization
     */
    public function getPreAuthorization()
    {
        return $this->preAuthorization;
    }

    /**
     * Set newCardHash
     *
     * @param string $newCardHash
     *
     * @return RDV
     */
    public function setNewCardHash($newCardHash)
    {
        $this->newCardHash = $newCardHash;

        return $this;
    }

    /**
     * Get newCardHash
     *
     * @return string
     */
    public function getNewCardHash()
    {
        return $this->newCardHash;
    }

    /**
     * Set newCardHashCreatedAt
     *
     * @param \DateTime $newCardHashCreatedAt
     *
     * @return RDV
     */
    public function setNewCardHashCreatedAt($newCardHashCreatedAt)
    {
        $this->newCardHashCreatedAt = $newCardHashCreatedAt;

        return $this;
    }

    /**
     * Get newCardHashCreatedAt
     *
     * @return \DateTime
     */
    public function getNewCardHashCreatedAt()
    {
        return $this->newCardHashCreatedAt;
    }

    public function generateNewCardHash()
    {
        return $this
            ->setNewCardHash(sha1(uniqid(microtime())))
            ->setNewCardHashCreatedAt(new \DateTime);
    }

    public function getConsultationTime()
    {
        return $this->getTarification() ? $this->tarification->getTemps() : 0;
    }

    public function getMontantTotal()
    {
        return $this->getTarification() ? $this->getTarification()->getMontantTotal() : 0;
    }

    /**
     * Set spouseName
     *
     * @param string $spouseName
     *
     * @return RDV
     */
    public function setSpouseName($spouseName)
    {
        $this->spouseName = $spouseName;

        return $this;
    }

    /**
     * Get spouseName
     *
     * @return string
     */
    public function getSpouseName()
    {
        return $this->spouseName;
    }

    /**
     * Set spouseSign
     *
     * @param string $spouseSign
     *
     * @return RDV
     */
    public function setSpouseSign($spouseSign)
    {
        $this->spouseSign = $spouseSign;

        return $this;
    }

    /**
     * Get spouseSign
     *
     * @return string
     */
    public function getSpouseSign()
    {
        return $this->spouseSign;
    }

    /**
     * Set spouseBirthday
     *
     * @param \DateTime $spouseBirthday
     *
     * @return RDV
     */
    public function setSpouseBirthday($spouseBirthday)
    {
        $this->spouseBirthday = $spouseBirthday;

        return $this;
    }

    /**
     * Get spouseBirthday
     *
     * @return \DateTime
     */
    public function getSpouseBirthday()
    {
        return $this->spouseBirthday;
    }

    /**
     * Set questionCode
     *
     * @param string $questionCode
     *
     * @return RDV
     */
    public function setQuestionCode($questionCode)
    {
        $this->questionCode = $questionCode;

        return $this;
    }

    /**
     * Get questionCode
     *
     * @return string
     */
    public function getQuestionCode()
    {
        return $this->questionCode;
    }

    /**
     * Set questionText
     *
     * @param string $questionText
     *
     * @return RDV
     */
    public function setQuestionText($questionText)
    {
        $this->questionText = $questionText;

        return $this;
    }

    /**
     * Get questionText
     *
     * @return string
     */
    public function getQuestionText()
    {
        return $this->questionText;
    }

    /**
     * Set questionSubject
     *
     * @param string $questionSubject
     *
     * @return RDV
     */
    public function setQuestionSubject($questionSubject)
    {
        $this->questionSubject = $questionSubject;

        return $this;
    }

    /**
     * Get questionSubject
     *
     * @return string
     */
    public function getQuestionSubject()
    {
        return $this->questionSubject;
    }

    /**
     * Set questionContent
     *
     * @param string $questionContent
     *
     * @return RDV
     */
    public function setQuestionContent($questionContent)
    {
        $this->questionContent = $questionContent;

        return $this;
    }

    /**
     * Get questionContent
     *
     * @return string
     */
    public function getQuestionContent()
    {
        return $this->questionContent;
    }

    /**
     * Set prospect
     *
     * @param LandingUser $prospect
     *
     * @return RDV
     */
    public function setProspect($prospect)
    {
        $this->prospect = $prospect;

        return $this;
    }

    /**
     * Get prospect
     *
     * @return LandingUser
     */
    public function getProspect()
    {
        return $this->prospect;
    }

    public function getPercentPaid(){
        $percent = 0;
        if(is_null($this->cloture)){
            if($this->montantEncaisse != 0){
                $percent = $this->getMontantTotal()==0 ? 100 : ($this->getMontantEncaisse() ? round($this->getMontantEncaisse() * 100 / $this->getMontantTotal(), 2) : 0);
            }
        }else{
            $percent = 100;
        }
        return $percent;
    }

    public function getRestantDu() {
        return $this->getMontantTotal() - $this->getMontantEncaisse();
    }


    /**
     * Get state
     *
     * @return RDV
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set state
     *
     * @param RdvState $state
     *
     * @return RDV
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Set dateState
     *
     * @param \DateTime $dateState
     *
     * @return RDV
     */
    public function setDateState($dateState)
    {
        $this->dateState = $dateState;

        return $this;
    }

    /**
     * Get dateState
     *
     * @return \DateTime
     */
    public function getDateState()
    {
        return $this->dateState;
    }

    public function getIdProspect() {
        return '';
    }

    public function setIdProspect($id) {
        return $this;
    }
}
