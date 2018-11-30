<?php

namespace KGC\RdvBundle\Elastic\Model;

use KGC\Bundle\SharedBundle\Entity\Website;
use KGC\RdvBundle\Entity\FormUrl;
use KGC\RdvBundle\Entity\CodePromo;
use KGC\RdvBundle\Entity\Support;
use KGC\RdvBundle\Entity\TPE;
use KGC\UserBundle\Entity\Utilisateur;
use KGC\UserBundle\Entity\Voyant;
use JMS\Serializer\Annotation as JMS;
use \Doctrine\Common\Collections\ArrayCollection;

class RdvSearch
{
    /**
     * Default elastic order.
     */
    const ORDER_BY_DEFAULT = '_score';

    /**
     * Default elastic sort direction.
     */
    const SORT_DIRECTION_DEFAULT = 'desc';

    /**
     * Date type names
     */
    const DATE_CONSULTATION = 1;
    const DATE_FOLLOW = 2;
    const DATE_LAST_RECEIPT = 3;
    const DATE_NEXT_RECEIPT = 4;

    /**
     * @var string
     *
     * @JMS\Type("string")
     */
    protected $id;

    /**
     * @var string
     *
     * @JMS\Type("string")
     */
    protected $idAstro;

    /**
     * @var string
     *
     * @JMS\Type("string")
     */
    protected $name;

    /**
     * @var string
     * @JMS\Type("string")
     */
    protected $phones;

    /**
     * @var string
     * @JMS\Type("string")
     */
    protected $mail;

    /**
     * @var \Datetime
     * @JMS\Type("DateTime<'d-m-Y'>")
     */
    protected $dateBegin;

    /**
     * @var \Datetime
     * @JMS\Type("DateTime<'d-m-Y'>")
     */
    protected $dateEnd;

    /**
     * @var int
     * @JMS\Type("string")
     */
    protected $dateType;

    /**
     * @var \Datetime
     * @JMS\Type("DateTime<'d-m-Y'>")
     */
    protected $birthdate;

    /**
     * @var ArrayCollection
     * @JMS\Type("ArrayCollection")
     */
    protected $consultants;

    /**
     * @var ArrayCollection
     * @JMS\Type("ArrayCollection")
     */
    protected $psychics;

    /**
     * @var int
     * @JMS\Type("string")
     */
    protected $timeMin;

    /**
     * @var int
     * @JMS\Type("string")
     */
    protected $timeMax;

    /**
     * @var int
     * @JMS\Type("string")
     */
    protected $amountMin;

    /**
     * @var int
     * @JMS\Type("string")
     */
    protected $amountMax;

    /**
     * @var ArrayCollection
     * @JMS\Type("ArrayCollection")
     */
    protected $supports;

    /**
     * @var ArrayCollection
     * @JMS\Type("ArrayCollection")
     */
    protected $codepromos;

    /**
     * @var ArrayCollection
     * @JMS\Type("ArrayCollection")
     */
    protected $tpes;

    /**
     * @var ArrayCollection
     * @JMS\Type("ArrayCollection")
     */
    protected $websites;

    /**
     * @var ArrayCollection
     * @JMS\Type("ArrayCollection")
     */
    protected $form_urls;

    /**
     * @var string
     *
     * @JMS\Type("string")
     */
    protected $card;

    /**
     * @var ArrayCollection
     * @JMS\Type("ArrayCollection")
     */
    protected $states;

    /**
     * @var ArrayCollection
     * @JMS\Type("ArrayCollection")
     */
    protected $sources;

    /**
     * @var ArrayCollection
     * @JMS\Type("ArrayCollection")
     */
    protected $classements;

    /**
     * @var ArrayCollection
     * @JMS\Type("ArrayCollection")
     */
    protected $forfaits;

    /**
     * @var ArrayCollection
     * @JMS\Type("ArrayCollection")
     */
    protected $tags;

    /**
     * @var \Datetime
     * @JMS\Type("DateTime<'d-m-Y'>")
     */
    protected $dateFollowBegin;

