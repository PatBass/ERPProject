<?php

namespace KGC\ChatBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ChatWebsiteType.
 */
class ChatPromotionFilterType extends ChatWebsiteType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    /*public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::builder($builder, $options);
    }*/

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_ChatBundle_promotion_filter';
    }
}
