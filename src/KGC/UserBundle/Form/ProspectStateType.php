<?php
// src/KGC/UserBundle/Form/ProspectStateType.php

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
 * Constructeur de formulaire d'état pour entité prospect.
 *
 * @category Form
 *
 * @author Nicolas Mendez <nicolas.kgcom@gmail.com>
 */
class ProspectStateType extends CommonAbstractType
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $em;

    protected $field_defs = array();
    protected $options = array();


    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em, array $options)
    {
        $this->em = $em;
        $this->options = $options;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('state', 'entity', [
                'label' => 'Etat',
                'class' => 'KGCSharedBundle:LandingState',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('s');
                },
                'property' => 'name',
                'mapped' => false,
                'required' => true,
            ])
            ->add('dateState', 'datetime', array(
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy HH:mm',
                'required' => false,
                'attr' => ['class' => 'date-picker-hours'],
                'with_seconds' => false,
            ));
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\Bundle\SharedBundle\Entity\LandingUser',
            'csrf_protection'   => false,
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_userbundle_prospect_state';
    }
}
