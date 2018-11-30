<?php

namespace KGC\ChatBundle\Form;

use KGC\Bundle\SharedBundle\Repository\WebsiteRepository;
use KGC\ChatBundle\Entity\ChatPromotion;
use KGC\ChatBundle\Entity\ChatType;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class ChatPromotionType.
 *
 * @category Form
 */
class ChatPromotionType extends AbstractType
{
    /**
     * @var array
     */
    protected $chatTypesByWebsite;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @param array
     */
    public function __construct($chatTypesByWebsite, EntityManager $em)
    {
        $this->chatTypesByWebsite = $chatTypesByWebsite;
        $this->em = $em;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $em = $this->em;

        $builder
            ->add('name', 'text', [
                'label' => 'Nom',
                'required' => true,
            ])
            ->add('enabled', 'checkbox', [
                'label' => 'Activé',
                'required' => false,
                'switch_style' => 5,
            ])
            ->add('type', 'choice', [
                'label' => 'Type de promotion',
                'choices' => [
                    ChatPromotion::TYPE_CODE_PROMO => 'Code promo'
                ]
            ])
            ->add('promotionCode', 'text', [
                'label' => 'Code promo',
                'required' => true
            ])
            ->add('unitType', 'choice', [
                'label' => 'Type de bonus',
                'choices' => [
                    ChatPromotion::UNIT_TYPE_BONUS => 'Temps/questions offertes',
                    ChatPromotion::UNIT_TYPE_PRICE => 'Réduction (€) - (Formules payantes)',
                    ChatPromotion::UNIT_TYPE_PERCENTAGE => 'Réduction (%) - (Formules payantes)'
                ],
                'attr' => [
                    'class' => 'unit-type-choice',
                    'data-unit-bonus' => ChatPromotion::UNIT_TYPE_BONUS,
                    'data-unit-default' => ChatPromotion::UNIT_TYPE_PERCENTAGE,
                    'data-unit-money' => ChatPromotion::UNIT_TYPE_PRICE,
                    'data-label-bonus' => json_encode([ChatType::TYPE_MINUTE => 'Minutes offertes', ChatType::TYPE_QUESTION => 'Questions offertes']),
                    'data-label-default' => 'Réduction (%)'
                ]
            ])
            ->add('allowedFormulas', 'choice', [
                'label' => 'Types de formules autorisées',
                'multiple' => true,
                'expanded' => true,
                'mapped' => false,
                'choices' => ChatPromotion::getFormulaFilterLabels(),
                'attr' => ['data-choice-reduction-not-allowed' => json_encode([ChatPromotion::FORMULA_FILTER_NONE, ChatPromotion::FORMULA_FILTER_DISCOVERY])]
            ])
            ->add('unit', 'integer', [
                'attr' => ['data-unit-field' => 'default']
            ])
            ->add('unitMoney', 'money', [
                'label' => 'Réduction (€)',
                'required' => false,
                'mapped' => false,
                'attr' => ['data-unit-field' => 'money']
            ])
            ->add('website', 'entity', [
                'label' => 'Site',
                'class' => 'KGCSharedBundle:Website',
                'choice_label' => 'libelle',
                'query_builder' => function (WebsiteRepository $repo) {
                    return $repo->findIsChatQB(true, true);
                },
                'attr' => [
                    'class' => 'promotion-website',
                    'data-website-types' => json_encode($this->chatTypesByWebsite)
                ]
            ])
            ->add('startDate', 'date', [
                'label' => 'Date de début',
                'required' => false,
                'widget' => 'single_text',
                'input-mask' => 'true',
                'format' => 'dd/MM/yyyy',
                'date-picker' => true
            ])
            ->add('endDate', 'date', [
                'label' => 'Date de fin',
                'required' => false,
                'widget' => 'single_text',
                'input-mask' => 'true',
                'format' => 'dd/MM/yyyy',
                'date-picker' => true
            ])
            ->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
                $promotion = $event->getData();
                $form = $event->getForm();

                $unitType = $promotion->getUnitType();

                $allowedFormulasData = [];

                foreach (ChatPromotion::getFormulaFilterLabels() as $filter => $label) {
                    if ($promotion->hasFormulaFilter($filter)) {
                        $allowedFormulasData[] = $filter;
                    }
                }
                $form->get('allowedFormulas')->setData($allowedFormulasData);

                if ($unitType == ChatPromotion::UNIT_TYPE_PRICE) {
                    $form->get('unit')->setData(0);
                    $form->get('unitMoney')->setData($promotion->getUnit() / ChatPromotion::PRICE_RATIO);
                } else if ($unitType == ChatPromotion::UNIT_TYPE_BONUS) {
                    $website = $promotion->getWebsite();

                    if ($website && $this->chatTypesByWebsite[$website->getId()] == ChatType::TYPE_MINUTE) {
                        $form->get('unit')->setData($promotion->getUnit() / ChatPromotion::MINUTE_RATIO);
                    }
                }
            })
            ->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($em) {
                $data = $event->getData();
                $form = $event->getForm();

                if ($data->getUnitType() == ChatPromotion::UNIT_TYPE_PRICE) {
                    $data->setUnit($form->get('unitMoney')->getData() * ChatPromotion::PRICE_RATIO);
                } else if ($data->getUnitType() == ChatPromotion::UNIT_TYPE_BONUS && $this->chatTypesByWebsite[$data->getWebsite()->getId()] == ChatType::TYPE_MINUTE) {
                    $data->setUnit($data->getUnit() * ChatPromotion::MINUTE_RATIO);
                }

                $data->setFormulaFilter(array_sum($form->get('allowedFormulas')->getData()));

                $event->setData($data);

                $cpRepo = $em->getRepository('KGCChatBundle:ChatPromotion');

                if ($cpRepo->isChatPromotionWithSameCode($data)) {
                    $form->addError(new FormError('Il y a déjà une promotion avec le même code sur ce site.'));
                }

                if ($cpRepo->isChatPromotionWithSameName($data)) {
                    $form->addError(new FormError('Il y a déjà une promotion avec le même nom sur ce site.'));
                }
            });
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\ChatBundle\Entity\ChatPromotion',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_ChatBundle_chatPromotion';
    }
}
