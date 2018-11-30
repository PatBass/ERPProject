<?php

// src/KGC/RdvBundle/Form/ForfaitType.php


namespace KGC\RdvBundle\Form;

use KGC\ClientBundle\Entity\Historique;
use KGC\ClientBundle\Entity\Option;
use KGC\CommonBundle\Form\MinuteType;
use KGC\CommonBundle\Upgrade\UpgradeDate;
use KGC\RdvBundle\Entity\Forfait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Constructeur de formulaire pour entité Forfait.
 *
 * @category Form
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class ForfaitType extends AbstractType
{
    protected $choiceAttributes = [];

    protected $rdv;

    public function __construct($rdv = null)
    {
        $this->rdv = $rdv;
    }

    protected function isAutoForfaitAvailable(\DateTime $date)
    {
        return $date >= UpgradeDate::getDate(UpgradeDate::FORFAIT_AUTO_TARIFICATION);
    }

    protected function buildForfaitChoicesAttributes($config)
    {
        $choices = [];

        foreach ($config as $c) {
            $id = $c->getForfait()->getId();
            if (!array_key_exists($id, $choices)) {
                $choices["$id"] = [
                    'data-forfait' => $id,
                    'data-tar-'.$c->getCodeTarification()->getId() => $c->getPrice(),
                ];
            }
            $choices[$id] = array_merge($choices[$id], [
                'data-tar-'.$c->getCodeTarification()->getId() => $c->getPrice(),
            ]);
        }

        $this->choiceAttributes = $choices;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $dateConsultation = $this->rdv->getDateConsultation();
        $mainConfig = [
            'class' => 'KGCClientBundle:Option',
            'property' => 'label',
            'attr' => ['class' => 'js-forfait'],
            'empty_value' => '',
            'query_builder' => function ($er) use ($dateConsultation) {
                $this->buildForfaitChoicesAttributes(
                    $er->findForfaitTarification()
                );

                return $er->findAllByTypeQB(Historique::TYPE_PLAN, true, $dateConsultation);
            },
        ];

        $priceConfig = ['attr' => ['class' => 'trigger_amount_calc add_amount js-tar-amount', 'data-ignore-ref' => 'forfait-vendu']];

        $builder
            ->add('nom', 'entity', $mainConfig)
            ->add('prix', 'money', $priceConfig);

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) use ($mainConfig, $priceConfig) {
            $form = $event->getForm();
            $forfait = $form->getData();

            if ($forfait === null) {
                $form->add('temps_consomme', new MinuteType(), [
                    'attr' => ['placeholder' => 'Temps consommé'],
                ]);
            } elseif ($forfait instanceof Forfait) {
                $form->add('cancel', 'checkbox', [
                    'mapped' => false,
                    'required' => false,
                    'checked_label_style' => false,
                    'attr' => [
                        'class' => 'ace-checkbox-2 remove-checkbox disable-fields trigger_amount_calc',
                        'title' => 'Annuler',
                        'data-enable' => 'kgc_RdvBundle_rdv_tarification_forfait_vendu_nom;kgc_RdvBundle_rdv_tarification_forfait_vendu_prix',
                    ],
                ]);
                $priceConfig['disabled'] = true;
            }

            $dateConsultation = $form->getParent()->getParent()->getData()->getDateConsultation();
            if ($dateConsultation && $this->isAutoForfaitAvailable($dateConsultation)) {
                $priceConfig['read_only'] = true;
                $priceConfig['attr']['data-enableable'] = 0;
                $mainConfig['choices_attr'] = $this->choiceAttributes;
            }

            $form->add('prix', 'money', $priceConfig);
            $form->add('nom', 'entity', $mainConfig);

        });
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\RdvBundle\Entity\Forfait',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_RdvBundle_Forfait';
    }
}
