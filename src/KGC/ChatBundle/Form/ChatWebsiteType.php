<?php

namespace KGC\ChatBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use KGC\Bundle\SharedBundle\Repository\WebsiteRepository;

/**
 * Class ChatWebsiteType.
 */
class ChatWebsiteType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('website', 'entity', [
                'class' => 'KGCSharedBundle:Website',
                'choice_label' => 'libelle',
                'query_builder' => function (WebsiteRepository $repo) {
                    return $repo->findIsChatQB(true, true);
                },
                'required' => false,
                'empty_value' => 'Tous les sites'
            ])
        ;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_ChatBundle_website';
    }
}
