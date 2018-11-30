<?php

// src/KGC/CommonBundle/Form/CommonAbstractType.php


namespace KGC\CommonBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

abstract class CommonAbstractType extends AbstractType
{
    protected function changeOptions(FormBuilderInterface $builder, $fieldName, $options)
    {
        $field = $builder->get($fieldName);
        $fieldType = $field->getType()->getName();
        $fieldOptions = $field->getOptions();

        if (isset($this->field_defs)) {
            $this->field_defs[$fieldName]['options'] = array_merge($fieldOptions, $options);
        }
        $builder->add($fieldName, $fieldType, array_merge($fieldOptions, $options));
    }

    protected function addFormfromDefArray(FormBuilderInterface $builder, $fields_definition = array())
    {
        foreach ($fields_definition as $fieldName => $field) {
            $builder->add($fieldName, $field['type'], $field['options']);
        }
    }
}
