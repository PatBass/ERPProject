<?php

namespace KGC\RdvBundle\Elastic\Form;

use Doctrine\ORM\EntityManagerInterface;
use JMS\DiExtraBundle\Annotation as DI;
use KGC\RdvBundle\Elastic\Model\RdvSearch;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class RdvSearchFormBuilder.
 *
 * @DI\Service("kgc.elastic.rdv.form_builder")
 */
class RdvSearchFormBuilder
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
     * @param null   $arg
     *
     * @return array
     */
    protected function buildManagedChoicesField($repositoryClass, $property, $repositoryMethod = 'findAll', $arg = null)
    {
        $choices = [];
        $repository = $this->entityManager->getRepository($repositoryClass);
        if (null !== $repository) {
            $references = $repository->$repositoryMethod($arg);
            $method = 'get'.ucfirst($property);
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
            ->add('id', 'text', [
                'required' => false,
                'label' => 'ID consultation',
            ])
            ->add('idastro', 'text', [
                    'required' => false,
                    'label' => 'ID Astro',
                ])
            ->add('phones', 'text', [
                    'required' => false,
                    'label' => 'Téléphones',
                ])
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
            // Consultation fields
            ->add('dateBegin', 'date', [
                    'label' => 'Date de début',
                ] + $this->getDatetimeConfig())
            ->add('dateEnd', 'date', [
                    'label' => 'Date de fin',
                ] + $this->getDatetimeConfig())
            ->add('dateType', 'choice', [
                    'label' => 'Type de date',
                    'choices' => RdvSearch::getDateTypeChoices(),
                    'empty_value' => '',
                    'required' => false,
                ])
            ->add('forfaits', 'choice', [
                    'label' => 'Forfaits',
                    'choices' => $this->buildManagedChoicesField(
                        'KGCClientBundle:Option', 'label', 'findAllByType', 'plan'
                    ),
                    'empty_value' => '',
                    'required' => false,
                    'multiple' => true,
                    'attr' => array(
                        'data-placeholder' => "Forfaits",
                        'class' => 'chosen-select tag-input-style'
                    )
                ])
            ->add('timeMin', 'text', [
                    'label' => 'Durée >=',
                    'required' => false,
                ])
            ->add('timeMax', 'text', [
                    'label' => 'Durée <=',
                    'required' => false,
                ])
            ->add('amountMin', 'text', [
                    'label' => 'Montant >=',
                    'required' => false,
                ])
            ->add('amountMax', 'text', [
                    'label' => 'Montant <=',
                    'required' => false,
                ])
            ->add('tpes', 'choice', [
                    'label' => 'TPE de la sécurisation',
                    'choices' => $this->buildManagedChoicesField(
                        'KGCRdvBundle:TPE', 'libelle'
                    ),
                    'empty_value' => '',
                    'required' => false,
                    'multiple' => true,
                    'attr' => array(
                        'data-placeholder' => "TPEs de la sécurisation",
                        'class' => 'chosen-select tag-input-style'
                    )
                ])

            ->add('websites', 'choice', [
                    'label' => 'Site web',
                    'choices' => $this->buildManagedChoicesField(
                        'KGCSharedBundle:Website', 'libelle'
                    ),
                    'empty_value' => '',
                    'required' => false,
                    'multiple' => true,
                    'attr' => array(
                        'data-placeholder' => "Site web",
                        'class' => 'chosen-select tag-input-style'
                    )
                ])
            ->add('form_urls', 'choice', [
                    'label' => 'Url',
                    'choices' => $this->buildManagedChoicesField(
                        'KGCRdvBundle:FormUrl', 'label'
                    ),
                    'empty_value' => '',
                    'required' => false,
                    'multiple' => true,
                    'attr' => array(
                        'data-placeholder' => "Url",
                        'class' => 'chosen-select tag-input-style'
                    )
                ])
            ->add('card', 'text', [
                'required' => false,
                'label' => 'Carte bancaire',
                'attr' => ['class' => 'js-check-luhn'],
            ])
            ->add('tags', 'choice', [
                'label' => 'Etiquette',
                'choices' => $this->buildManagedChoicesField(
                    'KGCRdvBundle:Etiquette', 'libelle'
                ),
                'empty_value' => '',
                'required' => false,
                    'multiple' => true,
                    'attr' => array(
                        'data-placeholder' => "Tags",
                        'class' => 'chosen-select tag-input-style'
                    )
            ])
            ->add('classements', 'choice', [
                'label' => 'Classement',
                'choices' => $this->buildManagedChoicesField(
                    'KGCRdvBundle:Classement', 'libelle'
                ),
                'empty_value' => '',
                'required' => false,
                'multiple' => true,
                'attr' => array(
                    'data-placeholder' => "Classements",
                    'class' => 'chosen-select tag-input-style'
                )
            ])
            ->add('psychics', 'choice', [
                    'label' => 'Voyant',
                    'choices' => $this->buildManagedChoicesField(
                        'KGCUserBundle:Voyant', 'nom'
                    ),
                    'empty_value' => '',
                    'required' => false,
                    'multiple' => true,
                    'attr' => array(
                        'data-placeholder' => "Voyants",
                        'class' => 'chosen-select tag-input-style'
                    )
                ])

            ->add('consultants', 'choice', [
                    'label' => 'Consultant',
                    'choices' => $this->buildManagedChoicesField(
                        'KGCUserBundle:Utilisateur', 'username', 'findAllVoyants'
                    ),
                    'empty_value' => '',
                    'required' => false,
                    'multiple' => true,
                    'attr' => array(
                        'data-placeholder' => "Consultants",
                        'class' => 'chosen-select tag-input-style'
                    )
                ])
            ->add('states', 'choice', [
                'label' => 'Etat',
                'choices' => $this->buildManagedChoicesField(
                    'KGCRdvBundle:Etat', 'libelle'
                ),
                'empty_value' => '',
                'required' => false,
                'multiple' => true,
                'attr' => array(
                    'data-placeholder' => "Etats",
                    'class' => 'chosen-select tag-input-style'
                )
            ])
            ->add('sources', 'choice', [
                'label' => 'Source',
                'choices' => $this->buildManagedChoicesField(
                    'KGCRdvBundle:Source', 'label'
                ),
                'empty_value' => '',
                'required' => false,
                'multiple' => true,
                'attr' => array(
                    'data-placeholder' => "Sources",
                    'class' => 'chosen-select tag-input-style'
                )
            ])
            ->add('supports', 'choice', [
                    'label' => 'Support',
                    'choices' => $this->buildManagedChoicesField(
                        'KGCRdvBundle:Support', 'libelle', 'findAll', true
                    ),
                    'empty_value' => '',
                    'required' => false,
                    'multiple' => true,
                    'attr' => array(
                        'data-placeholder' => "Supports",
                        'class' => 'chosen-select tag-input-style'
                    )
                ])
            ->add('codepromos', 'choice', [
                    'label' => 'Code promo',
                    'choices' => $this->buildManagedChoicesField(
                        'KGCRdvBundle:CodePromo', 'code'
                    ),
                    'empty_value' => '',
                    'required' => false,
                    'multiple' => true,
                    'attr' => array(
                        'data-placeholder' => "Code promos",
                        'class' => 'chosen-select tag-input-style'
                    )
                ])
            ->add('orderBy', 'choice', [
                    'attr' => ['class' => 'no-reset', 'data-reset-value' => RdvSearch::ORDER_BY_DEFAULT],
                    'label' => 'Trier par',
                    'choices' => [
                        '_score' => 'Pertinence',
                        'dateConsultationES' => 'Date de consultation',
                        'client.nomPrenom.raw' => 'Nom',
                        'tarification.temps' => 'Durée',
                        'tarification.montant_total' => 'Montant',
                    ],
                    'required' => true,
                ])
            ->add('sortDirection', 'choice', [
                    'attr' => ['class' => 'no-reset', 'data-reset-value' => RdvSearch::SORT_DIRECTION_DEFAULT],
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
}
