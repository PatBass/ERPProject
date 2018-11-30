<?php

// src/KGC/RdvBundle/Form/DateTimeType.php


namespace KGC\CommonBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\DateTimeType as BaseDateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * DateTimeType.
 *
 * @category Form
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class DateTimeType extends BaseDateTimeType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        // pass options of DateTypeExtension
        $field = $builder->get('date');
        $fieldType = $field->getType()->getName();
        $fieldOptions = $field->getOptions();

        $specOptions = array_intersect_key($options, array_flip(array(
            'input-mask',
            'date-picker',
            'start-date',
            'limit-size',
        )));

        $builder->add('date', $fieldType, array_merge($fieldOptions, $specOptions));

        $hide_time = $options['hide_time'];
        $time_type = $builder->get('time')->getType()->getName();
        $time_options = $builder->get('time')->getOptions();

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) use ($hide_time, $time_type, $time_options) {
            $date = $event->getData();
            $form = $event->getForm();
            if ($date === null && $hide_time) {
                $form->add('time', $time_type, array_merge($time_options, ['required' => false, 'attr' => ['value' => '00:00:00']]));
            }
        });
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $view->vars = array_replace($view->vars, array(
            'hide_time' => $options['hide_time'],
        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $resolver->setDefaults(array(
            'input-mask' => false,
            'date-picker' => false,
            'start-date' => false,
            'limit-size' => false,
            'hide_time' => false,
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_datetime';
    }
}
