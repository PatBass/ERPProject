<?php

namespace KGC\RdvBundle\Elastic\Form;

use Doctrine\ORM\EntityManagerInterface;
use JMS\DiExtraBundle\Annotation as DI;
use KGC\RdvBundle\Elastic\Model\RdvSearch;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class RdvIDSearchFormBuilder.
 *
 * @DI\Service("kgc.elastic.rdv.id.form_builder")
 */
class RdvIDSearchFormBuilder
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

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
            ->add('id', 'text', [
                    'required' => false,
                    'label' => 'ID consultation',
                ])
            ->add('name', 'text', [
                'required' => false,
                'label' => 'Nom/Prénom',
            ])
            ->add('mail', 'text', [
                'required' => false,
                'label' => 'Adresse mail',
            ])
            ->add('phones', 'text', [
                'required' => false,
                'label' => 'Téléphones',
            ]);
    }
}
