<?php

// src/KGC/RdvBundle/Form/DataTransformer/MontantTransformer.php
namespace KGC\RdvBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class MontantTransformer implements DataTransformerInterface
{
    /**
     * restitue montant.
     *
     * @param int $montant
     *
     * @return float
     */
    public function transform($montant)
    {
        return $montant / 100;
    }

    /**
     * Transforme prix à virgule en nombre entiers pour stockage optimisé ds la base.
     *
     * @param float $montant
     *
     * @return int
     */
    public function reverseTransform($montant)
    {
        return $montant * 100;
    }
}
