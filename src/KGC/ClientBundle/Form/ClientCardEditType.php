<?php

namespace KGC\ClientBundle\Form;

use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\Bundle\SharedBundle\Entity\LandingUser;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use KGC\RdvBundle\Entity\ActionSuivi;

/**
 * Constructeur de formulaire d'accès à l'édition des données du client.
 *
 * @category Form
 *
 * @author Nicolas Mendez <nicolas.kgcom@gmail.com>
 */
class ClientCardEditType extends AbstractType
{
    /**
     * @var \KGC\Bundle\SharedBundle\Entity\Client
     */
    private $client;

    /**
     * Constructeur.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $common_fields = [
            'mapped' => false,
            'required' => false,
            'switch_style' => 6,
        ];
        if (!empty($options['fiche'])) {
            $builder
                ->add('cartebancaires', 'checkbox', array_merge($common_fields, array(
                    'value' => ActionSuivi::UPDATE_BANKDETAILS,
                    'enable_fields' => 'kgc_RdvBundle_rdv_cartebancaire;add_cartebancaire',
                )))
                ->add('prenom', 'checkbox', array_merge($common_fields, array(
                    'enable_fields' => 'kgc_clientbundle_client_prenom',
                )))
                ->add('nom', 'checkbox', array_merge($common_fields, array(
                    'enable_fields' => 'kgc_clientbundle_client_nom',
                )))
                ->add('genre', 'checkbox', array_merge($common_fields, array(
                    'enable_fields' => 'kgc_clientbundle_client_genre',
                )))
                ->add('dateNaissance', 'checkbox', array_merge($common_fields, array(
                    'enable_fields' => 'kgc_clientbundle_client_dateNaissance',
                )))
                ->add('numtel1', 'checkbox', array_merge($common_fields, array(
                    'enable_fields' => 'kgc_clientbundle_client_numtel1',
                )))
                ->add('numtel2', 'checkbox', array_merge($common_fields, array(
                    'enable_fields' => 'kgc_clientbundle_client_numtel2',
                )))
                ->add('adresse', 'checkbox', array_merge($common_fields, array(
                    'enable_fields' => 'kgc_clientbundle_client_adresse',
                )))
                ->add('mail', 'checkbox', array_merge($common_fields, array(
                    'enable_fields' => 'kgc_clientbundle_client_mail',
                )));
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array('fiche' => true));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_clientbundle_clientedit';
    }
}
