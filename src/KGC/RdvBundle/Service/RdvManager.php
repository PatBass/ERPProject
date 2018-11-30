<?php
// src/KGC/RdvBundle/Service/RdvManager.php

namespace KGC\RdvBundle\Service;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Query;
use JMS\DiExtraBundle\Annotation as DI;
use KGC\ClientBundle\Entity\Historique;
use KGC\ClientBundle\Entity\Mail;
use KGC\ClientBundle\Entity\SmsSent;
use KGC\ClientBundle\Service\SmsService;
use KGC\ClientBundle\Transformer\SmsTransformer;
use KGC\CommonBundle\Mailer\Mailer;
use KGC\CommonBundle\Mailer\TwigSwiftMailer;
use KGC\PaymentBundle\Entity\Authorization;
use KGC\PaymentBundle\Entity\Payment;
use KGC\PaymentBundle\Exception\Payment\PaymentFailedException;
use KGC\RdvBundle\Entity\ActionSuivi;
use KGC\RdvBundle\Entity\CarteBancaire;
use KGC\RdvBundle\Entity\Dossier;
use KGC\RdvBundle\Entity\Encaissement;
use KGC\RdvBundle\Entity\EnvoiProduit;
use KGC\RdvBundle\Entity\Etat;
use KGC\RdvBundle\Entity\Etiquette;
use KGC\RdvBundle\Entity\Forfait;
use KGC\RdvBundle\Entity\MoyenPaiement;
use KGC\RdvBundle\Entity\RDV;
use KGC\RdvBundle\Entity\Tiroir;
use KGC\RdvBundle\Entity\TPE;
use KGC\RdvBundle\Events\RDVActionEvent;
use KGC\RdvBundle\Events\RDVEvents;
use KGC\RdvBundle\Lib\Facture;
use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\Bundle\SharedBundle\Model\CreditCard;
use Payum\Core\Request\GetHumanStatus;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

/**
 * @DI\Service("kgc.rdv.manager")
 */
