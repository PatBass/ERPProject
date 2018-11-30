<?php

// src/KGC/RdvBundle/Form/RDVValidationType.php


namespace KGC\RdvBundle\Form;

use Doctrine\ORM\EntityManager;
use KGC\ClientBundle\Service\HistoriqueManager;
use KGC\RdvBundle\Entity\ActionSuivi;
use KGC\RdvBundle\Entity\Support;
use KGC\UserBundle\Entity\Utilisateur;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ExecutionContextInterface;

/**
 * Constructeur de formulaire de validation pour entité RDV.
 * Consultation effectuée par le voyant et validée par les données d'encaissement.
 *
 * Class RDVValidationType
 */
class RDVValidationType extends RDVType
{
    /**
     * @param Utilisateur   $user
     * @param EntityManager $em
     * @param array         $edit_params
     */
    public function __construct(Utilisateur $user, EntityManager $em, HistoriqueManager $historiqueManager, $edit_params = array())
    {
        parent::__construct($user, $edit_params, $em, $historiqueManager);
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);
        $resolver->setDefaults(array(
            'validation_groups' => array('Default', 'facturation'),
        ));
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $this->recursiveEnableFields($builder->get('notes'));
        $this->recursiveEnableFields($builder->get('notesLibres'));
        $builder->add('tarification', new TarificationType(), ['add_type' => true]);
        $this->recursiveEnableFields($builder->get('encaissements'));

        $validateDateSuivi = function ($date, ExecutionContextInterface $context) {
            if ($date instanceof \DateTime) {
                if ($date->format('dmY') == date('dmY')) {
                    $msg = 'Vous ne pouvez pas programmer de suivi le même jour que la consultation.';
                }
                if ($date->format('N') > 5) {
                    $msg = 'Vous ne pouvez pas programmer de suivi le week-end.';
                }

                if (isset($msg)) {
                    $context->addViolation($msg);
                }
            }
        };
        $reminder_config = [
            'required' => false,
            'attr' => ['class' => 'js-original-date'],
            'widget' => 'single_text',
            'format' => 'dd/MM/yyyy HH:mm',
            'constraints' => array(
                new CallBack($validateDateSuivi),
            ),
        ];
        $builder->get('notes')->get('reminder')->add('datetime', 'datetime', $reminder_config);

        // TODO: Demander plus tard s'il faut réactiver une version du check de la durée de consultation

        /*$builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($reminder_config) {
            $form = $event->getForm();
            $data = $event->getData();

            if (empty($data['notes']['recurrent'])) {
                $support = $form->getData()->getSupport()->getIdcode();
                $msg = 'La consultation a duré plus de 30 minutes, vous devez programmer une date de rappel.';
                if (null !== $support) {
                    $msg = 'Dans le cadre dʼun suivi vous devez systématiquement programmer une date de rappel.';
                }

                if ((null === $support && $data['tarification']['temps'] >= 30) || Support::SUIVI_CLIENT === $support) {
                    $reminder_config['constraints'][] = new NotBlank(['message' => $msg]);
                    $form['notes']['reminder']->add('datetime', 'datetime', $reminder_config);
                }

                if ($data['tarification']['temps'] >= 30) {
                    if ($this->historique_manager instanceof HistoriqueManager) {
                        $fields = $this->historique_manager->getFormFieldsBySection([
                            HistoriqueManager::HISTORY_SECTION_NOTES,
                            HistoriqueManager::HISTORY_SECTION_HISTORY,
                        ]);
                        foreach ($fields as $f) {
                            $label = $this->historique_manager->getTypeLabelMapping($f['name']);
                            $f['form']->setLabel($label);
                            $form['notes']->add($f['name'], $f['form'], [
                                'mapped' => false,
                                'constraints' => new NotBlank(['message' => 'La consultation a duré plus de 30 minutes, vous devez remplir la section « '.$label.' » des notes de consultation.']),
                            ]);
                        }
                    }
                }
            }
        });*/

        $builder->get('mainaction')->setData(ActionSuivi::DO_CONSULT);

        // TO handle PAUSE action
        $builder->add('pause', 'submit');

        $this->addAction($builder, array(ActionSuivi::ADD_BILLING => 'facturation'));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kgc_RdvBundle_rdv';
    }

    private function validateDateSuivi($object, ExecutionContextInterface $context)
    {
    }
}
