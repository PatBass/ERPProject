<?php
namespace KGC\ClientBundle\Form;

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
use KGC\RdvBundle\Form\AdresseType;
use KGC\RdvBundle\Form\CarteBancaireType;
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
 * Constructeur de formulaire pour entité client.
 *
 * @category Form
 *
 * @author Nicolas Mendez <nicolas.kgcom@gmail.com>
 */
class ClientCardType extends CommonAbstractType
{
    /**
     * @var \KGC\UserBundle\Entity\Utilisateur
     */
    protected $user;
    /**
     * @var array
     */
    protected $edit_params;

    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $em;

    protected $field_defs = array();

    protected $cbMasked = false;
    protected $decrypt = false;

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
    public function __construct(Utilisateur $user, $edit_params, EntityManager $em, $cbMasked = false, $decrypt = false)
    {
        $this->user = $user;
        $this->edit_params = $edit_params;
        $this->em = $em;
        $this->cbMasked = $cbMasked;
        $this->decrypt = $decrypt;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->field_defs = array(
            'cartebancaires' => [
                'type' => 'collection',
                'options' => [
                    'type' => new CarteBancaireType($this->cbMasked, $this->decrypt),
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                    'required' => false,
                ]
            ]
        );
        $this->addFormfromDefArray($builder, $this->field_defs);
        $builder
            ->add('cartebancaires_selected', 'hidden', ['mapped' => false, 'empty_data' => '0'])
            ->add('nom', 'text')
            ->add('prenom', 'text');
        if($this->user->isAllowedToMakeCall()) {
            if(!is_null($this->user->getPoste())) {
                $builder
                    ->add('numtel1', new PhoneType(), ['required' => false, 'attr' => ['btnCall' => true]])
                    ->add('numtel2', new PhoneType(), ['required' => false, 'attr' => ['btnCall' => true]]);
            }
        } else {
            $builder
                ->add('numtel1', new PhoneType())
                ->add('numtel2', new PhoneType(), ['required' => false]);
        }
        $builder
            ->add('adresse', new AdresseType())
            ->add('genre', 'choice', array(
                'choices' => ['M' => 'Homme', 'F' => 'Femme'],
                'empty_value' => '',
                'required' => true,
            ))
            ->add('dateNaissance', 'date', array(
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'input-mask' => true,
            ))
            ->add('mail', 'email', array('required' => false));

        /* --------------------------------- */
        /* --  TRAITEMENT DES/ACTIVATION  -- */
        /* --------------------------------- */
        if ($options['client']) {
            $this->enableEditFields($builder);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\Bundle\SharedBundle\Entity\Client',
            'cascade_validation' => true,
            'client' => true,
            'validation_groups' => array('Default'),
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_clientbundle_client';
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
