<?php
/**
 * Created by PhpStorm.
 * User: niko
 * Date: 17/08/2016
 * Time: 11:55
 */

namespace KGC\UserBundle\Elastic\Form;

use Doctrine\ORM\EntityManagerInterface;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ProspectSearchFormBuilder.
 *
 * @DI\Service("kgc.elastic.prospect.form_builder")
 */
class ProspectSearchFormBuilder
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @return array
     */
    protected function getDatetimeConfig()
    {
        return [
            'attr' => ['class' => 'date-picker'],
            'required' => false,
            'widget' => 'single_text',
            'format' => 'dd/MM/yyyy',
            'input-mask' => true,
            'date-picker' => true,
        ];
    }

    /**
     * @param $repositoryClass
     * @param $property
     * @param string $repositoryMethod
     * @param null $arg
     *
     * @return array
     */
    protected function buildManagedChoicesField($repositoryClass, $property, $repositoryMethod = 'findAll', $arg = null)
    {
        $choices = [];
        $repository = $this->entityManager->getRepository($repositoryClass);
        if (null !== $repository) {
            $references = $repository->$repositoryMethod($arg);
            $method = 'get' . ucfirst($property);
            foreach ($references as $r) {
                $choices[$r->$method()] = $r->$method();
            }
        }
        return $choices;
    }

    /**
     * @param EntityManagerInterface $entityManager
     *
     * @DI\InjectParams({
     *      "entityManager" = @DI\Inject("doctrine.orm.entity_manager"),
     * })
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Build the search form.
     * Custom method needed to transform managed entities to simple choices.
     * The need is to serialize and deserialize form to save search.
     *
     * @param FormBuilderInterface $builder
     */
    public function buildUnmanagedForm(FormBuilderInterface $builder)
    {
        $builder
            // Client fields
            ->add('idastro', 'text', [
                'required' => false,
                'label' => 'ID Astro',
            ])
            ->add('phones', 'text', [
                'required' => false,
                'label' => 'Téléphone',
            ])
            ->add('name', 'text', [
                'required' => false,
                'label' => 'Prénom',
            ])
            ->add('mail', 'text', [
                'required' => false,
                'label' => 'Adresse mail',
            ])
            ->add('birthdate', 'date', [
                    'label' => 'Date de naissance',
                ] + $this->getDatetimeConfig())
            ->add('page', 'hidden', [
                'attr' => ['class' => 'js-page-target'],
            ])
            ->add('dateBegin', 'date', [
                    'label' => 'Date d\'inscription',
                ] + $this->getDatetimeConfig());
    }
}