    /**
     * @var \Datetime
     * @JMS\Type("DateTime<'d-m-Y'>")
     */
    protected $dateFollowEnd;

    /**
     * @var string
     * @JMS\Type("string")
     */
    protected $orderBy;

    /**
     * @var string
     * @JMS\Type("string")
     */
    protected $sortDirection;

    /**
     * @var int
     *
     * @JMS\Type("integer")
     */
    protected $page;

    /**
     * @var int
     *
     * @JMS\Type("integer")
     */
    protected $pageRange;

    public function __construct()
    {
        $this->orderBy          = self::ORDER_BY_DEFAULT;
        $this->sortDirection    = self::SORT_DIRECTION_DEFAULT;
        $this->form_urls        = new ArrayCollection();
        $this->websites         = new ArrayCollection();
        $this->sources          = new ArrayCollection();
        $this->supports         = new ArrayCollection();
        $this->codepromos       = new ArrayCollection();
        $this->consultants      = new ArrayCollection();
        $this->psychics         = new ArrayCollection();
        $this->tpes             = new ArrayCollection();
        $this->states           = new ArrayCollection();
        $this->classements      = new ArrayCollection();
        $this->forfaits         = new ArrayCollection();
        $this->tags             = new ArrayCollection();
    }


    public static function getDateTypeChoices()
    {
        return array(
            self::DATE_CONSULTATION => 'Date de consulation',
            self::DATE_FOLLOW => 'Date de suivi',
            self::DATE_LAST_RECEIPT => 'Date dernier encaissement',
            self::DATE_NEXT_RECEIPT => 'Date prochain encaissement'
        );
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }


    /**
     * @return string
     */
    public function getIdAstro()
    {
        return $this->idAstro;
    }

