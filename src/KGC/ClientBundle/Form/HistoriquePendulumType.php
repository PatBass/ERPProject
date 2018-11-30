<?php

namespace KGC\ClientBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;

class HistoriquePendulumType extends HistoriqueType
{
    /**
     * @param $type
     */
    public function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('pendulum', 'collection', [
            'type' => new PendulumType($this->type),
            'attr' => array('class' => 'pendulum-histo'),
            'allow_add' => true,
            'by_reference' => false,
            'label' => false,
        ]);

//        $builder->add('pendulum', new PendulumType($this->type), [
//            'label' => false
//        ]);
    }
}
