<?php

// src/KGC/ClientBundle/Entity/DrawType.php


namespace KGC\ClientBundle\Form;

use Doctrine\ORM\EntityRepository;
use KGC\ClientBundle\Entity\Option;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class DrawType extends AbstractType
{
    /**
     * @return string
     */
    protected function getEntityClass()
    {
        return 'KGCClientBundle:Option';
    }

    /**
     * @return string
     */
    protected function getDisplayProperty()
    {
        return 'label';
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('deck', 'entity', [
                'class' => $this->getEntityClass(),
                'property' => $this->getDisplayProperty(),
                'empty_value' => 'Jeu de cartes',
                'label' => false,
                'required' => false,
                'query_builder' => function (EntityRepository $er) {
                    return $er->findAllByTypeQB(Option::TYPE_DRAW_DECK);
                },
                'required' => true,
            ])
            ->add('card', 'entity', [
                'class' => $this->getEntityClass(),
                'property' => $this->getDisplayProperty(),
                'empty_value' => 'Carte tirée',
                'label' => false,
                'required' => false,
                'query_builder' => function (EntityRepository $er) {
                    return $er->findAllByTypeQB(Option::TYPE_DRAW_CARD);
                },
                'js_dependant_select' => [
                    'depends_on' => 'deck',
                    'reference_value' => ['inheritanceId'],
                ],
                'required' => true,
            ])
            ->add('supprimer', 'button', array(
                'label' => '<i class="icon-trash bigger-120"></i>',
                'attr' => array(
                    'class' => 'collection_del btn-danger btn-xs',
                ),
            ))
            ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\ClientBundle\Entity\Draw',
        ));
    }

    public function getName()
    {
        return 'kgc_clientbundle_draw';
    }
}
