<?php

namespace KGC\RdvBundle\Elastic\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * Class RdvSearchType.
 *
 * @DI\Service("kgc.elastic.rdv.form")
 * @DI\FormType
 */
class RdvSearchType extends AbstractType
{
    /**
     * @var RdvSearchFormBuilder
     */
    protected $unmanagedFormBuilder;

    /**
     * @param RdvSearchFormBuilder $unmanagedFormBuilder
     *
     * @DI\InjectParams({
     *      "unmanagedFormBuilder" = @DI\Inject("kgc.elastic.rdv.form_builder"),
     * })
     */
    public function __construct(RdvSearchFormBuilder $unmanagedFormBuilder)
    {
        $this->unmanagedFormBuilder = $unmanagedFormBuilder;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->unmanagedFormBuilder->buildUnmanagedForm($builder);
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\RdvBundle\Elastic\Model\RdvSearch',
            'csrf_protection' => false,
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'elastic_search';
    }
}
