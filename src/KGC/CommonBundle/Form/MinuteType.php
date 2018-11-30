<?php
// src/KGC/CommonBundle/Form/Extension/MinuteType.php

namespace KGC\CommonBundle\Form;

use Symfony\Component\Form\AbstractType;

/**
 * MinuteType.
 *
 * @category Form
 *
 * @author Laurene Dourdin <2aurene@gmail.com>
 */
class MinuteType extends AbstractType
{
    
    /**
     * @return string
     */
    public function getParent()
    {
        return 'number';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_minute';
    }
}
