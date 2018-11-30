<?php

namespace KGC\ClientBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormBuilderInterface;

class HistoriqueOptionType extends HistoriqueType
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @return string
     */
    protected function getEntityClass()
    {
        return 'KGCClientBundle:Option';
    }

    /**
     * @return string
     */
    protected function getDisplayProperty()
    {
        return 'label';
    }

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

        $builder
            ->add('option', 'entity', array(
                'class' => $this->getEntityClass(),
                'label_attr' => ['class' => 'histo-field-label'],
                'property' => $this->getDisplayProperty(),
                'empty_value' => '',
                'label' => $this->label,
                'required' => false,
                'query_builder' => function (EntityRepository $er) {
                    return $er->findAllByTypeQB($this->type);
                },
                'constraints' => isset($options['constraints']) ? $options['constraints'] : [],
            ))
        ;
    }
}
