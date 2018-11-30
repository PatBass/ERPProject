<?php

namespace KGC\ClientBundle\Form;

use Doctrine\ORM\EntityRepository;
use KGC\ClientBundle\Entity\Pendulum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PendulumType extends AbstractType
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
        $builder
            ->add('target', 'text', [
                'required' => false,
                'label' => false,
                'attr' => ['placeholder' => 'X'],
            ])
            ->add('customQuestion', 'text', [
                'required' => false,
                'label' => false,
                'attr' => ['placeholder' => 'Question personnalisée'],
            ])
            ->add('question', 'entity', [
                'class' => $this->getEntityClass(),
                'label_attr' => ['class' => 'histo-field-label'],
                'property' => $this->getDisplayProperty(),
                'empty_value' => 'Question ???',
                'label' => false,
                'required' => false,
                'query_builder' => function (EntityRepository $er) {
                    return $er->findAllByTypeQB($this->type);
                },
            ])
            ->add('answer', 'checkbox', [
                'label' => false,
                'required' => false,
                'switch_style' => 5,
            ])
            ->add('type', 'hidden', [
                'attr' => array('class' => 'pendulum-type-field'),
            ]);
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\ClientBundle\Entity\Pendulum',
        ));
    }

    public function getName()
    {
        return 'kgc_clientbundle_pendulum';
    }
}
