<?php

// src/KGC/ClientBundle/Form/WebsiteType.php


namespace KGC\ClientBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class WebsiteType.
 *
 * @category Form
 */
class WebsiteType extends AbstractType
{
    /**
     * @var array
     */
    protected $availableGateways;

    public function __construct(array $availableGateways = [])
    {
        $this->availableGateways = $availableGateways;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('libelle', 'text', array(
                'label' => 'Libellé',
                'required' => true,
            ))
            ->add('url', 'text', array(
                'label' => 'Url',
                'required' => true,
            ))
            ->add('phone', 'text', array(
                'label' => 'Téléphone',
                'required' => true,
            ))
            ->add('file', 'file', [
                'attr' => ['class' => 'js-file ace_file_input'],
                'required' => false,
            ])
            ->add('enabled', 'checkbox', [
                'required' => false,
                'switch_style' => 5,
            ])
            ->add('hasSource', 'checkbox', [
                'required' => false,
                'switch_style' => 5,
            ])
        ;

        if (!empty($this->availableGateways)) {
            $builder->add('paymentGateway', 'choice', ['choices' => $this->availableGateways]);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\Bundle\SharedBundle\Entity\Website',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_ClientBundle_website';
    }
}