class RdvManager
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ObjectManager
     */
    protected $entityManager;

    /**
     * @var KGC\RdvBundle\Service\SuiviRdvManager
     */
    protected $suiviRdvManager;

    /**
     * @var Mailer
     */
    protected $mailer;

    /**
     * @var SmsService
     */
    protected $smsApi;

    /**
     * @var SmsTransformer
     */
    protected $smsTransformer;

    /**
     * @var TwigSwiftMailer
     */
    protected $secondMailer;

    /**
     * @var Facture
     */
    protected $billingService;

    /**
     * @var PaymentManager
     */
    protected $paymentManager;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @param RequestStack    $request_stack
     * @param ObjectManager   $entityManager
     * @param SuiviRdvManager $suiviRdvManager
     * @param Mailer          $mailer
     * @param Facture         $billingService
     * @param PaymentManager  $paymentManager
     *
     * @DI\InjectParams({
     *     "request"         = @DI\Inject("request_stack"),
     *     "entityManager"   = @DI\Inject("doctrine.orm.entity_manager"),
     *     "suiviRdvManager" = @DI\Inject("kgc.suivirdv.manager"),
     *     "mailer"          = @DI\Inject("kgc.common.mailer"),
     *     "secondMailer"    = @DI\Inject("kgc.common.twig_swift_mailer"),
     *     "billingService"  = @DI\Inject("kgc.billing.service"),
     *     "paymentManager"  = @DI\Inject("kgc.rdv.payment_manager"),
     *     "smsApi"  = @DI\Inject("kgc.client.sms.service"),
     *     "smsTransformer"  = @DI\Inject("kgc.client.sms.transformer"),
     *     "session"         = @DI\Inject("session"),
     * })
     */
    public function __construct(
        RequestStack $request_stack,
        ObjectManager $entityManager,
        SuiviRdvManager $suiviRdvManager,
        Mailer $mailer,
        TwigSwiftMailer $secondMailer,
        Facture $billingService,
        PaymentManager $paymentManager,
        SmsService $smsApi,
        SmsTransformer $smsTransformer,
        Session $session
    ) {
        $this->request = $request_stack->getCurrentRequest();
        $this->entityManager = $entityManager;
        $this->suiviRdvManager = $suiviRdvManager;
        $this->mailer = $mailer;
        $this->secondMailer = $secondMailer;
        $this->billingService = $billingService;
        $this->paymentManager = $paymentManager;
        $this->session = $session;
        $this->smsApi = $smsApi;
        $this->smsTransformer = $smsTransformer;
    }

    /**
     * @param \KGC\RdvBundle\Events\RDVActionEvent $event
     *
     * @DI\Observe(RDVEvents::onAjoutConsult)
     */
    public function ajout_consult(RDVActionEvent $event)
    {
        $form = $event->getForm();
        $rdv = $event->getRdv();

        if ($form->has('new_card_send_choice') && $send = $form->get('new_card_send_choice')->getData()) {
            if ($hash = $rdv->getNewCardHash()) {
                $rdv->setNewCardHashCreatedAt(new \DateTime);
            } else {
                $hash = $rdv->generateNewCardHash()->getNewCardHash();
            }

            foreach ($send as $method) {
                switch ($method) {
                    case 'mail':
                        try {
                            $this->secondMailer->sendNewCardHashSuccessEmailMessage($rdv);
                        } catch (\Exception $e) {}
                        break;
                    case 'sms':
                        $sms = $this->entityManager->getRepository('KGCClientBundle:Sms')->findOneByType(1);
                        if($sms) {
                            $this->smsTransformer->transform('', $sms, $rdv->getId(), $rdv);
                            $sentSms = new SmsSent();
                            $sentSms->setSms($sms);
                            $sentSms->setText($sms->getText());
                            $sentSms->setPhone($rdv->getNumtel1());
                            $client = $rdv->getClient();
                            $user = $rdv->getProprio();
                            $this->suiviRdvManager->addAction(ActionSuivi::SEND_SMS);

                            $result = $this->smsApi->sendSms($sentSms, $rdv->getWebsite()->getId());

                            $h = new Historique();
                            $h->setBackendType(Historique::BACKEND_TYPE_SMS);
                            $h->setType(Historique::TYPE_SMS);
                            $h->setClient($client);
                            $h->setRdv($rdv);
                            $h->setSms($sentSms);
                            $h->setConsultant($user);

                            $this->entityManager->persist($h);
                            $this->entityManager->persist($sentSms);
                        }
                        break;
                }
            }
        }

        $event->setRDV($rdv);
    }

    /**
     * @param \KGC\RdvBundle\Events\RDVActionEvent $event
     *
     * @DI\Observe(RDVEvents::onCancel)
     */
    public function cancel_consult(RDVActionEvent $event)
    {
        $this->suiviRdvManager->addAction(ActionSuivi::CANCEL_CONSULT);

        $rdv = $event->getRDV();
        $form = $event->getForm();

        $rdv->setClassement($classement = $form['dossier_annulation']->getData());

        $this->setRDVEtat($rdv, Etat::CANCELLED);
        $rdv->setConsultation(false);

        // cancel pre-auth if rdv cancelled for NVP
        if (
            $classement instanceof Dossier &&
            $classement->getIdcode() == Dossier::NVP
        ) {
            try {
                $this->checkForPreAuthCancel($rdv);
            } catch (PaymentFailedException $e) {
                $this->session->getFlashBag()->add('error#remove', "Impossible d'annuler la pré-authorisation :--".$e->getCode().' - '.$e->getMessage());
            }
        }

        $event->setRDV($rdv);
    }

    /**
     * @return GetHumanStatus
     */
    public function cancelPreAuthorization(Rdv $rdv)
    {
        if (
            ($authorization = $rdv->getPreAuthorization()) &&
            $authorization->getCapturePayment() === null
        ) {
            $status = $this->paymentManager->cancelAuthorization($authorization);

            if ($status && $status->isCanceled()) {
                $etat = $rdv->getEtat()->getIdcode();

                if (in_array($etat, [Etat::ADDED, Etat::CANCELLED, Etat::CONFIRMED]) && $rdv->getMiserelation() === null) {
                    $rdv->setSecurisation(Rdv::SECU_PENDING);

                    if ($etat == Etat::CONFIRMED) {
                        $this->setRDVEtat($rdv, Etat::ADDED);
                    }
                }

                $this->entityManager->persist($rdv);
                $this->entityManager->flush($rdv);
            }

            return $status;
        }

        return null;
    }

    protected function checkForPreAuthCancel(Rdv $rdv)
    {
        if (
            ($authorization = $rdv->getPreAuthorization()) &&
            $authorization->getCapturePayment() === null
        ) {
            $tpe = $authorization->getTpe();
            if (!$tpe->isCancellable()) {
                $moyenPaiement = $this->entityManager->getRepository('KGCRdvBundle:MoyenPaiement')
                    ->findOneByIdcode(MoyenPaiement::DEBIT_CARD);

                $encaissement = (new Encaissement)
                    ->setMontant($amount = 1)
                    ->setDate(new \DateTime)
                    ->setConsultation($rdv)
                    ->setMoyenPaiement($moyenPaiement)
                    ->setTpe($tpe)
                    ->setPsychicAsso(false);

                $rdv->addEncaissements($encaissement);

                $status = $this->paymentManager->captureAuthorization($authorization, $amount);

                $encaissement->setPayment($status->getFirstModel());

                if ($status->isCaptured()) {
                    $encaissement->setEtat(Encaissement::DONE);
                } else {
                    $encaissement->setEtat(Encaissement::DENIED);

                    $authorization->setCapturePayment($status->getFirstModel());
                    $authorization->setCapturedAmount(0);
                }
            }

        }
    }

    /**
     * @param \KGC\RdvBundle\Events\RDVActionEvent $event
     *
     * @DI\Observe(RDVEvents::onUpdateSecurisation)
     */
    public function update_secure_consult(RDVActionEvent $event)
    {
        $this->suiviRdvManager->addAction(ActionSuivi::UPDATE_SECURISATION);

        $rdv = $event->getRDV();

        if ($rdv->getSecurisation() === RDV::SECU_SKIPPED) {
            $rdv->setTPE(null);
        }

        $event->setRDV($rdv);
    }

    /**
     * @param \KGC\RdvBundle\Events\RDVActionEvent $event
     *
     * @DI\Observe(RDVEvents::onSecurisation)
     */
    public function secure_consult(RDVActionEvent $event)
    {
        $rdv = $event->getRDV();
        $form = $event->getForm();
        $tpe = $form['TPE']->getData();

        if ($tpe instanceof TPE && $tpe->getPaymentGateway() !== null) {
            try {
                $status = $this->processSecurisationPaymentViaGateway($rdv, $form, $tpe);

                if ($status->isCaptured()) {
                    $rdv->setSecurisation(RDV::SECU_DONE);
                    $this->setRDVEtat($rdv, Etat::CONFIRMED);
                } else if ($status->isAuthorized()) {
                    $rdv->setPreAuthorization(
                        (new Authorization)
                            ->setClient($rdv->getClient())
                            ->setAuthorizePayment($status->getFirstModel())
                            ->setAuthorizedAmount($form->get('preauthorization')->getData())
                    );

                    $rdv->setSecurisation(RDV::SECU_DONE);
                    $this->setRDVEtat($rdv, Etat::CONFIRMED);
                } else {
                    throw $this->paymentManager->getPaymentException($status);
                }
            } catch (\Exception $e) {
                $form->addError(new FormError('Securisation payment failed'));
                throw $e;
            }
        } elseif ($form['skip']->getData() == 1) {
            $rdv->setSecurisation(RDV::SECU_SKIPPED);
            $rdv->setTPE(null);
            $this->setRDVEtat($rdv, Etat::CONFIRMED);
        } elseif ($form['securisation']->getData()) {
            $rdv->setSecurisation(RDV::SECU_DONE);
            $this->setRDVEtat($rdv, Etat::CONFIRMED);
        } else {
            $rdv->setSecurisation(RDV::SECU_DENIED);
            if ($form['confirmation']->getData() == 1) {
                $this->setRDVEtat($rdv, Etat::CONFIRMED);
            } else {
                $this->cancel_consult($event);
            }
        }

        $this->suiviRdvManager->addAction(ActionSuivi::SECURE_BANKDETAILS);
        $event->setRDV($rdv);
    }

    protected function processSecurisationPaymentViaGateway($rdv, $form, $tpe)
    {
        $selectedIndex = $form->get('cartebancaires_selected')->getData();
        $carteBancaire = $form->get('cartebancaires')->getData()->get($selectedIndex);

        $array = $form->get('cartebancaires')->getData()->toArray();
        $result = [];
        foreach ($array as $key => $card) {
            $result[$key] = serialize($card);
        }

        if ($carteBancaire === null) {
            throw new \Exception('Invalid card index');
        }

        $preAuthorizeAmount = $form->get('preauthorization')->getData();

        if ($preAuthorizeAmount > 1) {
            return $this->paymentManager->preAuthorizeWithCartebancaire($rdv->getClient(), $carteBancaire, $tpe->getPaymentGateway(), $preAuthorizeAmount, false);
        } else {
            return $this->paymentManager->payWithCartebancaire($rdv->getClient(), $carteBancaire, $tpe->getPaymentGateway(), 1, false, false);
        }
    }

    /**
     * @param \KGC\RdvBundle\Events\RDVActionEvent $event
     *
     * @DI\Observe(RDVEvents::onMiserelation)
     */
    public function connecting_psychic(RDVActionEvent $event)
    {
        $this->suiviRdvManager->addAction(ActionSuivi::CONNECTING_PSYCHIC);

        $rdv = $event->getRDV();
        $form = $event->getForm();
        if ($form['miserelation']->getData() == 1) {
            $this->setRDVEtat($rdv, Etat::CONFIRMED);

            $event->setRDV($rdv);
        } else {
            $this->cancel_consult($event);
        }
    }

    /**
     * @param RDV $rdv
     *
     * @return bool
     */
    public function isTakeable(RDV $rdv)
    {
        return $rdv->getMiserelation() && $rdv->getPriseencharge() === null;
    }

    /**
     * @param \KGC\RdvBundle\Events\RDVActionEvent $event
     *
     * @DI\Observe(RDVEvents::onPriseenCharge)
     */
    public function take_consult(RDVActionEvent $event)
    {
        $this->suiviRdvManager->addAction(ActionSuivi::TAKE_CONSULT);

        $rdv = $event->getRDV();

        $rdv->setPriseencharge(true);
        $this->setRDVEtat($rdv, Etat::INPROGRESS);
    }

    /**
     * @param RDVActionEvent $event
     *
     * @DI\Observe(RDVEvents::onPauseConsult)
     */
    public function pause_consult(RDVActionEvent $event)
    {
        $this->suiviRdvManager->addAction(ActionSuivi::PAUSE_CONSULT);
        $rdv = $event->getRDV();
        $rdv->setPriseencharge(null);
        $this->setRDVEtat($rdv, Etat::PAUSED);
    }

    /**
     * @param \KGC\RdvBundle\Events\RDVActionEvent $event
     *
     * @DI\Observe(RDVEvents::onConsultationEffectuee)
     */
    public function do_consult(RDVActionEvent $event)
    {
        $this->suiviRdvManager->addAction(ActionSuivi::DO_CONSULT);

        $rdv = $event->getRDV();
        $rdv->setConsultation(true);
//        $rdv->setDateConsultation(new \Datetime);
        $this->setRDVEtat($rdv, Etat::COMPLETED);
        $rdv->setPaiement(false);
        $this->setRDVClassement($rdv, Tiroir::UNPAID);
        $rdv->setBalance($rdv->isMontantEncaissementsValid());

        foreach ($rdv->getTarification()->getProduits() as $tar) {
            $tar->setQuantiteEnvoiSupposee();
        }

        $this->close_consult($rdv);

        $event->setRDV($rdv);
    }

    /**
     * @param RDV $rdv
     *
     * @return bool
     */
    public function isReactivable(RDV $rdv)
    {
        if ($rdv->getEtat()->getIdcode() == Etat::CANCELLED) {
            return true;
        }

        return false;
    }

    /**
     * @param \KGC\RdvBundle\Events\RDVActionEvent $event
     *
     * @DI\Observe(RDVEvents::onReportConsult)
     */
    public function report_consult(RDVActionEvent $event)
    {
        $this->suiviRdvManager->addAction(ActionSuivi::POSTPONE_CONSULT);
        $rdv = $event->getRDV();
        $rdv->setPriseencharge(null);
        $event->setRDV($rdv);
    }

    /**
     * @param \KGC\RdvBundle\Events\RDVActionEvent $event
     *
     * @DI\Observe(RDVEvents::onReactivation)
     */
    public function reactivate_consult(RDVActionEvent $event)
    {
        $this->suiviRdvManager->addAction(ActionSuivi::REACTIVATE_CONSULT);

        $rdv = $event->getRDV();
        if ($rdv->getSecurisation() == RDV::SECU_PENDING) {
            $this->setRDVEtat($rdv, Etat::ADDED);
        } else {
            $this->setRDVEtat($rdv, Etat::CONFIRMED);
        }
        // consultation non faite
        $rdv->setMiserelation(null);
        $rdv->setPriseencharge(null);
        $rdv->setConsultation(null);
        $rdv->setConsultant(null);
        $this->setRDVClassement($rdv, Tiroir::PROCESSING);

        $event->setRDV($rdv);
    }

    protected function duplicateOriginalEncaissement(Encaissement $encaissement)
    {
        $uow = $this->entityManager->getUnitOfWork();
        // retrieve encaissement original data
        $originalData = $uow->getOriginalEntityData($encaissement);

        $converter = new CamelCaseToSnakeCaseNameConverter;
        $normalizer = new GetSetMethodNormalizer(null, $converter);
        // create new encaissement entity from original data
        $newEncaissement = $normalizer->denormalize($originalData, get_class($encaissement));
        $newEncaissement->setEtat(Encaissement::PLANNED);
        // fix invalid amount
        $newEncaissement->setMontant($encaissement->getMontant());
        $newEncaissement->getDate()->modify('tomorrow');

        $this->entityManager->persist($newEncaissement);
    }

    /**
     * @param \KGC\RdvBundle\Events\RDVActionEvent $event
     *
     * @DI\Observe(RDVEvents::onValidEncaissement)
     */
    public function proceed_payment(RDVActionEvent $event)
    {
        $rdv = $event->getRDV();
        $encaissement = $event->getEncaissement();

        $encaissement->setDate(new \DateTime());
        $tpe = $encaissement->getTpe();

        if (
            $tpe == 'preauth'
            || ($tpe !== null && $tpe->getPaymentGateway() !== null)
        ) {
            try {
                if ($tpe == 'preauth') {
                    $authorization = $rdv->getPreAuthorization();
                    $encaissement->setTpe($authorization->getAuthorizePayment()->getTpe());
                    $status = $this->paymentManager->captureAuthorization($authorization, $encaissement->getMontant());
                } else {
                    $carteBancaire = $event->getForm()['encaissement']['cartebancaire']->getData();
                    $status = $this->paymentManager->payWithCartebancaire($rdv->getClient(), $carteBancaire, $tpe->getPaymentGateway(), $encaissement->getMontant(), false, false);
                }
                $encaissement->setPayment($payment = $status->getFirstModel());
                if ($payment->hasTag(Payment::TAG_CBI)) {
                    $etiquetteCbi = $this->entityManager->getRepository('KGCRdvBundle:Etiquette')->findOneByIdcode(Etiquette::CBI);
                    $rdv->addEtiquettes($etiquetteCbi);
                }

                if ($status->isCaptured()) {
                    $encaissement->setEtat(Encaissement::DONE);
                } else {
                    $encaissement->setEtat(Encaissement::DENIED);

                    $this->duplicateOriginalEncaissement($encaissement);
                }
            } catch (\Exception $e) {
                $encaissement->setEtat(Encaissement::DENIED);
                if ($e instanceof PaymentFailedException) {
                    $encaissement->setPayment($e->getPayment());
                }

                $this->duplicateOriginalEncaissement($encaissement);
            }

            // explicitly persist encaissement as payment do a flush in the middle of the process
            $this->entityManager->persist($encaissement);
        }

        $this->suiviRdvManager->addAction(ActionSuivi::PROCEED_PAYMENT);
        $this->suiviRdvManager->setDonneeLiee($encaissement->getId());

        if ($encaissement->getEtat() == Encaissement::DONE) {
            if ($encaissement->getDateES() === $rdv->getDateConsultationES()) {
                $rdv->setPaiement(true);
            }
        }

        $event->setRDV($rdv);
        $event->setEncaissement($encaissement);
    }

    /**
     * @param RDV $rdv
     *
     * @return bool
     */
    public function check_payments_match_billing(RDV $rdv)
    {
        $somme = 0;
        foreach ($rdv->getEncaissements() as $encaissement) {
            if ($encaissement->getEtat() == Encaissement::DONE) {
                $somme += $encaissement->getMontant();
            }
            $paiement = $encaissement->getMoyenPaiement();
            if (null !== $paiement && $paiement->getIdcode() !== MoyenPaiement::DEBIT_CARD) {
                $encaissement->setTpe(null);
            }
        }

        return ($rdv->getTarification()->getMontantTotal() == $somme);
    }

    /**
     * @param \KGC\RdvBundle\Events\RDVActionEvent $event
     *
     * @DI\Observe(RDVEvents::onUpdateTarification)
     */
    public function onUpdateTarification(RDVActionEvent $event)
    {
        $tar = $event->getRDV()->getTarification();
        $em = $this->entityManager;
        // traitement possible suppression consommations de forfaits
        $s_consos = $this->request->getSession()->get('original_rdv')->getTarification()->getConsommationsForfaits();
        foreach ($s_consos as $conso) {
            if ($tar->getConsommationsForfaits()->contains($conso) == false) {
                $forfait = $conso->getForfait();
                $or_conso = $em->getRepository('KGCRdvBundle:ConsommationForfait')->findOneById($conso->getId());
                $tar->removeConsommationsForfaits($or_conso);
                $forfait->removeConsommations($or_conso);
                $em->remove($or_conso);
                $em->persist($forfait);
            }
        }
        // traitement possible suppression forfait vendu
        $s_forfait_vendu = $this->request->getSession()->get('original_rdv')->getTarification()->getForfaitVendu();
        if ($tar->getForfaitVendu() === null and $s_forfait_vendu instanceof Forfait) {
            $or_forfait = $em->getRepository('KGCRdvBundle:Forfait')->findOneById($s_forfait_vendu->getId());
            $em->remove($or_forfait);
        }
        // traitement possible suppression produit
        $s_vproduits = $this->request->getSession()->get('original_rdv')->getTarification()->getProduits();
        foreach ($s_vproduits as $vpr) {
            if ($tar->getProduits()->contains($vpr) == false) {
                $or_vpr = $em->getRepository('KGCRdvBundle:VentesProduits')->findOneById($vpr->getId());
                $tar->removeProduits($or_vpr);
                $em->remove($or_vpr);
            }
        }
        // traitement suppression envoi
        foreach ($tar->getProduits() as $vpr) {
            $vpr->setQuantiteEnvoiSupposee();

            if ($vpr->getQuantiteEnvoi() == 0 && $vpr->getEnvoi() instanceof EnvoiProduit) {
                $em->remove($vpr->getEnvoi());
                $vpr->setEnvoi(null);
            }
        }
    }

    /**
     * @param \KGC\RdvBundle\Events\RDVActionEvent $event
     *
     * @DI\Observe(RDVEvents::onUpdateFacturation)
     */
    public function onUpdateFacturation(RDVActionEvent $event)
    {
        $rdv = $event->getRDV();

        $em = $this->entityManager;
        // traitement possible suppression encaissement
        $s_encs = $this->request->getSession()->get('original_rdv')->getEncaissements();
        foreach ($s_encs as $enc) {
            if ($rdv->getEncaissements()->contains($enc) == false) {
                $or_enc = $em->getRepository('KGCRdvBundle:Encaissement')->findOneById($enc->getId());
                $rdv->removeEncaissements($or_enc);
                $em->remove($or_enc);
            }
        }
    }

    /**
     * @param \KGC\RdvBundle\Events\RDVActionEvent $event
     *
     * @DI\Observe(RDVEvents::onValidEncaissement)
     * @DI\Observe(RDVEvents::onUpdateTarification)
     * @DI\Observe(RDVEvents::onUpdateFacturation)
     */
    public function onBillingChanges(RDVActionEvent $event)
    {
        $rdv = $event->getRDV();

        $this->processBillingChanges($rdv);

        $event->setRDV($rdv);
    }

    public function processBillingChanges(RDV $rdv, $suivi = true)
    {
        $rdv->setBalance($rdv->isMontantEncaissementsValid());
        $rdv = $this->update_paid_amount($rdv);
        $rdv = $this->close_consult($rdv, $suivi);
    }

    /**
     * @param RDVActionEvent $event
     *
     * @DI\Observe(RDVEvents::onUpdateClassement)
     */
    public function sort_consult(RDVActionEvent $event)
    {
        $this->suiviRdvManager->addAction(ActionSuivi::SORT_CONSULT);

        $rdv = $event->getRDV();
        $form = $event->getForm();

        // traitement litige
        if ($rdv->getClassement()->getTiroir()->getIdcode() == Tiroir::UNPAID) {
            if ($form['litige']->getData() == true) {
                $rdv->setCloture(false);
                $this->setRDVEtat($rdv, Etat::UNPAID);
            } else {
                $rdv->setCloture(null);
            }
        }

        $event->setRDV($rdv);
    }

    /**
     * @param RDV $rdv
     *
     * @return RDV
     */
    public function close_consult(RDV $rdv, $suivi = true)
    {
        if ($this->check_payments_match_billing($rdv)) {
            if ($suivi) {
                $this->suiviRdvManager->addAction(ActionSuivi::CLOSE_CONSULT);
            }

            $rdv->setCloture(true);
            $this->setRDVEtat($rdv, Etat::CLOSED);
            if ($rdv->getDateConsultation()->format('dmY') == date('dmY')) {
                $classement = Dossier::VALIDE;
            } else {
                $classement = Dossier::RECUP;
            }
            if ($rdv->is10MIN()) {
                $classement = Dossier::DIXMIN;
            }

            $this->setRDVClassement($rdv, $classement);
        } elseif ($rdv->getCloture() == true) {
            if ($suivi) {
                $this->suiviRdvManager->addAction(ActionSuivi::UNCLOSE_CONSULT);
            }

            $rdv->setCloture(null);
            $this->setRDVEtat($rdv, Etat::COMPLETED);
            $this->setRDVClassement($rdv, Tiroir::UNPAID);
        }

        return $rdv;
    }

    /**
     * @param RDV $rdv
     *
     * @return RDV
     */
    public function update_paid_amount(RDV $rdv)
    {
        $somme = 0;
        foreach ($rdv->getEncaissements() as $encaissement) {
            if ($encaissement->getEtat() == Encaissement::DONE) {
                $somme = $somme + $encaissement->getMontant();
            }
        }
        $rdv->setMontantEncaisse($somme);

        return $rdv;
    }

    /**
     * @param RDVActionEvent $event
     *
     * @DI\Observe(RDVEvents::onCancelPayment)
     */
    public function cancel_payment(RDVActionEvent $event)
    {
        $this->suiviRdvManager->addAction(ActionSuivi::CANCEL_PAYMENT);

        $enc = $event->getEncaissement();
        $this->suiviRdvManager->setDonneeLiee($enc->getId());

        $enc->setEtat(Encaissement::CANCELLED);
        $enc->setDateOppo(new \DateTime());
        $event->setEncaissement($enc);

        $rdv = $event->getRDV();
        $rdv->setCloture(null);
        $this->setRDVEtat($rdv, Etat::COMPLETED);
        $this->setRDVClassement($rdv, Dossier::OPPOSITION);
        $eti_rep = $this->entityManager->getRepository('KGCRdvBundle:Etiquette');
        $rdv->addEtiquettes($eti_rep->findOneByIdcode(Etiquette::OPPO));
        $event->setRDV($rdv);
    }

    /**
     * fix entities : fix entities that need to be updated before save.
     *
     * @param KGC\RdvBundle\Entity\RDV $rdv
     */
    public function fixEntities($rdv)
    {
        $rdv->getAdresse()->setClient($rdv->getClient());
        if($rdv->getIdAstro()){
            $rdv->getIdAstro()->setClient($rdv->getClient());
        }
    }

    /**
     * @param KGC\RdvBundle\Entity\RDV $rdv
     * @param string                   $etatcode
     */
    public function setRDVEtat($rdv, $etatcode)
    {
        $etat = $this->entityManager->getRepository('KGCRdvBundle:Etat')->findOneByIdcode($etatcode);
        $rdv->setEtat($etat);
    }

    /**
     * @param KGC\RdvBundle\Entity\RDV $rdv
     * @param string                   $clacode
     */
    public function setRDVClassement($rdv, $clacode)
    {
        $classement = $this->entityManager->getRepository('KGCRdvBundle:Classement')->findOneByIdcode($clacode);
        $rdv->setClassement($classement);
    }

    /**
     * preventDuplicate : Affiliations transparentes.
     *
     * @param type $consult
     * @param bool $persist
     */
    public function preventDuplicate($consult, $idastro, $persist = false)
    {
        $client = $consult->getClient();
        $cli_rep = $this->entityManager->getRepository('KGCSharedBundle:Client');
        $consult->setClient($cli_rep->preventDuplicateRdv($client, $consult, $persist));
        $client = $consult->getClient();
        $client->setNumtel1($consult->getNumtel1());
        $client->setNumtel2($consult->getNumtel2());
        $client->addAdress($consult->getAdresse());
        foreach ($consult->getCartebancaires() as $cb){
            $client->addCartebancaires($cb);
        }
        $ida_rep = $this->entityManager->getRepository('KGCClientBundle:IdAstro');
        $consult->setIdAstro($ida_rep->preventDuplicate($consult->getIdAstro(), $consult->getClient(), $consult->getWebsite(), $idastro));
    }

    /**
     * @param \KGC\RdvBundle\Events\RDVActionEvent $event
     *
     * @DI\Observe(RDVEvents::onMailSend)
     */
    public function mail_send(RDVActionEvent $event)
    {
        $sentMailForm = $event->getForm()->get('mail_sent');
        $client = $event->getClient();
        $rdv = $event->getRDV();
        $user = $event->getUser();
        if ($sentMailForm && $client && $rdv && $user) {
            $this->suiviRdvManager->addAction(ActionSuivi::SEND_MAIL);
            $sentMail = $sentMailForm->getData();
            $mailObject = $sentMailForm->get('mail')->getData();

            if ($mailObject && Mail::BILL === $mailObject->getCode()) {
                $this->billingService->create($rdv);
                $attachment = $this->billingService->Output('facture.pdf', 'S');
                $sentMail->addAttachment([
                    'stream' => $attachment,
                    'name' => 'facture.pdf',
                    'mime' => 'application/pdf',
                ]);
            }

            $file = $sentMailForm->get('file')->getData();
            if ($file) {
                $sentMail->addAttachment([
                    'stream' => file_get_contents($file),
                    'name' => $file->getClientOriginalName(),
                    'mime' => $file->getClientMimeType(),
                ]);
            }

            $this->mailer->sendConsultationMail($sentMail, $client->getMail());

            $h = new Historique();
            $h->setBackendType(Historique::BACKEND_TYPE_MAIL);
            $h->setType(Historique::TYPE_MAIL);
            $h->setClient($client);
            $h->setRdv($rdv);
            $h->setMail($sentMail);
            $h->setConsultant($user);

            $this->entityManager->persist($h);
            $this->entityManager->persist($sentMail);
        }
    }

    /**
     * @param \KGC\RdvBundle\Events\RDVActionEvent $event
     *
     * @DI\Observe(RDVEvents::onSmsSend)
     */
    public function sms_send(RDVActionEvent $event)
    {
        $sentSmsForm = $event->getForm()->get('sms_sent');
        $client = $event->getClient();
        $rdv = $event->getRDV();
        $user = $event->getUser();
        if ($sentSmsForm && $client && $rdv && $user) {
            $this->suiviRdvManager->addAction(ActionSuivi::SEND_SMS);
            $sentSms = $sentSmsForm->getData();
            $smsObject = $sentSmsForm->get('sms')->getData();

//            $this->mailer->sendConsultationSms($sentSms, $client->getNumtel1());

            $result = $this->smsApi->sendSms($sentSms, $rdv->getWebsite()->getId());



            $h = new Historique();
            $h->setBackendType(Historique::BACKEND_TYPE_SMS);
            $h->setType(Historique::TYPE_SMS);
            $h->setClient($client);
            $h->setRdv($rdv);
            $h->setSms($sentSms);
            $h->setConsultant($user);

            $this->entityManager->persist($h);
            $this->entityManager->persist($smsObject);
        }
    }

    public function generateNewCreditCard(RDV $rdv, CreditCard $creditCard)
    {
        $rdv->setNewCardHash(null)
            ->setNewCardHashCreatedAt(null);

        $carteBancaire = (new CarteBancaire)
            ->setNumero($creditCard->getNumber())
            ->setCryptogramme($creditCard->getSecurityCode())
            ->setExpiration($creditCard->getExpireAt()->format('m/y'));

        $rdv->addCartebancaires($carteBancaire);

        $this->entityManager->persist($rdv);
        $this->entityManager->flush($rdv);

        return true;
    }

    /**
     * @param getHumanStatus $status
     *
     * @return bool true if updated
     */
    public function updateRdvFromStatus(GetHumanStatus $status)
    {
        $payment = $status->getFirstModel();
        $encaissement = $this->entityManager->getRepository('KGCRdvBundle:Encaissement')->findOneByPayment($payment->getOriginalPayment() ?: $payment);

        if ($encaissement && $this->paymentManager->updateEncaissementFromStatus($encaissement, $status)) {
            $rdv = $encaissement->getConsultation();
            $this->processBillingChanges($rdv, false);

            $this->entityManager->persist($encaissement);
            $this->entityManager->persist($rdv);
            $this->entityManager->flush($encaissement);
            $this->entityManager->flush($rdv);

            return true;
        }

        return false;
    }

    /**
     * @param GetHumanStatus $status
     *
     * @return bool true si modifié
     */
    public function updateAuthorizationFromStatus(GetHumanStatus $status)
    {
        $payment = $status->getFirstModel();

        $authorization = $this->entityManager->getRepository('KGCPaymentBundle:Authorization')->findOneByAuthorizePayment($payment->getOriginalPayment() ?: $payment);

        if ($authorization instanceof Authorization) {
            if ($status->isCanceled() && $authorization->getCapturePayment() === null) {
                $authorization
                    ->setCapturePayment($payment)
                    ->setCapturedAmount(0);

                $this->entityManager->persist($authorization);
                $this->entityManager->flush($authorization);

                return true;
            } else if (($status->isRefunded() || $status->isOpposed()) && $authorization->getCapturedAmount() > 0) {
                $encaissement = $this->entityManager->getRepository('KGCRdvBundle:Encaissement')->findOneByPayment($authorization->getCapturePayment());

                if ($encaissement && $this->paymentManager->updateEncaissementFromStatus($encaissement, $status)) {
                    $rdv = $encaissement->getConsultation();
                    $this->processBillingChanges($rdv, false);

                    $this->entityManager->persist($encaissement);
                    $this->entityManager->persist($rdv);
                    $this->entityManager->flush($encaissement);
                    $this->entityManager->flush($rdv);

                    return true;
                }
            }
        }

        return false;
    }
}
