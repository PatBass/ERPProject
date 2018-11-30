<?php

// src/KGC/CommonBundle/Form/Extension/CheckboxTypeExtension.php


namespace KGC\CommonBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * CheckboxTypeExtension.
 *
 * Extension du type de champ checkbox :
 *  - style Switch
 *  - enable de champs associés
 *
 * @category Form/Extension
 *
 * @author Laurene Dourdin <2aurene@gmail.com>
 *
 * @DI\Service("kgc.form.checkbox.extension")
 * @DI\Tag("form.type_extension", attributes = {"alias" = "checkbox"})
 */
class CheckboxTypeExtension extends AbstractTypeExtension
{
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars = array_replace($view->vars, array(
            'switch_style' => $options['switch_style'],
            'enable_fields' => $options['enable_fields'],
            'checked_label_style' => $options['checked_label_style'],
        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'switch_style' => null,
            'enable_fields' => null,
            'checked_label_style' => true,
        ));
    }

    /**
     * Retourne le nom du type de champ qui est étendu.
     *
     * @return string Le nom du type qui est étendu
     */
    public function getExtendedType()
    {
        return 'checkbox';
    }
}
