<?php

namespace KGC\RdvBundle\Form;

use KGC\CommonBundle\Form\CommonAbstractType;
use KGC\CommonBundle\Form\DateTimeType;
use KGC\RdvBundle\Entity\Etiquette;
use KGC\RdvBundle\Entity\MoyenPaiement;
use KGC\RdvBundle\Repository\MoyenPaiementRepository;
use KGC\RdvBundle\Repository\TPERepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Constructeur de formulaire d'Etiquette.
 *
 * @category Form
 *
 * @author FAKIR Yahya <yaya.fakir@gmail.com>
 */
class EtiquetteType extends CommonAbstractType
{


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('libelle','text', array('attr'=> array('class'=>'form-control')))
            ->add('desc', 'textarea', array('attr'=> array('class'=>'form-control')))
            ->add('active', 'checkbox', [
                'required' => false,
                'switch_style' => 5,
            ])
            ->add('profils', 'entity', [
                'class' => 'KGCUserBundle:Profil',
                'property' => 'name',
                'multiple' => true,
                'expanded' => true,

            ])
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\RdvBundle\Entity\Etiquette',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_RdvBundle_Etiquette';
    }




}
