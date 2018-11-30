<?php

namespace KGC\ClientBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class SourceType.
 *
 * @category Form
 */
class SourceType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('code', 'text', array(
                'label' => 'Code',
                'required' => true,
            ))
            ->add('label', 'text', array(
                'label' => 'Label',
                'required' => true,
            ))
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\Bundle\SharedBundle\Entity\Source',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_ClientBundle_source';
    }
}
