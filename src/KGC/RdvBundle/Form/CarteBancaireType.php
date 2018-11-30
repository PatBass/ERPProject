<?php

// src/KGC/RdvBundle/Form/CarteBancaireType.php


namespace KGC\RdvBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Constructeur de formulaire pour entité Carte bancaire.
 *
 * @category Form
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class CarteBancaireType extends AbstractType
{

    protected $cbMasked = false;
    protected $decrypt = false;
    protected $forceHide = false;

    public function __construct($cbMasked = false, $decrypt = false, $forceHide = false)
    {
        $this->cbMasked = $cbMasked;
        $this->decrypt = $decrypt;
        $this->forceHide = $forceHide;
    }
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
//        $this->forceHide = true;
        $builder
            ->add('nom', 'hidden', array(
                'empty_data' => 'auto',
            ))
            ->add('maskedNumber', 'text', [
                'required' => false,
                'attr' => array(
                    'id' => 'cbMasked',
                    'hide' => ((!$this->cbMasked) ? 'hide' : ''),
                    'decrypt' => $this->decrypt,
                    'forceHide' => $this->forceHide

                )])
            ->add('expiration', 'text', array(
                'attr' => array(
                    'class' => 'input-mask-expiration',
                ),
            ))
            ->add('cryptogramme', 'text')
        ;
        if($this->decrypt) {
            $builder->add('numero', 'text', [
                'attr' => array(
                    'class' => 'js-check-luhn',
                    'hide' => (($this->cbMasked) ? 'hide' : ''),
                )
            ]);
        }
//        else if($this->forceHide) {
        else {
            $builder->add('numero', 'text', [
                'attr' => array(
                    'class' => 'js-check-luhn',
                    'hide' => (($this->cbMasked) ? 'hide' : '')
                )
            ]);
        }
//    else {
//            $builder->add('numero', 'text', [
//                'data' => '',
//                'attr' => array(
//                    'class' => 'js-check-luhn',
//                    'hide' => (($this->cbMasked) ? 'hide' : ''),
//                )
//            ]);
//        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\RdvBundle\Entity\CarteBancaire',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_RdvBundle_cartebancaire';
    }
}
