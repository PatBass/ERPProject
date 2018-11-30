<?php
// src/KGC/RdvBundle/Form/RDVType.php

namespace KGC\UserBundle\Form;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use KGC\ClientBundle\Form\ClientType;
use KGC\ClientBundle\Form\IdAstroType;
use KGC\Bundle\SharedBundle\Repository\WebsiteRepository;
use KGC\ClientBundle\Service\HistoriqueManager;
use KGC\CommonBundle\Form\CommonAbstractType;
use KGC\DashboardBundle\Form\PhoneType;
use KGC\RdvBundle\Entity\Encaissement;
use KGC\RdvBundle\Entity\RDV;
use KGC\RdvBundle\Entity\Source;
use KGC\RdvBundle\Form\DataTransformer\HiddenEntityTransformer;
use KGC\RdvBundle\Repository\CodePromoRepository;
use KGC\RdvBundle\Repository\DossierRepository;
use KGC\RdvBundle\Repository\EtiquetteRepository;
use KGC\RdvBundle\Repository\SupportRepository;
use KGC\RdvBundle\Repository\TPERepository;
use KGC\UserBundle\Entity\Utilisateur;
use KGC\UserBundle\Entity\Profil;
use KGC\UserBundle\Repository\VoyantRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Constructeur de formulaire pour entité prospect.
 *
 * @category Form
 *
 * @author Nicolas Mendez <nicolas.kgcom@gmail.com>
 */
class ProspectType extends CommonAbstractType
{
    /**
     * @var array
     */
    protected $edit_params;

    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var \KGC\UserBundle\Entity\Utilisateur
     */
    protected $user;

    protected $field_defs = array();
    protected $options = array();

    /**
     * @param $editParams
     */
    protected function setEditParams($editParams)
    {
        $this->edit_params = $editParams;
    }

