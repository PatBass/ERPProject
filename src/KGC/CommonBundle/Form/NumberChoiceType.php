<?php

// src/KGC/CommonBundle/Form/Extension/NumberChoiceType.php


namespace KGC\CommonBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * NumberChoiceType.
 *
 * @category Form
 *
 * @author Laurene Dourdin <2aurene@gmail.com>
 */
class NumberChoiceType extends AbstractType
{
    public $choices = array();

    public function __construct($min = 0, $max = 20, $increment = 1)
    {
        $this->choices = $this->buildNumberList($min, $max, $increment);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'choices' => $this->choices,
        ));
    }

    private function buildNumberList($min, $max, $increment = 1)
    {
        $choicelist = array();
        for ($i = $min; $i <= $max; $i = $i + $increment) {
            $choicelist[$i] = $i;
        }

        return $choicelist;
    }

    public function getParent()
    {
        return 'choice';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_numberChoice';
    }
}
