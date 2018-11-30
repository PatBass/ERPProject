<?php

namespace KGC\ClientBundle\Form;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use KGC\Bundle\SharedBundle\Form\CreditCardType;
use KGC\Bundle\SharedBundle\Model\CreditCard;

class CardCreateType extends CreditCardType
{
    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults(
                [
                    'data_class' => 'KGC\Bundle\SharedBundle\Model\CreditCard',
                    'validation_groups' => ['CreditCard'],
                    'csrf_protection' => false,
                ]
            );
    }

    public function getName()
    {
        return '';
    }
}