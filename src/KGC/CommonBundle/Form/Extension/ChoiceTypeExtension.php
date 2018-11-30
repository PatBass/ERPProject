<?php

// src/KGC/CommonBundle/Form/Extension/ChoiceTypeExtension.php


namespace KGC\CommonBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * ChoiceTypeExtension.
 *
 * Extension du type de champ choice :
 *  - ajoute des attributs aux options
 *
 * @category Form/Extension
 *
 * @author Laurene Dourdin <2aurene@gmail.com>
 *
 * @DI\Service("kgc.form.choice.extension")
 * @DI\Tag("form.type_extension", attributes = {"alias" = "choice"})
 */
class ChoiceTypeExtension extends AbstractTypeExtension
{
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $js_dependant_select = $options['js_dependant_select'];
        $view->vars = array_replace($view->vars, array(
            'choices_attr' => $options['choices_attr'],
            'js_dependant_select' => $js_dependant_select,
        ));
        if ($js_dependant_select !== false) {
            $attr = $view->vars['attr'];
            $deps = array_map(function ($n) use ($view) {
                return '#'.$view->parent[$n]->vars['id'];
            }, explode(';', $js_dependant_select['depends_on']));
            $view->vars['attr'] = array_merge($attr, array(
                'class' => (isset($attr['class']) ? $attr['class'] : '').' js-dependant-select',
                'data-depends-on' => implode(';', $deps),
            ));
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'choices_attr' => array(),
            'js_dependant_select' => false,
        ));
    }

    /**
     * Retourne le nom du type de champ qui est étendu.
     *
     * @return string Le nom du type qui est étendu
     */
    public function getExtendedType()
    {
        return 'choice';
    }
}
