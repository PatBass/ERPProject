<?php

namespace KGC\ClientBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ContactType extends AbstractType
{
    protected $list;
    protected $tchat = 0;
    /**
     * Constructeur.
     */
    public function __construct($list, $tchat = 0)
    {
        $this->list = $list;
        $this->tchat = $tchat;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('firstname', 'text', array(
                'label' => 'Prénom',
                'required' => true,
            ))
            ->add('lastname', 'text', array(
                'label' => 'Nom',
                'required' => true,
            ))
            ->add('phone', 'text', array(
                'label' => 'Téléphone',
                'required' => true,
            ))
            ->add('list', 'entity', array(
                'class' => 'KGCClientBundle:ListContact',
                'property' => 'formProperty',
                'empty_value' => 'Liste de contact',
                'label' => 'Liste',
                'required' => true,
                'query_builder' => function (EntityRepository $er) {
                    return $er->getSimpleListQB($this->tchat);
                },
                'attr' => array('class' => 'hide')
            ));
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\ClientBundle\Entity\Contact',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_clientbundle_contact';
    }
}
