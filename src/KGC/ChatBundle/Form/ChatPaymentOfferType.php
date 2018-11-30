<?php

namespace KGC\ChatBundle\Form;

use KGC\ChatBundle\Entity\ChatPayment;
use KGC\ChatBundle\Entity\ChatType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ChatPaymentOfferType.
 */
class ChatPaymentOfferType extends AbstractType
{
    /**
     * @var ChatType
     */
    protected $chatType;

    /**
     * @param ChatType $chatType
     */
    public function __construct(ChatType $chatType)
    {
        $this->chatType = $chatType;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($this->chatType->getType() == ChatType::TYPE_QUESTION) {
            $label = 'Questions offertes';
            $list = [5, 10, 15, 20, 25, 30];
        } else {
            $label = 'Minutes offertes';
            $list = [5, 10, 15, 20, 25, 30, 45, 60, 90, 120];
        }

        $choices = [];
        foreach ($list as $value) {
            $choices[$value] = $value;
        }

        $builder
            ->add('unit', ChoiceType::class, [
                'choices' => $choices,
                'label' => $label
            ])
            ->add('commentary', TextType::class, [
                'label' => 'Commentaire',
                'attr' => ['maxlength' => 100],
                'required' => false
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
