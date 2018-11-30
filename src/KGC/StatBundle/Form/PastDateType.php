<?php
// src/KGC/StatBundle/Form/PastDateType.php

namespace KGC\StatBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class PastDateType.
 */
class PastDateType extends AbstractType
{
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'widget' => 'single_text',
            'format' => 'dd/MM/yyyy',
            'date-picker' => true,
            'empty_data' => date('d/m/Y'),
            'limit-size' => true,
            'data' => new \Datetime('today 00:00'),
            'attr' => array(
                'class' => 'submit-onchange',
            ),
        ]);
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return 'date';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_pastdate';
    }
}
