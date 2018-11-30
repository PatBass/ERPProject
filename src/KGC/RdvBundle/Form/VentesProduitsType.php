<?php

// src/KGC/RdvBundle/Form/VentesProduitsType.php


namespace KGC\RdvBundle\Form;

use KGC\ClientBundle\Entity\Historique;
use KGC\ClientBundle\Entity\Option;
use KGC\CommonBundle\Form\NumberChoiceType;
use KGC\CommonBundle\Upgrade\UpgradeDate;
use KGC\RdvBundle\Entity\EnvoiProduit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Constructeur de formulaire pour entité VentesProduits.
 *
 * @category Form
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class VentesProduitsType extends AbstractType
{
    protected $productsChoicesAttributes = [];

    protected $rdv;

    public function __construct($rdv = null)
    {
        $this->rdv = $rdv;
    }

    protected function isAutoPriceAvailable(\DateTime $date)
    {
        return $date >= UpgradeDate::getDate(UpgradeDate::PRODUCT_AUTO_TARIFICATION);
    }

    protected function buildProductChoicesAttributes($listProducts)
    {
        $choices = [];
        foreach ($listProducts as $p) {
            $choices[$p->getId()] = ['data-price' => $p->getDataAttr()];
        }

        $this->productsChoicesAttributes = $choices;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $dateConsultation = $this->rdv->getDateConsultation();

        $productField = array(
            'class' => 'KGCClientBundle:Option',
            'property' => 'label',
            'empty_value' => '',
            'query_builder' => function ($er) use ($dateConsultation) {
                $this->buildProductChoicesAttributes(
                    $er->findByType(Option::TYPE_PRODUCT)
                );

                return $er->findAllByTypeQB(Historique::TYPE_PRODUCT, true, $dateConsultation);
            },
            'attr' => ['class' => 'price-calc-product'],
        );

        $priceField = [
            'attr' => ['class' => 'produits_montant_calc price-calc-field'],
        ];

        $builder
            ->add('produit', 'entity', $productField)
            ->add('quantite', new NumberChoiceType(1), [
                'attr' => ['class' => 'price-calc-qt no-appearance no-padding center'],
                'required' => true,
            ])
            ->add('montant', 'money', $priceField)
            //->add('quantite_envoi', 'hidden')
            ->add('supprimer', 'button', array(
                'label' => '<i class="icon-trash bigger-120"></i>',
                'attr' => array(
                    'class' => 'collection_del del_product btn-danger btn-xs',
                    'style' => 'margin: 5px;',
                ),
            ))
        ;

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) use ($productField, $dateConsultation, $priceField) {
            $form = $event->getForm();

            if ($dateConsultation && $this->isAutoPriceAvailable($dateConsultation)) {
                $priceField['read_only'] = true;
                $priceField['attr']['data-enableable'] = 0;
            }
            $productField['choices_attr'] = $this->productsChoicesAttributes;

            $form->add('produit', 'entity', $productField);
            $form->add('montant', 'money', $priceField);

            if ($form->getData() !== null) {
                $envoi_produit = $form->getData()->getEnvoi();
                if ($envoi_produit instanceof EnvoiProduit) {
                    if ($envoi_produit->getEtat() == EnvoiProduit::DONE) {
                        $form->remove('supprimer');
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
            'data_class' => 'KGC\RdvBundle\Entity\VentesProduits',
            'attr' => ['class' => 'price-calc-container'],
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_RdvBundle_VentesProduits';
    }
}
