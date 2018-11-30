<?php
/**
 * Created by PhpStorm.
 * User: niko
 * Date: 17/08/2016
 * Time: 11:43
 */

namespace KGC\UserBundle\Elastic\Model;

use JMS\Serializer\Annotation as JMS;
use \Doctrine\Common\Collections\ArrayCollection;


class ProspectSearch
{
    /**
     * Default elastic order.
     */
    const ORDER_BY_DEFAULT = 'createdAt';

    /**
     * Default elastic sort direction.
     */
    const SORT_DIRECTION_DEFAULT = 'desc';

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
     * @var int
     * @JMS\Type("integer")
     */
    protected $stateEn;

    /**
     * @var int
     * @JMS\Type("integer")
     */
    protected $websiteEn;

    /**
     * @var int
     * @JMS\Type("integer")
     */
    protected $sourceEn;

    /**
     * @var int
     * @JMS\Type("integer")
     */
    protected $supportEn;

    /**
     * @var int
     * @JMS\Type("integer")
     */
    protected $urlEn;
    /**
     * @var int
     * @JMS\Type("integer")
     */
    protected $codePromoEn;

    /**
     * @var \Datetime
     * @JMS\Type("DateTime<'d-m-Y'>")
     */
    protected $dateBegin;

    /**
     * @var \Datetime
     * @JMS\Type("DateTime<'d-m-Y'>")
     */
    protected $birthdate;

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
    protected $perPage = 10;

    public function __construct()
    {
        $this->orderBy = self::ORDER_BY_DEFAULT;
        $this->sortDirection = self::SORT_DIRECTION_DEFAULT;
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
     * @return int
     */
    public function getStateEn()
    {
        return $this->stateEn;
    }

    /**
     * @param int $stateEn
     */
    public function setStateEn($stateEn)
    {
        $this->stateEn = $stateEn;
    }

    /**
     * @return int
     */
    public function getWebsiteEn()
    {
        return $this->websiteEn;
    }

    /**
     * @param int $websiteEn
     */
    public function setWebsiteEn($websiteEn)
    {
        $this->websiteEn = $websiteEn;
    }

    /**
     * @return int
     */
    public function getSourceEn()
    {
        return $this->sourceEn;
    }

    /**
     * @param int $sourceEn
     */
    public function setSourceEn($sourceEn)
    {
        $this->sourceEn = $sourceEn;
    }

    /**
     * @return int
     */
    public function getSupportEn()
    {
        return $this->supportEn;
    }

    /**
     * @param int $supportEn
     */
    public function setSupportEn($supportEn)
    {
        $this->supportEn = $supportEn;
    }

    /**
     * @return int
     */
    public function getUrlEn()
    {
        return $this->urlEn;
    }

    /**
     * @param int $urlEn
     */
    public function setUrlEn($urlEn)
    {
        $this->urlEn = $urlEn;
    }

    /**
     * @return int
     */
    public function getCodePromoEn()
    {
        return $this->codePromoEn;
    }

    /**
     * @param int $codePromoEn
     */
    public function setCodePromoEn($codePromoEn)
    {
        $this->codePromoEn = $codePromoEn;
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
    
    public function getPerPage()
    {
        return $this->perPage;
    }

    public function setPerPage($perPage=null)
    {
        if($perPage != null){
            $this->perPage = $perPage;
        }

        return $this;
    }
}