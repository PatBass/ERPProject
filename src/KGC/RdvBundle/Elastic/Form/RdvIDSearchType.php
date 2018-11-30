<?php

namespace KGC\RdvBundle\Elastic\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * Class RdvIDSearchType.
 *
 * @DI\Service("kgc.elastic.rdv.id.form")
 * @DI\FormType
 */
class RdvIDSearchType extends AbstractType
{
    /**
     * @var RdvSearchFormBuilder
     */
    protected $unmanagedFormBuilder;

    /**
     * @param RdvIDSearchFormBuilder $unmanagedFormBuilder
     *
     * @DI\InjectParams({
     *      "unmanagedFormBuilder" = @DI\Inject("kgc.elastic.rdv.id.form_builder"),
     * })
     */
    public function __construct(RdvIDSearchFormBuilder $unmanagedFormBuilder)
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
