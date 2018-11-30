<?php

// src/KGC/RdvBundle/Form/NotesType.php


namespace KGC\RdvBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use KGC\ClientBundle\Service\HistoriqueManager;

/**
 * Constructeur de formulaire pour entité Adresse.
 *
 * @category Form
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class NotesType extends AbstractType
{
    /**
     * @var HistoriqueManager
     */
    protected $historiqueManager;

    /**
     * @var sections
     */
    protected $sections;

    /**
     * @param mixed $historiqueManager
     */
    public function __construct($sections = array(), $historiqueManager = null)
    {
        $this->historiqueManager = $historiqueManager;
        $this->sections = $sections;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($this->historiqueManager instanceof HistoriqueManager) {
            $fields = $this->historiqueManager->getFormFieldsBySection($this->sections);
            foreach ($fields as $f) {
                $form = $f['form'];
                $form->setLabel($this->historiqueManager->getTypeLabelMapping($f['name']));
                $builder->add($f['name'], $f['form']);
            }
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'mapped' => false,
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_RdvBundle_notes';
    }
}
