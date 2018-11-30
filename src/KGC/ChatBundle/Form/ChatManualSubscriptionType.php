<?php

namespace KGC\ChatBundle\Form;

use KGC\ChatBundle\Entity\ChatPayment;
use KGC\ChatBundle\Entity\ChatSubscription;
use KGC\RdvBundle\Repository\TPERepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ChatPaymentOfferType.
 */
class ChatManualSubscriptionType extends AbstractType
{
    /**
     * @var ChatSubscription
     */
    protected $subscription;

    /**
     * @param ChatSubscription $subscription
     */
    public function __construct(ChatSubscription $subscription)
    {
        $this->subscription = $subscription;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('amount', MoneyType::class, [
                'label' => 'Montant',
                'attr' => ['disabled' => true],
                'data' => $this->subscription->getChatFormulaRate()->getPrice()
            ])
            ->add('paymentMethod', EntityType::class, [
                'class' => 'KGCRdvBundle:MoyenPaiement',
                'choice_label' => 'libelle',
                'label' => 'Moyen de paiement',
                'required' => true,
            ])
            ->add('tpe', EntityType::class, [
                'class' => 'KGCRdvBundle:TPE',
                'empty_value' => 'Choisissez un TPE',
                'label' => 'TPE',
                'required' => false,
                'query_builder' => function (TPERepository $er) {
                    return $er->findAllManualQB(true);
                }
            ])
            ->add('commentary', TextType::class, [
                'label' => 'Commentaire',
                'required' => false,
                'attr' => ['maxlength' => 100]
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => ChatPayment::class,
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_ChatBundle_chatpaymentoffer';
    }
}
