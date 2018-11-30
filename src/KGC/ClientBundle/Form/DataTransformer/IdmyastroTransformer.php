<?php

// src/KGC/ClientBundle/Form/DataTransformer/IdmyastroTransformer.php
namespace KGC\ClientBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class IdmyastroTransformer implements DataTransformerInterface
{
    /**
     * Transforms idmyastro.
     *
     * @param string $idmyastro
     *
     * @return string
     */
    public function transform($idmyastro)
    {
        return $idmyastro;
    }

    /**
     * Transforme le codemyastro (code promo mailing) en idmyastro décodé ( de base 32 à base 10).
     *
     * @param string $codemyastro
     *
     * @return string
     */
    public function reverseTransform($codemyastro)
    {
        $convert = false;
        if ($codemyastro !== null) {
            if (preg_match('[a-z]', $codemyastro)) {
                $convert = true;
            }
            if (strlen($codemyastro) <= 5) {
                $convert = true;
            }
        }

        return $convert ? base_convert($codemyastro, 32, 10) : $codemyastro;
    }
}
