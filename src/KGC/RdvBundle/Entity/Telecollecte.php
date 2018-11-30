<?php
// src/KGC/RdvBundle/Entity/Telecollecte.php

namespace KGC\RdvBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Entité Télécollecte
 *
 * @category Entity
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 *
 * @ORM\Table(name="telecollecte")
 * @ORM\Entity(repositoryClass="KGC\RdvBundle\Repository\TelecollecteRepository")
 * @UniqueEntity(
 *     fields={"date", "tpe"},
 *     message="Une télécollecte existe déjà pour cette date et ce TPE. Vous pouvez la modifier depuis le tableau de pointage."
 * )
 */
class Telecollecte
{
    /**
     * @var int
     * @ORM\Column(name="tlc_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \DateTime
     * @ORM\Column(name="tlc_date", type="date")
     */
    protected $date;
    
    /**
     * @var tpe
     * @ORM\ManyToOne(targetEntity="TPE")
     * @ORM\JoinColumn(name="tlc_tpe", referencedColumnName="tpe_id")
     */
    protected $tpe;
    
    /**
     * @var int
     * @ORM\Column(name="tlc_montant_un", type="integer")
     */
    protected $amountOne;
    
    /**
     * @var int
     * @ORM\Column(name="tlc_montant_deux", type="integer")
     */
    protected $amountTwo;
    
    /**
     * @var int
     * @ORM\Column(name="tlc_montant_trois", type="integer")
     */
    protected $amountThree;
    
    /**
     * @var int
     * @ORM\Column(name="tlc_total", type="integer")
     */
    protected $total;

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
     * @return $this
     */
    public function setDate($date)
    {
        if($date !== null){
            $this->date = $date;
        }

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
     * Set amountOne.
     *
     * @param int $montant
     * @return $this
     */
    public function setAmountOne($montant)
    {
        $this->amountOne = $montant * 100;
        $this->updateTotal();

        return $this;
    }

    /**
     * Get amountOne.
     *
     * @return int
     */
    public function getAmountOne()
    {
        return $this->amountOne / 100;
    }
    
    /**
     * Set amountTwo.
     *
     * @param int $montant
     *
     * @return $this
     */
    public function setAmountTwo($montant)
    {
        $this->amountTwo = $montant * 100;
        $this->updateTotal();

        return $this;
    }

    /**
     * Get amountTwo.
     *
     * @return int
     */
    public function getAmountTwo()
    {
        return $this->amountTwo / 100;
    }
    
    /**
     * Set amountThree.
     *
     * @param int $montant
     *
     * @return $this
     */
    public function setAmountThree($montant)
    {
        $this->amountThree = $montant * 100;
        $this->updateTotal();

        return $this;
    }

    /**
     * Get montant.
     *
     * @return int
     */
    public function getAmountThree()
    {
        return $this->amountThree / 100;
    }
    
    /**
     * Set total.
     *
     * @param int $total
     *
     * @return $this
     */
    public function setTotal($total)
    {
        $this->total = $total * 100;

        return $this;
    }

    /**
     * Get montant.
     *
     * @return int
     */
    public function getTotal()
    {
        return $this->total / 100;
    }
    
    public function updateTotal()
    {
        $this->setTotal($this->getAmountOne() + $this->getAmountTwo() + $this->getAmountThree());
    }
}
