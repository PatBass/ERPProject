<?php

// src/KGC/RdvBundle/Form/TarificationType.php


namespace KGC\RdvBundle\Form;

use KGC\CommonBundle\Form\MinuteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Constructeur de formulaire pour entité Tarification.
 *
 * @category Form
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class TarificationType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $addtype = $options['add_type'];
        $tarcode_config = array(
            'class' => 'KGCRdvBundle:CodeTarification',
            'property' => 'libelle',
            'empty_value' => ' ',
            'attr' => ['class' => 'js-tarification'],
            'disabled' => $addtype,
        );

        $builder
            ->add('code_old', 'text')
            ->add('code', 'entity', $tarcode_config)
            ->add('temps', new MinuteType())
            ->add('montant_minutes', 'money', [
                'attr' => ['class' => 'trigger_amount_calc add_amount', 'data-ignore-if-ref-not-empty' => 'forfait-vendu'],
                'required' => false,
            ])
            ->add('decount10min', 'checkbox', [
                'required' => false,
                'label' => '10min gratuites',
            ])
            ->add('montant_produits', 'money', [
                'attr' => ['class' => 'trigger_amount_calc add_amount', 'data-enableable' => '0'],
                'required' => false,
                'read_only' => true,
            ])
            ->add('montant_frais', 'money', [
                'attr' => ['class' => 'trigger_amount_calc add_amount'],
                'required' => false,
            ])
            ->add('montant_remise', 'money', [
                'attr' => ['class' => 'trigger_amount_calc sub_amount'],
                'required' => false,
            ])
            ->add('montant_total', 'money', [
                'attr' => ['class' => 'auto_amount_result'],
            ])
        ;
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) use ($addtype) {
            $form = $event->getForm();
            $rdv = $form->getParent()->getData();
            $client = $rdv->getClient();

            if ($rdv->getVoyant() !== null) {
                $form['code']->setData($rdv->getVoyant()->getCodeTarification());
            }

            $form->add('forfait_vendu', new ForfaitType($rdv), [
                'required' => false,
                'add_type' => $addtype,
            ]);

            $form->add('consommations_forfaits', 'collection', [
                'type' => new ConsommationForfaitType($client),
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ]);

            $form->add('produits', 'collection', array(
                'type' => new VentesProduitsType($rdv),
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'required' => false,
            ));
        });
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            if ($form['forfait_vendu']->has('cancel')) {
                if ($form['forfait_vendu']['cancel']->getData()) {
                    $tar = $form->getData();
                    $forfait_vendu = $tar->getForfaitVendu();
                    $can_delete = true;
                    foreach ($forfait_vendu->getConsommations() as $conso) {
                        if ($conso->getTarification() !== $tar) {
                            $can_delete = false;
                        }
                    }
                    if ($can_delete) {
                        $tar->setForfaitVendu(null);
                    } else {
                        $form->addError(new FormError('Vous ne pouvez pas annuler un forfait au moins partiellement consommé dans une autre consultation.'));
                    }
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
            'data_class' => 'KGC\RdvBundle\Entity\Tarification',
            'attr' => ['class' => 'auto_amount_calc'],
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_RdvBundle_tarification';
    }
}
