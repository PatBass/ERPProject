<?php

namespace KGC\ChatBundle\Form;

use KGC\ChatBundle\Entity\ChatPayment;
use KGC\RdvBundle\Repository\TPERepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ChatPaymentType.
 */
class ChatPaymentType extends AbstractType
{
    /**
     * @var bool
     */
    protected $isManualPayment;

    /**
     * @var bool
     */
    protected $isFlexible;

    /**
     * @param ChatPayment $chatPayment
     */
    public function __construct(ChatPayment $chatPayment)
    {
        $this->isManualPayment = $chatPayment->getPaymentMethod() !== null && $chatPayment->getPayment() === null;
        $this->isFlexible = $chatPayment->getChatFormulaRate()->getFlexible();
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $states = ChatPayment::getStates($this->isFlexible);

        $builder
            ->add('state', ChoiceType::class, [
                'choices' => $states,
            ]);
        if ($this->isFlexible) {
            $builder->add('commentary', TextType::class, [
                'required' => false,
            ]);
        } else {
            $builder->add('opposedDate', DateType::class, [
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'date-picker' => true,
                'input-mask' => true,
                'required' => false,
            ]);

            if ($this->isManualPayment) {
                $builder
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
                    ]);
            }
        }
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($states) {
            $chatPayment = $event->getData();
            $form = $event->getForm();

            if ($chatPayment->getPayment() !== null) {
                unset($states[ChatPayment::STATE_REFUNDED]);

                $form
                    ->add('state', ChoiceType::class, [
                        'choices' => $states,
                    ]);
            }
        });
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\ChatBundle\Entity\ChatPayment',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_ChatBundle_chatpayment';
    }
}
