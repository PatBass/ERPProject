<?php

namespace KGC\RdvBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use KGC\ClientBundle\Entity\Option;

/**
 * Class ForfaitTarification.
 *
 * @ORM\Table(name="forfait_tarification", uniqueConstraints={
 *      @ORM\UniqueConstraint(name="forfait_tarification_idx", columns={"codeTarification_id", "forfait_id"})
 *  })
 * @ORM\Entity()
 */
class ForfaitTarification
{
    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var CodeTarification
     *
     * @ORM\ManyToOne(targetEntity="CodeTarification", inversedBy="forfaitTarification")
     * @ORM\JoinColumn(referencedColumnName="cdt_id")
     */
    protected $codeTarification;

    /**
     * @var Option
     *
     * @ORM\ManyToOne(targetEntity="\KGC\ClientBundle\Entity\Option")
     */
    protected $forfait;

    /**
     * @var float
     *
     * @ORM\Column(type="decimal", precision=6, scale=2, nullable=false)
     */
    protected $price;

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
     * @return mixed
     */
    public function getCodeTarification()
    {
        return $this->codeTarification;
    }

    /**
     * @param mixed $codeTarification
     */
    public function setCodeTarification($codeTarification)
    {
        $this->codeTarification = $codeTarification;
    }

    /**
     * @return mixed
     */
    public function getForfait()
    {
        return $this->forfait;
    }

    /**
     * @param mixed $forfait
     */
    public function setForfait($forfait)
    {
        $this->forfait = $forfait;
    }

    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param mixed $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }
}
