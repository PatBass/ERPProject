<?php
// src/KGC/StatBundle/Form/StatDateType.php

namespace KGC\StatBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class StatDateType extends AbstractType
{
    const KEY_DATE_TYPE_CONSULTATION = 'prise_rdv';
    const KEY_DATE_TYPE_HISTORIQUE = 'historique';

    const DATE_TYPE_CONSULTATION = 'Date de prise en charge du rdv';
    const DATE_TYPE_HISTORIQUE = 'Date d\'historique';

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('dateType', 'choice', [
                'label' => 'Date',
                'choices' => [
                    self::KEY_DATE_TYPE_CONSULTATION => self::DATE_TYPE_CONSULTATION,
                    self::KEY_DATE_TYPE_HISTORIQUE => self::DATE_TYPE_HISTORIQUE,
                ],
                'required' => true,
                'data' => isset($options['data']['selected']) ? $options['data']['selected'] : self::KEY_DATE_TYPE_CONSULTATION,
                'attr' => array(
                    'class' => 'chosen-select tag-input-style'
                )
            ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_StatBundle_admin_datescope';
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'mapped' => false
        ));
    }
}
?>