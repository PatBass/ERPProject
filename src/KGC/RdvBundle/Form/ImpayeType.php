<?php

namespace KGC\RdvBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Choice;
use KGC\CommonBundle\Form\DatePeriodType;
use KGC\RdvBundle\Entity\Encaissement;

/**
 * Class Impaye Type.
 *
 * @category Form
 */
class ImpayeType extends AbstractType
{
    protected $defaultPeriod;

    public function __construct($defaultPeriod = null)
    {
        $currentDate = new \DateTime;
        $this->defaultPeriod = $defaultPeriod ?: ['begin' => $currentDate, 'end' => $currentDate];
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', 'hidden', [
                'constraints' => new Choice(['choices' => ['done', 'denied', 'cbi']]),
            ])
            ->add('period', new DatePeriodType, [
                'required' => true,
                'mapped' => false,
                'data' => $this->defaultPeriod
            ]);
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return null;
    }
}
