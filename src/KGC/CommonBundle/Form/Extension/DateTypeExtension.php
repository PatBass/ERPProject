<?php

// src/KGC/CommonBundle/Form/Extension/DateTypeExtension.php


namespace KGC\CommonBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * DateTypeExtension.
 *
 * Extension du type de champ date avec options d'interface
 *
 * @category Form/Extension
 *
 * @author Laurene Dourdin <2aurene@gmail.com>
 *
 * @DI\Service("kgc.form.date.extension")
 * @DI\Tag("form.type_extension", attributes = {"alias" = "date"})
 */
class DateTypeExtension extends AbstractTypeExtension
{
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars = array_replace($view->vars, array(
            'input_mask' => $options['input-mask'],
            'date_picker' => $options['date-picker'],
            'start_date' => $options['start-date'],
            'limit_size' => $options['limit-size'],
        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'input-mask' => false,
            'date-picker' => false,
            'start-date' => false,
            'limit-size' => false,
        ));
    }

    /**
     * Retourne le nom du type de champ qui est étendu.
     *
     * @return string Le nom du type qui est étendu
     */
    public function getExtendedType()
    {
        return 'date';
    }
}
