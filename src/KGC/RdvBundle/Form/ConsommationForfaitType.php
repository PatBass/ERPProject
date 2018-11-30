<?php

// src/KGC/RdvBundle/Form/ConsommationForfaitType.php


namespace KGC\RdvBundle\Form;

use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\CommonBundle\Form\MinuteType;
use KGC\RdvBundle\Entity\ConsommationForfait;
use KGC\RdvBundle\Entity\Forfait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Constructeur de formulaire pour entité Adresse.
 *
 * @category Form
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class ConsommationForfaitType extends AbstractType
{
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $client = $this->client;
        $builder
            ->add('forfait', 'entity', [
                'class' => 'KGCRdvBundle:Forfait',
                'property' => 'label',
                'error_bubbling' => true,
                'query_builder' => function ($er) use ($client) {
                    return $er->findAvailableByClientQB($client);
                },
            ])
            ->add('temps', new MinuteType())
            ->add('supprimer', 'button', array(
                'label' => '<i class="icon-trash bigger-120"></i>',
                'attr' => array(
                    'class' => 'collection_del btn-danger btn-xs',
                    'style' => 'margin: 5px;',
                ),
            ))
        ;
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($client) {
            $conso = $event->getData();
            if ($conso instanceof ConsommationForfait) {
                $forfait = $conso->getForfait();
                $form = $event->getForm();
                if ($forfait instanceof Forfait && $forfait->getEpuise()) {
                    $form->add('forfait', 'entity', [
                        'class' => 'KGCRdvBundle:Forfait',
                        'property' => 'label',
                        'error_bubbling' => true,
                        'query_builder' => function ($er) use ($client) {
                            return $er->findByClientQB($client);
                        },
                    ]);
                }
            }
        });
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($client) {
            $conso = $event->getData();
            if ($conso instanceof ConsommationForfait) {
                $forfait = $conso->getForfait();
                $form = $event->getForm();
                if ($forfait instanceof Forfait && $forfait->getEpuise()) {
                    $form->add('forfait', 'entity', [
                        'class' => 'KGCRdvBundle:Forfait',
                        'property' => 'label',
                        'error_bubbling' => true,
                        'query_builder' => function ($er) use ($client) {
                            return $er->findByClientQB($client);
                        },
                    ]);
                }
            }
        });
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\RdvBundle\Entity\ConsommationForfait',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_RdvBundle_ConsommationForfait';
    }
}
