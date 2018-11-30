<?php

namespace KGC\ChatBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use KGC\ChatBundle\Entity\ChatFormulaRate;
use KGC\ChatBundle\Entity\ChatType;

/**
 * Class ChatFormulaRateType.
 *
 * @category Form
 */
class ChatFormulaRateType extends AbstractType
{
    /**
     * @var int
     */
    protected $formulaType;

    /**
     * @var int
     */
    protected $chatTypeId;

    public function __construct($formulaType, ChatType $chatType)
    {
        $this->formulaType = $formulaType;
        $this->chatTypeId = $chatType->getType();
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($this->chatTypeId == ChatType::TYPE_MINUTE) {
            $unitLabel = 'Durée (minutes)';
            $bonusLabel = 'Bonus (minutes)';

            // champs dependant des donnees
            $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
                $data = $event->getData();

                $data->setUnit($data->getUnit() / 60);
                $data->setBonus($data->getBonus() / 60);
            });

            $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $data = $event->getData();

                $data['unit'] *= 60;
                $data['bonus'] *= 60;

                $event->setData($data);
            });
        } else {
            $unitLabel = 'Nombre de questions';
            $bonusLabel = 'Bonus (questions)';
        }

        if ($this->formulaType !== ChatFormulaRate::TYPE_FREE_OFFER) {
            $builder->add('price', 'money', [
                'label' => 'Prix',
                'required' => true,
            ]);
        }

        $builder
            ->add('unit', 'integer', [
                'label' => $unitLabel,
                'required' => true,
            ])
            ->add('bonus', 'integer', [
                'label' => $bonusLabel,
                'required' => true,
            ]);
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\ChatBundle\Entity\ChatFormulaRate',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_ChatBundle_chatFormulaRate';
    }
}
