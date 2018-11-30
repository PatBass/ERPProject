<?php

// src/KGC/UserBundle/Form/ProspectDRIType.php


namespace KGC\UserBundle\Form;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use KGC\Bundle\SharedBundle\Repository\WebsiteRepository;
use KGC\RdvBundle\Repository\CodePromoRepository;
use KGC\RdvBundle\Repository\SupportRepository;
use KGC\UserBundle\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Constructeur de formulaire pour DRI.
 *
 * @category Form
 *
 * @author Nicolas MENDEZ <nicolas.kgcom@gmail.com>
 */
class ProspectDRIType extends AbstractType
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var \KGC\UserBundle\Entity\Utilisateur
     */
    protected $user;

    /**
     * @param EntityManagerInterface $entityManager
     *
     * @DI\InjectParams({
     *      "entityManager" = @DI\Inject("doctrine.orm.entity_manager"),
     * })
     */
    public function __construct(Utilisateur $user, EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->user = $user;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currentProfils = $this->user->getProfils();
        $builder
            ->add('page', 'hidden', [
                'attr' => ['class' => 'js-page-target'],
            ])
            ->add('begin', 'date', [
                'label' => 'Début',
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'date-picker' => true,
                'input-mask' => true,
                'attr' => ['placeholder' => 'Début'],
                'required' => false,
            ])
            ->add('end', 'date', [
                'label' => 'Fin',
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'date-picker' => true,
                'input-mask' => true,
                'attr' => ['placeholder' => 'Fin'],
                'required' =>  false,
            ])
            ->add('firstName', 'text', [
                'required' => false,
                'label' => 'Prénom',
                'attr' => ['placeholder' => 'Prénom'],
            ])
            ->add('website', 'entity', [
                'label' => 'Site web',
                'class' => 'KGCSharedBundle:Website',
                'property' => 'libelle',
                'query_builder' => function (WebsiteRepository $web_rep) {
                    return $web_rep->findIsChatQB(false, true);
                },
                'empty_value' => '...',
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => 'js-website-select'],
            ])
            ->add('sourceConsult', 'entity', [
                'label' => 'Source',
                'class' => 'KGCRdvBundle:Source',
                'required' => false,
                'empty_value' => '...',
                'property' => 'label',
                'mapped' => false,
                'attr' => ['class' => 'js-source-select chosen-select'],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('s');
                },
            ])
            ->add('support', 'entity', [
                'label' => 'Support',
                'required' => false,
                'class' => 'KGCRdvBundle:Support',
                'property' => 'libelle',
                'query_builder' => function (SupportRepository $support_rep) use ($currentProfils) {
                    return $support_rep->findByProfilesQB($currentProfils, true);
                },
                'empty_value' => ' ',
                'attr' => ['class' => 'chosen-select'],
            ])
            ->add('formurl', 'entity', [
                'required' => false,
                'label' => 'Url',
                'class' => 'KGCRdvBundle:FormUrl',
                'property' => 'label',
                'mapped' => false,
                'empty_value' => '',
                'js_dependant_select' => [
                    'depends_on' => 'website;sourceConsult',
                    'reference_value' => ['websiteId', 'sourceId'],
                ],
                'attr' => array(
                    'class' => 'chosen-select',
                ),
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')->addOrderBy('u.label');
                },
            ])
            ->add('codePromo', 'entity', [
                'required' => false,
                'label' => 'Code promo',
                'class' => 'KGCRdvBundle:CodePromo',
                'property' => 'code',
                'query_builder' => function (CodePromoRepository $cpm_rep) {
                    return $cpm_rep->findAllQB(true);
                },
                'mapped' => false,
                'attr' => ['class' => 'chosen-select'],
            ])
            ->add('state', 'entity', [
                'label' => 'Etat',
                'class' => 'KGCSharedBundle:LandingState',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('s');
                },
                'property' => 'name',
                'mapped' => false,
                'required' => false,
            ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_userbundle_dri';
    }
}