    /**
     * @param string $idAstro
     */
    public function setIdAstro($idAstro)
    {
        $this->idAstro = $idAstro;
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
    public function getPhones()
    {
        return $this->phones;
    }

    /**
     * @param string $phones
     */
    public function setPhones($phones)
    {
        $this->phones = $phones;
    }

    /**
     * @return string
     */
    public function getMail()
    {
        return $this->mail;
    }

    /**
     * @param string $mail
     */
    public function setMail($mail)
    {
        $this->mail = $mail;
    }

    /**
     * @return \Datetime
     */
    public function getDateBegin()
    {
        return $this->dateBegin;
    }

    /**
     * @param \Datetime $dateBegin
     */
    public function setDateBegin($dateBegin)
    {
        $this->dateBegin = $dateBegin;
    }

    /**
     * @return \Datetime
     */
    public function getDateEnd()
    {
        return $this->dateEnd;
    }

    /**
     * @param \Datetime $dateEnd
     */
    public function setDateEnd($dateEnd)
    {
        $this->dateEnd = $dateEnd;
    }

    /**
     * @return int
     */
    public function getDateType()
    {
        return $this->dateType;
    }

    /**
     * @param int $dateType
     */
    public function setDateType($dateType)
    {
        $this->dateType = $dateType;
    }

    /**
     * @return \Datetime
     */
    public function getBirthdate()
    {
        return $this->birthdate;
    }

    /**
     * @param \Datetime $birthdate
     */
    public function setBirthdate($birthdate)
    {
        $this->birthdate = $birthdate;
    }

    /**
     * @return Consultant
     */
    public function getConsultants()
    {
        return $this->consultants?$this->consultants->toArray():null;
    }

    /**
     * @param Consultant $consultant
     */
    public function addConsultant($consultant)
    {
        if (!$this->consultants->contains($consultant)) {
            $this->consultants->add($consultant);
        }

        return $this;
    }

    /**
     * @param Consultant $consultant
     */
    public function removeConsultant($consultant)
    {
        if ($this->consultants->contains($consultant)) {
            $this->consultants->removeElement($consultant);
        }

        return $this;
    }

    /**
     * @return Array
     */
    public function getPsychics()
    {
        return $this->psychics?$this->psychics->toArray():null;
    }

    /**
     * @param Voyant $psychic
     */
    public function addPsychic($psychic)
    {
        if (!$this->psychics->contains($psychic)) {
            $this->psychics->add($psychic);
        }

        return $this;
    }

    /**
     * @param Voyant $psychic
     */
    public function removePsychic($psychic)
    {
        if ($this->psychics->contains($psychic)) {
            $this->psychics->removeElement($psychic);
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getTimeMin()
    {
        return $this->timeMin;
    }

    /**
     * @param int $timeMin
     */
    public function setTimeMin($timeMin)
    {
        $this->timeMin = $timeMin;
    }

    /**
     * @return int
     */
    public function getTimeMax()
    {
        return $this->timeMax;
    }

    /**
     * @param int $timeMax
     */
    public function setTimeMax($timeMax)
    {
        $this->timeMax = $timeMax;
    }

    /**
     * @return int
     */
    public function getAmountMin()
    {
        return $this->amountMin;
    }

    /**
     * @param int $amountMin
     */
    public function setAmountMin($amountMin)
    {
        $this->amountMin = $amountMin;
    }

    /**
     * @return int
     */
    public function getAmountMax()
    {
        return $this->amountMax;
    }

    /**
     * @param int $amountMax
     */
    public function setAmountMax($amountMax)
    {
        $this->amountMax = $amountMax;
    }

    /**
     * @return Support
     */
    public function getSupports()
    {
        return $this->supports?$this->supports->toArray():null;
    }

    /**
     * @param Support $support
     */
    public function addSupport($support)
    {
        if (!$this->supports->contains($support)) {
            $this->supports->add($support);
        }

        return $this;
    }

    /**
     * @param Support $support
     */
    public function removeSupport($support)
    {
        if ($this->supports->contains($support)) {
            $this->supports->removeElement($support);
        }

        return $this;
    }

    /**
     * @return Codepromo
     */
    public function getCodepromos()
    {
        return $this->codepromos?$this->codepromos->toArray():null;
    }

    /**
     * @param Codepromo $codepromo
     */
    public function addCodepromo($codepromo)
    {
        if (!$this->codepromos->contains($codepromo)) {
            $this->codepromos->add($codepromo);
        }

        return $this;
    }

    /**
     * @param Codepromo $codepromo
     */
    public function removeCodepromo($codepromo)
    {
        if ($this->codepromos->contains($codepromo)) {
            $this->codepromos->removeElement($codepromo);
        }

        return $this;
    }

    /**
     * @return Array
     */
    public function getTpes()
    {
        return ($this->tpes)?$this->tpes->toArray():null;
    }

    /**
     * @param Tpe $tpe
     */
    public function addTpe($tpe)
    {
        if (!$this->tpes->contains($tpe)) {
            $this->tpes->add($tpe);
        }

        return $this;
    }

    /**
     * @param Tpe $tpe
     */
    public function removeTpe($tpe)
    {
        if ($this->tpes->contains($tpe)) {
            $this->tpes->removeElement($tpe);
        }

        return $this;
    }

    /**
     * @return FormUrl
     */
    public function getFormUrls()
    {
        return $this->form_urls?$this->form_urls->toArray():null;
    }

    /**
     * @param FormUrl $form_url
     */
    public function addFormUrl($form_url)
    {
        if (!$this->form_urls->contains($form_url)) {
            $this->form_urls->add($form_url);
        }

        return $this;
    }

    /**
     * @param FormUrl $form_url
     */
    public function removeFormUrl($form_url)
    {
        if ($this->form_urls->contains($form_url)) {
            $this->form_urls->removeElement($form_url);
        }

        return $this;
    }

    /**
     * @return  [Website]
     */
    public function getWebsites()
    {
        return ($this->websites)?$this->websites->toArray():null;
    }

    /**
     * @param Website $website
     */
    public function addWebsite($website)
    {
        if (!$this->websites->contains($website)) {
            $this->websites->add($website);
        }

        return $this;
    }

    /**
     * @param Website $website
     */
    public function removeWebsite($website)
    {
        if ($this->websites->contains($website)) {
            $this->websites->removeElement($website);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getCard()
    {
        return $this->card;
    }

    /**
     * @param string $card
     */
    public function setCard($card)
    {
        $this->card = $card;
    }

    /**
     * @return Array
     */
    public function getStates()
    {
        return $this->states?$this->states->toArray():null;
    }

    /**
     * @param State $state
     */
    public function addState($state)
    {
        if (!$this->states->contains($state)) {
            $this->states->add($state);
        }

        return $this;
    }

    /**
     * @param State $state
     */
    public function removeState($state)
    {
        if ($this->states->contains($state)) {
            $this->states->removeElement($state);
        }

        return $this;
    }

    /**
     * @return Source
     */
    public function getSources()
    {
        return $this->sources?$this->sources->toArray():null;
    }

    /**
     * @param FormUrl $form_url
     */
    public function addSource($source)
    {
        if (!$this->sources->contains($source)) {
            $this->sources->add($source);
        }

        return $this;
    }

    /**
     * @param FormUrl $form_url
     */
    public function removeSource($source)
    {
        if ($this->sources->contains($source)) {
            $this->sources->removeElement($source);
        }

        return $this;
    }

    /**
     * @return Array
     */
    public function getClassements()
    {
        return $this->classements?$this->classements->toArray():null;
    }

    /**
     * @param Classement $classement
     */
    public function addClassement($classement)
    {
        if (!$this->classements->contains($classement)) {
            $this->classements->add($classement);
        }

        return $this;
    }

    /**
     * @param Classement $classement
     */
    public function removeClassement($classement)
    {
        if ($this->classements->contains($classement)) {
            $this->classements->removeElement($classement);
        }

        return $this;
    }

    /**
     * @return Array
     */
    public function getForfaits()
    {
        return ($this->forfaits)?$this->forfaits->toArray():null;
    }

    /**
     * @param Forfait $forfait
     */
    public function addForfait($forfait)
    {
        if (!$this->forfaits->contains($forfait)) {
            $this->forfaits->add($forfait);
        }

        return $this;
    }

    /**
     * @param Forfait $forfait
     */
    public function removeForfait($forfait)
    {
        if ($this->forfaits->contains($forfait)) {
            $this->forfaits->removeElement($forfait);
        }

        return $this;
    }

    /**
     * @return Array
     */
    public function getTags()
    {
        return $this->tags?$this->tags->toArray():null;
    }

    /**
     * @param Tag $tag
     */
    public function addTag($tag)
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    /**
     * @param Tag $tag
     */
    public function removeTag($tag)
    {
        if ($this->tags->contains($tag)) {
            $this->tags->removeElement($tag);
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDateFollowBegin()
    {
        return $this->dateFollowBegin;
    }

    /**
     * @param mixed $dateFollowBegin
     */
    public function setDateFollowBegin($dateFollowBegin)
    {
        $this->dateFollowBegin = $dateFollowBegin;
    }

    /**
     * @return \Datetime
     */
    public function getDateFollowEnd()
    {
        return $this->dateFollowEnd;
    }

    /**
     * @param \Datetime $dateFollowEnd
     */
    public function setDateFollowEnd($dateFollowEnd)
    {
        $this->dateFollowEnd = $dateFollowEnd;
    }

    /**
     * @return string
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }

    /**
     * @param string $orderBy
     */
    public function setOrderBy($orderBy)
    {
        $this->orderBy = $orderBy;
    }

    /**
     * @return string
     */
    public function getSortDirection()
    {
        return $this->sortDirection;
    }

    /**
     * @param string $sortDirection
     */
    public function setSortDirection($sortDirection)
    {
        $this->sortDirection = $sortDirection;
    }

    /**
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param int $page
     */
    public function setPage($page)
    {
        $this->page = $page;
    }

    /**
     * @return int
     */
    public function getPageRange()
    {
        return $this->pageRange;
    }

    /**
     * @param int $pageRange
     */
    public function setPageRange($pageRange)
    {
        $this->pageRange = $pageRange;
    }
}
