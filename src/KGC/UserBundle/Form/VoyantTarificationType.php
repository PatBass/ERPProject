<?php

// src/KGC/UserBundle/Form/VoyantTarificationType.php


namespace KGC\UserBundle\Form;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use KGC\UserBundle\Entity\Profil;

/**
 * Class VoyantTarificationType.
 *
 * @category Form
 */
class VoyantTarificationType extends AbstractType
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var bool
     */
    protected $isChat;

    /**
     * @param EntityManager $em
     * @param
     */
    public function __construct(EntityManager $em, $isChat)
    {
        $this->em = $em;
        $this->isChat = $isChat;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($isChat = $this->isChat) {
            $builder->add('utilisateur', 'entity', [
                'class' => 'KGCUserBundle:Utilisateur',
                'property' => 'username',
                'required' => false,
                'empty_value' => 'Aucun voyant',
                'query_builder' => function (EntityRepository $er) {
                    return $er->findAllChatPsychics();
                },
                'group_by' => function($utilisateur) {
                    return $utilisateur->getChatType()->getEntitled();
                }
            ])
            ->add('reference', 'text', array(
                'required' => false,
                'attr' => ['placeholder' => 'Référence externe'],
            ));
        } else {
            $builder->add('codeTarification', 'entity', [
                'class' => 'KGCRdvBundle:CodeTarification',
                'property' => 'libelle',
                'required' => false,
                'empty_value' => 'Tarification minute',
            ]);
        }

        $builder
            ->add('nom', 'text', array(
                'required' => true,
            ))
            ->add('website', 'entity', array(
                'class' => 'KGCSharedBundle:Website',
                'property' => 'libelle',
                'required' => $isChat,
                'empty_value' => 'Site de rattachement',
                'query_builder' => function (EntityRepository $er) use ($isChat) {
                    return $er->findIsChatQB($isChat);
                },
            ))
            ->add('sexe', 'choice', array(
                'required' => false,
                'choices' => [0 => 'Homme', 1 => 'Femme'],
                'empty_value' => 'Genre/Sexe',
            ))
            ->add('enabled', 'checkbox', [
                'required' => false,
                'switch_style' => 5,
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                $voyant = $event->getData();

                if ($utilisateur = $voyant->getUtilisateur()) {
                    if ($utilisateur && ($chatType = $utilisateur->getChatType())) {
                        $chatFormula = $this->em->getRepository('KGCChatBundle:ChatFormula')->findOneByWebsite($voyant->getWebsite());
                    }

                    if (empty($chatFormula)) {
                        $form->get('utilisateur')->addError(new FormError('Les voyants du site "'.$voyant->getWebsite()->getLibelle().'" ne supportent pas l\'association avec un utilisateur'));
                    } else if (empty($chatType) || $chatType->getId() != $chatFormula->getChatType()->getId()) {
                        $form->get('utilisateur')->addError(new FormError('Le site "'.$voyant->getWebsite()->getLibelle().'" doit avoir des voyants de type '.$chatFormula->getChatType()->getEntitled()));
                    }
                }
            });
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'KGC\UserBundle\Entity\Voyant',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_userbundle_voyant_tarification';
    }
}
