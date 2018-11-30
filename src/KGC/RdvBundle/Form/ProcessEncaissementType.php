<?php

namespace KGC\RdvBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Doctrine\ORM\EntityManager;
use Payum\Core\Security\Util\Mask;
use KGC\RdvBundle\Entity\Encaissement;
use KGC\RdvBundle\Entity\Rdv;
use KGC\RdvBundle\Entity\TPE;
use KGC\RdvBundle\Repository\CarteBancaireRepository;

class ProcessEncaissementType extends EncaissementType
{
    /**
     * @var Rdv
     */
    protected $rdv;

    protected $tpeList = [];

    public function __construct(EntityManager $em, Rdv $rdv, $collection = false, $etat = false, $disabled_enc = array(), $no_date = false)
    {
        $this->em = $em;
        $this->rdv = $rdv;

        parent::__construct($collection, $etat, $disabled_enc, $no_date);
    }

    protected function getTpeFieldConf()
    {
        $choices = ['preauth' => 'Pré-autorisation'];

        foreach ($this->em->getRepository('KGCRdvBundle:TPE')->findAllBackofficeQB(true)->getQuery()->getResult() as $tpe) {
            $this->tpeList[$tpe->getId()] = $tpe;
            $choices[$tpe->getId()] = $tpe->getLibelle();
        }

        return [
            'type' => 'choice',
            'options' => [
                'choices' => $choices,
                'empty_value' => 'TPE non renseigné',
                'required' => false,
                'mapped' => false,
                'input_addon' => 'credit-card',
                'attr' => ['class' => 'encaissement-tpe']
            ],
        ];
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $pgTpeAllowed = $this->em->getRepository('KGCRdvBundle:TPE')->getTpeIdsWithPaymentGateway(true);
        $rdv = $this->rdv;

        $builder->add(
            'cartebancaire',
            'entity',
            [
                'class' => 'KGCRdvBundle:CarteBancaire',
                'choice_label' => function($cb) {
                    return $cb->getNom().' - '.Mask::mask($cb->getNumero());
                },
                'query_builder' => function (CarteBancaireRepository $cb_rep) use($rdv) {
                     return $cb_rep
                        ->createQueryBuilder('cb')
                        ->innerJoin('cb.rdvs', 'rdv')
                        ->where('rdv = :rdv')
                        ->orderBy('rdv.id')
                        ->setParameter('rdv', $rdv);
                },
                'attr' => ['class' => 'encaissement-cb'],
                'input_addon' => 'credit-card',
                'required' => true,
                'mapped' => false
            ]
        )->add(
            'payer',
            'submit',
            [
                'attr' => [
                    'class' => 'btn btn-sm btn-danger encaissement-payment',
                    'type' => 'submit',
                    'data-allowed' => implode($pgTpeAllowed, '|')
                ]
            ]
        )->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $encaissement = $event->getData();

            if ($tpe = $encaissement->getTpe()) {
                $event->getForm()->get('tpe')->setData($tpe->getId());
            }
        })->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $encaissement = $event->getData();

            if (array_key_exists($tpeId = $event->getForm()->get('tpe')->getData(), $this->tpeList)) {
                $encaissement->setTpe($this->tpeList[$tpeId]);
            } else {
                $encaissement->setTpe($tpeId);
            }
        });
    }

    protected function updateTpeFieldConf(&$fieldDefs, Encaissement $encaissement)
    {
        foreach ($fieldDefs['tpe']['options']['choices'] as $id => $libelle) {
            if (
                isset($this->tpeList[$id])
                && $this->tpeList[$id]->getDisabledDate() !== null
                && $this->tpeList[$id]->getDisabledDate() <= $encaissement->getDate()
            ) {
                unset($fieldDefs['tpe']['options']['choices'][$id]);
            } else if ($id == 'preauth') {
                $preAuthorization = $encaissement->getConsultation()->getPreAuthorization();

                if (
                    !$preAuthorization
                    || ($preAuthorization->getCapturedAmount() !== null)
                    || ($preAuthorization->getAuthorizedAmount() < $encaissement->getMontant())
                ) {
                    // supprimer le choix pre-authorize si l'encaissement n'est pas éligible pour l'autorisation (pas de pré-auth dispo ou pré-auth trop faible)
                    unset($fieldDefs['tpe']['options']['choices'][$id]);
                } else if (
                    $preAuthorization
                ) {
                    unset($fieldDefs['tpe']['options']['choices'][$preAuthorization->getAuthorizePayment()->getTpe()->getId()]);
                }
            }
        }
    }
}