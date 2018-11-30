<?php

// src/KGC/CommonBundle/Form/Extension/FormTypeExtension.php


namespace KGC\CommonBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * FormTypeExtension.
 *
 * Extension pour tout les types de champs :
 *  - option input-addon
 *
 * @category Form/Extension
 *
 * @author Laurene Dourdin <2aurene@gmail.com>
 *
 * @DI\Service("kgc.form.formtype.extension")
 * @DI\Tag("form.type_extension", attributes = {"alias" = "form"})
 */
class FormTypeExtension extends AbstractTypeExtension
{
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $ajax_autofill = $options['js_dependant_ajax_autofill'];
        $view->vars = array_replace($view->vars, array(
            'input_addon' => $options['input_addon'],
            'action_button' => $options['action_button'],
            'action_attr' => array_merge(['type' => 'button'], $options['action_attr']),
            'action_ajax_url' => $options['action_ajax_url'],
            'add_type' => $options['add_type'],
            'js_dependant_ajax_autofill' => $ajax_autofill,
        ));
        if ($ajax_autofill !== false) {
            $attr = $view->vars['attr'];
            $view->vars['attr'] = array_merge($attr, array(
                'class' => (isset($attr['class']) ? $attr['class'] : '').' js-dependant-ajax-autofill',
                'data-depends-on' => '#'.$view->parent[$ajax_autofill['depends_on']]->vars['id'],
                'data-ajax-autofill-url' => $ajax_autofill['url'],
            ));
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'input_addon' => false,
            'action_button' => false,
            'action_attr' => ['type' => 'button'],
            'action_ajax_url' => null,
            'add_type' => false,
            'js_dependant_ajax_autofill' => false,
        ));
    }

    /**
     * Retourne le nom du type de champ qui est étendu.
     *
     * @return string Le nom du type qui est étendu
     */
    public function getExtendedType()
    {
        return 'form';
    }
}
