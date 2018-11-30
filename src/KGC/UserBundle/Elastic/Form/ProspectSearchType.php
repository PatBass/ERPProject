<?php

namespace KGC\UserBundle\Elastic\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * Class ProspectSearchType.
 *
 * @DI\Service("kgc.elastic.prospect.form")
 * @DI\FormType
 */
class ProspectSearchType extends AbstractType
{
    /**
     * @var ProspectSearchFormBuilder
     */
    protected $unmanagedFormBuilder;

    /**
     * @param ProspectSearchFormBuilder $unmanagedFormBuilder
     *
     * @DI\InjectParams({
     *      "unmanagedFormBuilder" = @DI\Inject("kgc.elastic.prospect.form_builder"),
     * })
     */
    public function __construct(ProspectSearchFormBuilder $unmanagedFormBuilder)
    {
        $this->unmanagedFormBuilder = $unmanagedFormBuilder;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
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
            'data_class' => 'KGC\UserBundle\Elastic\Model\ProspectSearch',
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
