<?php

namespace KGC\ClientBundle\Elastic\Model;

use JMS\Serializer\Annotation as JMS;

class ClientSearch
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
     * @var string
     *
     * @JMS\Type("string")
     */
    protected $name;

    /**
     * @var string
     * @JMS\Type("string")
     */
    protected $mail;

    /**
     * @var \Datetime
     * @JMS\Type("DateTime<'d-m-Y'>")
     */
    protected $birthdate;

    /**
     * @var string
     *
     * @JMS\Type("string")
     */
    protected $origin;

    /**
     * @var string
     *
     * @JMS\Type("string")
     */
    protected $source;

    /**
     * @var string
     *
     * @JMS\Type("string")
     */
    protected $card;

    /**
     * @var string
     *
     * @JMS\Type("string")
     */
    protected $psychic;

    /**
     * @var \Datetime
     * @JMS\Type("DateTime<'d-m-Y'>")
     */
    protected $dateCreationBegin;

    /**
     * @var \Datetime
     * @JMS\Type("DateTime<'d-m-Y'>")
     */
    protected $dateCreationEnd;

    /**
     * @var string
     * @JMS\Type("string")
     */
    protected $formula;

    /**
     * @var string
     * @JMS\Type("string")
     */
    protected $phones;

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
        $this->orderBy = self::ORDER_BY_DEFAULT;
        $this->sortDirection = self::SORT_DIRECTION_DEFAULT;
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
     * @return string
     */
    public function getOrigin()
    {
        return $this->origin;
    }

    /**
     * @param string $origin
     */
    public function setOrigin($origin)
    {
        $this->origin = $origin;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource($source)
    {
        $this->source = $source;
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
     * @return string
     */
    public function getPsychic()
    {
        return $this->psychic;
    }

    /**
     * @param string $psychic
     */
    public function setPsychic($psychic)
    {
        $this->psychic = $psychic;
    }

    /**
     * @return \Datetime
     */
    public function getDateCreationBegin()
    {
        return $this->dateCreationBegin;
    }

    /**
     * @param \Datetime $dateCreationBegin
     */
    public function setDateCreationBegin($dateCreationBegin)
    {
        $this->dateCreationBegin = $dateCreationBegin;
    }

    /**
     * @return \Datetime
     */
    public function getDateCreationEnd()
    {
        return $this->dateCreationEnd;
    }

    /**
     * @param \Datetime $dateCreationEnd
     */
    public function setDateCreationEnd($dateCreationEnd)
    {
        $this->dateCreationEnd = $dateCreationEnd;
    }

    /**
     * @return string
     */
    public function getFormula()
    {
        return $this->formula;
    }

    /**
     * @param string $formula
     */
    public function setFormula($formula)
    {
        $this->formula = $formula;
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

}
