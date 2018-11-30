<?php

namespace KGC\ClientBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ApiLandingDRIUserType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', 'email', [
                'required' => false,
            ])
            ->add('firstName', 'text', [
                'required' => true,
            ])
            ->add('gender', 'text', [
                'required' => false,
            ])
            ->add('birthday', 'date', [
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('sign', 'text', [
                'required' => false,
            ])
            ->add('phone', 'text', [
                'required' => true,
            ])
            ->add('country', 'text', [
                'required' => false,
            ])
            ->add('spouseName', 'text', [
                'required' => false,
            ])
            ->add('spouseSign', 'text', [
                'required' => false,
            ])
            ->add('spouseBirthday', 'date', [
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('questionCode', 'text', [
                'required' => false,
            ])
            ->add('questionText', 'text', [
                'required' => false,
            ])
            ->add('questionDate', 'date', [
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('questionSubject', 'text', [
                'required' => false,
            ])
            ->add('questionContent', 'text', [
                'required' => false,
            ])
            ->add('isOptinNewsletter', 'checkbox', [
                'required' => false,
            ])
            ->add('isOptinPartner', 'checkbox', [
                'required' => false,
            ])
            ->add('myastroId', 'text', [
                'required' => false
            ])
            ->add('myastroIp', 'text', [
                'required' => false,
            ])
            ->add('myastroSource', 'text', [
                'required' => false,
            ])
            ->add('myastroUrl', 'text', [
                'required' => false,
            ])
            ->add('myastroPsychic', 'text', [
                'required' => false,
            ])
            ->add('myastroWebsite', 'text', [
                'required' => false,
            ])
            ->add('myastroPromoCode', 'text', [
                'required' => false,
            ])
            ->add('myastroSupport', 'text', [
                'required' => false,
            ])
            ->add('myastroGclid', 'text', [
                'required' => false,
            ])
            ->add('reflexSource', 'text', [
                'required' => false,
            ])
            ->add('reflexAffilateId', 'integer', [
                'required' => false,
            ])
            ->add('createdAt', 'date', [
                'required' => false,
                'widget' => 'single_text',
            ]);
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\Bundle\SharedBundle\Entity\LandingUser',
            'csrf_protection' => false,
            'validation_groups' => array('dri', 'Default')
        ));
    }

    public function getName()
    {
        return '';
    }
}
