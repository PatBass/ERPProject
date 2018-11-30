<?php

namespace KGC\ClientBundle\Elastic\Form;

use KGC\ChatBundle\Entity\ChatFormulaRate;
use KGC\ClientBundle\Elastic\Model\ClientSearch;
use KGC\UserBundle\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * Class ClientSearchType.
 *
 * @DI\Service("kgc.elastic.client.form")
 * @DI\FormType
 */
class ClientSearchType extends AbstractType
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
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('name', 'text', [
            'required' => false,
            'label' => 'Nom/Prénom',
        ])
        ->add('mail', 'text', [
            'required' => false,
            'label' => 'Adresse mail',
        ])
        ->add('birthdate', 'date', [
                'label' => 'Date de naissance',
            ] + $this->getDatetimeConfig())
        ->add('origin', 'choice', [
            'label' => 'Site web',
            'choices' => $this->entityManager->getRepository('KGCSharedBundle:Website')->getWebsitesForSearch(),
            'empty_value' => '',
            'required' => false,
        ])
        ->add('source', 'choice', [
            'label' => 'Source',
            'choices' => $this->entityManager->getRepository('KGCSharedBundle:Source')->getSourcesForSearch(),
            'empty_value' => '',
            'required' => false,
        ])
        ->add('dateCreationBegin', 'date', [
           'label' => 'Crée après le'
            ] + $this->getDatetimeConfig())
        ->add('dateCreationEnd', 'date', [
            'label' => 'et avant le'
            ] + $this->getDatetimeConfig())
        ->add('psychic', 'choice', [
            'label' => 'Voyant',
            'choices' => $this->entityManager->getRepository('KGCUserBundle:Utilisateur')->getClientPsychicsForSearch(),
            'empty_value' => '',
            'required' => false,
        ])
        ->add('formula', 'choice', [
            'label' => 'Formule souscrite',
            'choices' => $this->entityManager->getRepository('KGCChatBundle:ChatFormulaRate')->getEnabledChatFormulaRatesForSearch(),
            'empty_value' => '',
            'required' => false,
        ])
        ->add('orderBy', 'choice', [
            'attr' => ['class' => 'no-reset', 'data-reset-value' => ClientSearch::ORDER_BY_DEFAULT],
            'label' => 'Trier par',
            'choices' => [
                '_score' => 'Pertinence',
                'nomPrenom.raw' => 'Nom',
                'dateCreationES' => 'Date de création'
            ],
            'required' => true,
        ])
        ->add('sortDirection', 'choice', [
            'attr' => ['class' => 'no-reset', 'data-reset-value' => ClientSearch::SORT_DIRECTION_DEFAULT],
            'label' => 'Ordre',
            'choices' => [
                'asc' => 'Croissant',
                'desc' => 'Décroissant',
            ],
            'required' => true,
        ])

        ->add('page', 'hidden', [
            'attr' => ['class' => 'js-page-target'],
        ])
        ->add('pageRange', 'choice', [
            'attr' => ['class' => 'no-reset'],
            'label' => 'Résultats par page',
            'choices' => [
                10 => 10,
                20 => 20,
                50 => 50,
                100 => 100,
            ],
            'required' => true,
        ])
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\ClientBundle\Elastic\Model\ClientSearch',
            'csrf_protection' => false,
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'elastic_search_client';
    }
}