    /**
     * @param $edit_params
     * @param EntityManager $em
     */
    public function __construct(Utilisateur $user, $edit_params, EntityManager $em, array $options)
    {
        $this->user = $user;
        $this->edit_params = $edit_params;
        $this->em = $em;
        $this->options = $options;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currentProfils = $this->user->getProfils();
        $this->field_defs = array(
            'website' => array(
                'type' => 'entity',
                'options' => [
                    'class' => 'KGCSharedBundle:Website',
                    'property' => 'libelle',
                    'query_builder' => function (WebsiteRepository $web_rep) {
                        return $web_rep->findIsChatQB(false, true);
                    },
                    'data' => !empty($this->options['website']) ? $this->options['website'] : null,
                    'empty_value' => '...',
                    'required' => true,
                    'mapped' => false,
                    'attr' => ['class' => 'js-website-select'],
                ],
            ),
            'source' => array(
                'type' => 'entity',
                'options' => [
                    'class' => 'KGCRdvBundle:Source',
                    'required' => false,
                    'empty_value' => '...',
                    'data' => !empty($this->options['source']) ? $this->options['source'] : null,
                    'property' => 'label',
                    'mapped' => false,
                    'attr' => ['class' => 'js-source-select'],
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('s');
                    },
                ],
            ),
            'support' => array(
                'type' => 'entity',
                'options' => [
                    'class' => 'KGCRdvBundle:Support',
                    'property' => 'libelle',
                    'required' => false,
                    'data' => !empty($this->options['support']) ? $this->options['support'] : null,
                    'query_builder' => function (SupportRepository $support_rep) use ($currentProfils) {
                        return $support_rep->findByProfilesQB($currentProfils, true);
                    },
                    'mapped' => false,
                    'attr' => ['class' => 'chosen-select'],
                ],
            ),
            'codepromo' => array(
                'type' => 'entity',
                'options' => [
                    'class' => 'KGCRdvBundle:CodePromo',
                    'property' => 'code',
                    'data' => !empty($this->options['codePromo']) ? $this->options['codePromo'] : null,
                    'query_builder' => function (CodePromoRepository $cpm_rep) {
                        return $cpm_rep->findAllQB(true);
                    },
                    'required' => false,
                    'mapped' => false,
                    'attr' => ['class' => 'chosen-select'],
                ],
            ),
            'voyant' => array(
                'type' => 'entity',
                'options' => [
                    'class' => 'KGCUserBundle:Voyant',
                    'property' => 'nom',
                    'data' => !empty($this->options['voyant']) ? $this->options['voyant'] : null,
                    'query_builder' => function (VoyantRepository $voyant_rep) {
                        return $voyant_rep->findAllQB(true);
                    },
                    'required' => false,
                    'mapped' => false,
                    'attr' => ['class' => 'chosen-select'],
                ],
            ),
            'formurl' => array(
                'type' => 'entity',
                'options' => [
                    'class' => 'KGCRdvBundle:FormUrl',
                    'property' => 'label',
                    'required' => false,
                    'mapped' => false,
                    'empty_value' => '',
                    'data' => !empty($this->options['formurl']) ? $this->options['formurl'] : null,
                    'js_dependant_select' => [
                        'depends_on' => 'website;source',
                        'reference_value' => ['websiteId', 'sourceId'],
                    ],
                    'attr' => array(
                        'class' => 'chosen-select',
                    ),
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('u')->addOrderBy('u.label');
                    },
                ],
            ),
            'state' => array(
                'type' => 'entity',
                'options' => [
                    'class' => 'KGCSharedBundle:LandingState',
                    'property' => 'name',
                    'required' => false,
                    'empty_value' => '...',
                    'data' => !empty($this->options['state']) ? $this->options['state'] : null,
                    'attr' => array(
                        'class' => 'js-state-select',
                    ),
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('s')->addOrderBy('s.id');
                    },
                ],
            ),
        );
        $this->addFormfromDefArray($builder, $this->field_defs);
        $builder
            ->add('myastroId', 'text')
            ->add('firstname', 'text')
            ->add('gender', 'choice', array(
                'choices' => ['M' => 'Homme', 'F' => 'Femme'],
                'empty_value' => '',
                'required' => true,
            ))
            ->add('birthday', 'date', array(
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'input-mask' => true,
                'attr' => ['class' => 'date-picker'],
            ))
            ->add('createdAt', 'date', array(
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'input-mask' => true,
            ))
            ->add('email', 'email', array('required' => false))
            ->add('phone', new PhoneType())
            ->add('country', 'text')
            ->add('myastroGclid', 'text', array(
                'required' => false,
                'attr' => array(
                    'class' => 'gclid',
                    'placeholder' => 'GClid',
                ),
            ))
            ->add('myastroSource', 'text')
            ->add('myastroUrl', 'text')
            ->add('myastroPromoCode', 'text')
            ->add('questionSubject', 'text')
            ->add('questionContent', 'text')
            ->add('questionText', 'text')
            ->add('spouseName', 'text')
            ->add('spouseBirthday', 'date', array(
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'input-mask' => true,
                'attr' => ['class' => 'date-picker'],
            ))
            ->add('spouseSign', 'text')
            ->add('dateState', 'datetime', array(
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy HH:mm',
                'required' => false,
                'attr' => ['class' => 'date-picker-hours'],
                'with_seconds' => false,
            ));
        /* --------------------------------- */
        /* --  TRAITEMENT DES/ACTIVATION  -- */
        /* --------------------------------- */
        if ($options['prospect']) {
            $this->enableEditFields($builder);
        }
        /* ----------------------------- */
        /* --   CHAMPS FONCTIONNELS   -- */
        /* ----------------------------- */
        $builder
            ->add('fermeture', 'checkbox', array(
                'mapped' => false,
                'required' => false,
                'checked_label_style' => false,
            ));
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\Bundle\SharedBundle\Entity\LandingUser',
            'cascade_validation' => true,
            'prospect' => true,
            'validation_groups' => array('Default'),
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_userbundle_prospect';
    }

    /**
     * enableEditFields : permet la modification des champs de consultaion selon edit_params.
     *
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     */
    private function enableEditFields(FormBuilderInterface $builder)
    {
        foreach ($builder->all() as $childformbuilder) {
            $this->recursiveDisableFields($childformbuilder);
        }
        foreach ($this->edit_params as $key => $value) {
            $fields = explode(':', $key);
            foreach ($fields as $field) {
                $field_access = explode('_', $field);
                $form = $builder;
                foreach ($field_access as $nom) {
                    $form = $form->get($nom);
                    $form->setDisabled(false);
                }
                $this->recursiveEnableFields($form); // active tous les enfants du formulaires s'il y en a.
                unset($form);
            }
        }
    }

    /**
     * recurisveDisableFields : désactive récursivement tous les formulaires/champs du builder en paramètre.
     *
     * @param \Symfony\Component\Form\FormBuilderInterface $formBuilder
     */
    protected function recursiveDisableFields(FormBuilderInterface $formBuilder)
    {
        $childs = $formBuilder->all();
        foreach ($childs as $formchildBuilder) {
            $this->recursiveDisableFields($formchildBuilder);
        }
        $formBuilder->setDisabled(true);
        $name = $formBuilder->getName();
        if (isset($this->field_defs[$name]) && $name != 'cartebancaires_selected') {
            $this->field_defs[$name]['options']['disabled'] = true;
        }
    }

    /**
     * recurisveEnableFields : active récursivement tous les formulaires/champs du builder en paramètre.
     *
     * @param \Symfony\Component\Form\FormBuilderInterface $formBuilder
     */
    protected function recursiveEnableFields(FormBuilderInterface $formBuilder)
    {
        $childs = $formBuilder->all();
        foreach ($childs as $formchildBuilder) {
            $this->recursiveEnableFields($formchildBuilder);
        }
        $formBuilder->setDisabled(false);
        $name = $formBuilder->getName();
        if (isset($this->field_defs[$name])) {
            $this->field_defs[$name]['options']['disabled'] = false;
        }
    }
}
