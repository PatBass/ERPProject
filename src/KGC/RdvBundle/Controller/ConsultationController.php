<?php

// src/KGC/RdvBundle/Controller/ConsultationController.php


namespace KGC\RdvBundle\Controller;

use JMS\SecurityExtraBundle\Annotation\Secure;
use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\Bundle\SharedBundle\Entity\LandingUser;
use KGC\Bundle\SharedBundle\Entity\Website;
use KGC\ClientBundle\Entity\Historique;
use KGC\ClientBundle\Entity\MailSent;
use KGC\ClientBundle\Entity\SmsSent;
use KGC\CommonBundle\Controller\CommonController;
use KGC\RdvBundle\Entity\CarteBancaire;
use KGC\RdvBundle\Entity\CodePromo;
use KGC\RdvBundle\Entity\Etat;
use KGC\RdvBundle\Entity\RDV;
use KGC\RdvBundle\Entity\Source;
use KGC\RdvBundle\Entity\Support;
use KGC\RdvBundle\Form;
use KGC\RdvBundle\Form\Handler;
use KGC\RdvBundle\Repository\RDVRepository;
use KGC\RdvBundle\Service\PlanningService;
use KGC\UserBundle\Entity\Voyant;
use Proxies\__CG__\KGC\Bundle\SharedBundle\Entity\Adresse;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Validator\Constraints\NotBlank;
use \CALLR\Realtime\Server;
use \CALLR\Realtime\Command;
use \CALLR\Realtime\CallFlow;
use \CALLR\Realtime\Command\ConferenceParams;
/**
 * Consultation Controller.
 *
 * @category Controller
 *
 * @author Laurene Dourdin <2aurene@gmail.com>
 */
class ConsultationController extends CommonController
{
    const DASHBOARD_CANCELLED_LIMIT = 8;
    const DASHBOARD_CLOSED_LIMIT = 8;

    /**
     * Return the useful information to get repository.
     *
     * @return mixed
     */
    protected function getEntityRepository()
    {
        return 'KGCRdvBundle:RDV';
    }

    /**
     * Méthode AjouterAction
     * Affichage et traitement du formulaire d'ajout d'une consultation
     * sous forme de widget.
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_PHONISTE, ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE, ROLE_MANAGER_PHONIST")
     */
    public function AjouterAction($vue)
    {
        $currUser = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $rdv = new RDV($currUser);
        $request = $this->get('request');
        $form = $this->createForm(new Form\RDVAjouterType($currUser, $em), $rdv);
        $formhandler = $this->get('kgc.rdv.formhandler');
        $result = $formhandler->process($form, $request, true);
        if ($result !== null) { // submit
            $client = $rdv->getClient()->getFullName();
            if ($result) { // submit valid
                $this->addFlash('light#plus-light', $client . '--Consultation ajoutée.');
                $form = $this->createForm(new Form\RDVAjouterType($currUser, $em), new RDV($currUser)); // reset du formulaire
            } elseif ($result === false) { // submit invalid
                $this->addFlash('error#plus', $client . '--Consultation non ajoutée.');
            }
        }

        return $this->render('KGCRdvBundle:Consultation:ajouter.html.twig', array(
            'form' => $form->createView(),
            'vue' => $vue,
        ));
    }

    /**
     * Méthode AddRdvByProspectAction
     * Affichage et traitement du formulaire d'ajout d'une consultation à partir d'une fiche prospect
     * sous forme de widget.
     *
     * @param string $vue
     * @param int $id
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_PHONISTE, ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_MANAGER_STANDAR, ROLE_J_1, ROLE_DRI, ROLE_MANAGER_PHONE, ROLE_MANAGER_PHONIST")
     */
    public function AddRdvByProspectAction($vue, $id)
    {
        $prospect = $this->getRepository('KGCSharedBundle:LandingUser')->find($id);
        $currUser = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $rdv = new RDV($currUser);
        $toComplete = [];
        if ($prospect) {
            $website = $prospect->getWebsite() ?: $this->getRepository('KGCSharedBundle:Website')->getWebsiteByAssociationName($prospect->getMyastroWebsite(), false);
            if ($website instanceof Website) {
                $rdv->setWebsite($website);
            }
            $source = $prospect->getSourceConsult() ?: $this->getRepository('KGCRdvBundle:Source')->getSourceByAssociationName($prospect->getMyastroSource());
            if ($source instanceof Source) {
                $rdv->setSource($source);
            }
            $support = $prospect->getSupport() ?: $this->getRepository('KGCRdvBundle:Support')->findByProfilesQB($this->getUser()->getProfils(), true);
            if ($support instanceof Support) {
                $rdv->setSupport($support);
            }
            $voyant = $prospect->getVoyant() ?: $this->getRepository('KGCUserBundle:Voyant')->findOneByNom($prospect->getMyastroPsychic());
            if ($voyant instanceof Voyant) {
                $rdv->setVoyant($voyant);
            }
            $codePromo = $prospect->getCodePromo() ?: $this->getRepository('KGCRdvBundle:CodePromo')->findOneByCode(strtoupper($prospect->getMyastroPromoCode()));
            if ($codePromo instanceof CodePromo) {
                $rdv->setCodePromo($codePromo);
            }
            $find = ['label' => strtolower($prospect->getMyastroUrl())];
            if (!empty($website)) {
                $find['website'] = $website;
            }
            if (!empty($source)) {
                $find['source'] = $source;
            }
            $formurl = $prospect->getFormurl() ?: $this->getRepository('KGCRdvBundle:FormUrl')->findOneBy($find);
            if ($formurl) {
                $rdv->setFormUrl($formurl);
            }
            $toComplete['idAstro_valeur'] = $prospect->getMyastroId();
            $toComplete['gclid'] = $prospect->getMyastroGclid();
            $toComplete['numtel1'] = $prospect->getPhone();
            $toComplete['questionSubject'] = $prospect->getQuestionSubject();
            $toComplete['questionContent'] = $prospect->getQuestionContent();
            $toComplete['questionText'] = $prospect->getQuestionText();
            $toComplete['spouseName'] = $prospect->getSpouseName();
            $toComplete['spouseBirthday'] = $prospect->getSpouseBirthday() ? $prospect->getSpouseBirthday()->format('d/m/Y') : '';
            $toComplete['spouseSign'] = $prospect->getSpouseSign();
            $toComplete['adresse_pays'] = $prospect->getCountry();
            if ($this->get('request')->getMethod() == "POST") {
                $client = null;
            }else{
                $client = ($prospect->getClient()) ?: $this->getRepository('KGCSharedBundle:LandingUser')->getProspectClient($prospect);
            }
            if ($client) {
                $rdv->setClient($client);
                $toComplete['client_prenom'] = $prospect->getFirstName();
                $toComplete['client_nom'] = $prospect->getLastName();
                if (empty($toComplete['client_nom'])) {
                    $toComplete['client_nom'] = strtolower($client->getNom()) !== strtolower($client->getEmail()) ? $client->getNom() : '';
                }
                $toComplete['client_mail'] = $prospect->getEmail();
                $toComplete['client_dateNaissance'] = $prospect->getBirthday() ? $prospect->getBirthday()->format('d/m/Y') : '';
                $toComplete['client_genre'] = $prospect->getGender();
                $existCb = false;
                if ($client->getCartebancaires()->last()) {
                    $existCb = true;
                    $carteBancaire = $client->getCartebancaires()->last();
                    $toComplete['cartebancaires___name___numero'] = $carteBancaire->getNumero();
                    $toComplete['cartebancaires___name___expiration'] = $carteBancaire->getExpiration();
                    $toComplete['cartebancaires___name___cryptogramme'] = $carteBancaire->getCryptogramme();
                }

                $rdvClient = $this->getRepository('KGCRdvBundle:RDV')->findOneByClient($client);
                if ($rdvClient) {
                    if (empty($toComplete['numtel1'])) {
                        $toComplete['numtel1'] = $rdvClient->getNumtel1();
                    }
                    $toComplete['numtel2'] = $rdvClient->getNumtel2();
                    if ($adresse = $rdvClient->getAdresse()) {
                        $toComplete['adresse_voie'] = $adresse->getVoie();
                        $toComplete['adresse_complement'] = $adresse->getComplement();
                        $toComplete['adresse_codepostal'] = $adresse->getCodepostal();
                        $toComplete['adresse_ville'] = $adresse->getVille();
                        if($adresse->getPays() != ''){
                            $toComplete['adresse_pays'] = $adresse->getPays();
                        }
                    }
                    if (!$existCb && $rdvClient->getCartebancaires()->last()) {
                        $carteBancaire = $rdvClient->getCartebancaires()->last();
                        $toComplete['cartebancaires___name___numero'] = $carteBancaire->getNumero();
                        $toComplete['cartebancaires___name___expiration'] = $carteBancaire->getExpiration();
                        $toComplete['cartebancaires___name___cryptogramme'] = $carteBancaire->getCryptogramme();
                    }
                }
            } else {
                $toComplete['client_prenom'] = $prospect->getFirstName();
                $toComplete['client_nom'] = $prospect->getLastName();
                $toComplete['client_mail'] = $prospect->getEmail();
                $toComplete['client_dateNaissance'] = $prospect->getBirthday() ? $prospect->getBirthday()->format('d/m/Y') : '';
                $toComplete['client_genre'] = $prospect->getGender();
            }
        }
        $request = $this->get('request');
        $form = $this->createForm(new Form\RDVAjouterType($currUser, $em), $rdv);
        $formhandler = $this->get('kgc.rdv.formhandler');
        $result = $formhandler->process($form, $request, true);
        if ($result !== null) { // submit
            $client = $rdv->getClient()->getFullName();
            if ($result) { // submit valid
                $prospect->setClient($rdv->getClient());
                $prospect->setIsHandle(1);
                $rdv->setProspect($prospect);
                $em = $this->getDoctrine()->getManager();
                $em->persist($prospect);
                $em->persist($rdv);
                $em->flush();
                $this->addFlash('light#plus-light', $client . '--Consultation ajoutée.');
                $form = $this->createForm(new Form\RDVAjouterType($currUser, $em), new RDV($currUser)); // reset du formulaire
            } elseif ($result === false) { // submit invalid
                $this->addFlash('error#plus', $client . '--Consultation non ajoutée.');
            }
        }

        return $this->render('KGCRdvBundle:Consultation:ajouter.html.twig', array(
            'form' => $form->createView(),
            'vue' => $vue,
            'prospectId' => $prospect->getId(),
            'toComplete' => $toComplete
        ));
    }

    /**
     * Méthode SendCardLinkAction
     * Envoi du mail/sms avec le lien vers le formulaire de paiement
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_PHONISTE, ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONIST, ROLE_MANAGER_PHONE")
     */
    public function SendCardLinkAction($id, $type)
    {
        $em = $this->getDoctrine()->getManager();
        $rdv = $em->getRepository('KGCRdvBundle:RDV')->findOneById($id);

        if ($rdv && ($hash = $rdv->getNewCardHash())) {
            $client = $rdv->getClient()->getFullName();
            $rdv->setNewCardHashCreatedAt(new \DateTime);

            try {
                if ($type == 'mail') {
                    $this->get('kgc.common.twig_swift_mailer')->sendNewCardHashSuccessEmailMessage($rdv);
                } else {
                    throw new \Exception('Unsupported type');
                }

                $em->persist($rdv);
                $em->flush($rdv);

                $this->addFlash('light#copy-light', $client . '--Mail envoyé.');
            } catch (\Exception $e) {
                $this->addFlash('error#copy', $client . '--Erreur lors de l\'envoi de l\'email.');
            }
        } else if ($rdv) {
            $this->addFlash('error#copy', 'Erreur--Aucune demande de carte bancaire trouvée.');
        } else {
            $this->addFlash('error#copy', 'Erreur--Consultation introuvable.');
        }

        return $this->forward('KGCDashboardBundle:Dashboard:renderFlashBag');
    }

    /**
     * Méthode MakeCallAction
     * Permet de réaliser un appel sortant
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_QUALITE, ROLE_MANAGER_PHONE, ROLE_UNPAID_SERVICE, ROLE_VALIDATION, ROLE_MANAGER_STANDAR, ROLE_MANAGER_PHONIST, ROLE_PHONISTE, ROLE_PHONING_TODAY, ROLE_J_1")
     */
    public function MakeCallAction()
    {
        $this->get('kgc.client.sms.service')->makeCall();
    }

    /**
     * Méthode realtimeAction
     * Permet de lancer le server
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_QUALITE, ROLE_MANAGER_PHONE, ROLE_UNPAID_SERVICE, ROLE_VALIDATION, ROLE_MANAGER_STANDAR, ROLE_MANAGER_PHONIST, ROLE_PHONISTE, ROLE_PHONING_TODAY, ROLE_J_1")
     */
    public function realtimeAction(){
        /* Recommended */
        date_default_timezone_set('UTC');
        set_error_handler(function ($errno, $errstr, $errfile, $errline, array $errcontext) {
            throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        });
        /* Create a new call flow */
        $flow = new CallFlow;
        /* When a call is inbound (a DID is being called),
           this callback will be called */
        $flow->onInboundCall(function (CallFlow $flow) {
            // your code
            /* your callback **MUST** return a label to execute */
            return 'ask_age';
        });
        /* When an outbound call is answered,
           this callback will be called */
        $flow->onOutboundCall(function (CallFlow $flow) {
            // your code
            /* label to execute */
            return 'ask_age';
        });
        /* When an call is hung up,
           this callback will be called */
        $flow->onHangup(function (CallFlow $flow) {
            // your code
        });
        /* Define a label with a command and its parameters,
           along with the **async** result callback */
        $flow->define(
            'ask_age',
            function (CallFlow $flow) {
                return Command::read('TTS|TTS_EN-GB_SERENA|Hello there. How old are you?', 3, 2, 5000);
            },
            function ($result, $error, CallFlow $flow) {
                // your code
                /* if the 'read' command succeeds, the result will be in $result, and $error will be null.
                   if it fails, the error will be in $error, and result will be null */
                /* we can check if the call is hang up */
                if (!$flow->isHangup()) {
                    /* here we store some variables in the call
                       they can be used in subsequent labels */
                    $flow->setVariable('age', $result);
                    /* label to execute */
                    return 'say_age';
                }
            }
        );
        /* This label is using the $age variable store above */
        $flow->define(
            'say_age',
            function (CallFlow $flow) {
                return Command::play("TTS|TTS_EN-GB_SERENA|You are {$flow->getVariable('age')} years old.");
            },
            function ($result, $error, CallFlow $flow) {
                /* '_hangup' is a special label to hangup */
                return $flow->getVariable('age') >= 18 ? 'conference' : '_hangup';
            }
        );
        $flow->define(
            'conference',
            function (CallFlow $flow) {
                /* conference params */
                $params = new ConferenceParams;
                $params->autoLeaveWhenAlone = true;
                /* create a conference room based on your age */
                return Command::conference($flow->getVariable('age'), $params);
            },
            function ($result, $error, CallFlow $flow) {
                /* '_hangup' is a special label to hangup */
                return '_hangup';
            }
        );
        /* Real-time Server */
        $server = new Server;
        /* Register a callback to receive raw input. Useful for debugging. */
        $server->setRawInputHandler(function ($data) {
            $data = date('c').' <<<< '.$data."\n";
            file_put_contents('/tmp/RT_DEBUG', $data, FILE_APPEND);
        });
        /* Register a callback to receive raw output. Useful for debugging. */
        $server->setRawOutputHandler(function ($data) {
            $data = date('c').' >>>> '.$data."\n";
            file_put_contents('/tmp/RT_DEBUG', $data, FILE_APPEND);
        });
        /* Match your call flow against a REALTIME10 app hash */
//$server->registerCallFlow('DEADBEEF', $flow);
        /* Or any hash! */
        $server->registerCallFlow('*', $flow);
        /* Start */
        $server->start();
    }

    /**
     * Méthode DupliquerAction
     * Affichage et traitement du formulaire de d'ajout d'une consultation à partir d'une autre
     * sous forme de widget.
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_QUALITE, ROLE_MANAGER_PHONE, ROLE_UNPAID_SERVICE, ROLE_VALIDATION, ROLE_MANAGER_STANDAR, ROLE_MANAGER_PHONIST, ROLE_PHONISTE, ROLE_PHONING_TODAY, ROLE_J_1")
     */
    public function DupliquerAction($id, $suivi = false)
    {
        $currUser = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $rep_rdv = $em->getRepository('KGCRdvBundle:RDV');
        $rdvbase = $rep_rdv->findOneById($id);
        if (!$rdvbase) {
            $rdv = new RDV($currUser);
        } else {
            $rdv = clone $rdvbase;
            $rdv->resetRdv($currUser, !!$suivi);
            if($currUser->duplicationMaskPhone()) {
                $rdv->setNumtel1('');
                $rdv->setNumtel2('');
            }
        }
        $request = $this->get('request');
        $form = $this->createForm(new Form\RDVAjouterType($currUser, $em, array() , $currUser->duplicationMaskCB(), $this->getUser()->getIsDecryptAvailable()), $rdv);
        if ($suivi) {
            $sup_suivi = $em->getRepository('KGCRdvBundle:Support')->findOneByIdcode(Support::SUIVI_CLIENT);
            $form['support']->setData($sup_suivi);
        }
        $formhandler = $this->get('kgc.rdv.formhandler');
        $result = $formhandler->process($form, $request, true);
        $client = $rdv->getClient()->getFullName();
        if ($result === true) { // submit valid
            $this->addFlash('light#copy-light', $client . '--Consultation ajoutée.');

            return $this->redirect($this->get('router')->generate('kgc_rdv_fiche', array('id' => $rdv->getId())));
        } elseif ($result === false) { // submit invalid
            $this->addFlash('error#copy', $client . '--Consultation non ajoutée.');
        }

        return $this->render('KGCRdvBundle:Consultation:dupliquer.modal.html.twig', array(
            'form' => $form->createView(),
            'rdv' => $rdvbase,
            'suivi' => $suivi,
            'add_cartebancaire' => true
        ));
    }

    /**
     * @param $str_search
     *
     * @return string
     */
    protected function getSearchType($str_search)
    {
        return preg_match('#^[0-9]+-?#', $str_search)
            ? RDVRepository::BY_CB
            : RDVRepository::BY_CLIENT;
    }

    /**
     * @param $type
     * @param $str_search
     *
     * @return mixed
     */
    protected function decryptDataIfNeeded($type, $str_search)
    {
        if (RDVRepository::BY_CB === $type) {
            return $this->get('kgc.rdv.encryption.service')
                ->encrypt($str_search);
        }

        return $str_search;
    }

    /**
     * Méthode RechercherAction
     * Recherche de consultations.
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function rechercherAction()
    {
        $resultats = null;
        $em = $this->getDoctrine()->getManager();
        $form = $this->createFormBuilder()
            ->add('recherche', 'search', array(
                'required' => false,
                'constraints' => array(new NotBlank()),
            ))
            ->getForm();
        $request = $this->get('request');
        $form->handleRequest($request);
        if ($form->isValid()) {
            $str_recherche = $form['recherche']->getData();
            $rep_rdv = $em->getRepository('KGCRdvBundle:RDV');
            if ($str_recherche == '*') {
                $resultats = $rep_rdv->findAll();
            } else {
                $data = $form['recherche']->getData();
                $type = $this->getSearchType($data);
                $data = $this->decryptDataIfNeeded($type, $data);
                $resultats = $rep_rdv->search($data, $type);
            }
        }

        return $this->render('KGCRdvBundle:Consultation:rechercher.widget.html.twig', array(
            'form' => $form->createView(),
            'resultats' => $resultats,
        ));
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @throws \Exception
     *
     * @Secure(roles="ROLE_VOYANT")
     */
    public function BuildSearchDateIntervalleAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new \Exception('Ajax call only please !');
        }

        $value = $request->get('kgc_RdvBundle_search_date');
        $result = $this->get('kgc.rdv.planning.service')
            ->getIntervalleByType($value['intervalle']);

        return $this->jsonResponse($result);
    }

    /**
     * @param Request $request
     * @param FormInterface $form
     * @param $field
     * @param $sessionField
     * @param $default
     *
     * @return mixed
     */
    protected function setFormFieldValue(Request $request, FormInterface $form, $field, $sessionField, $default)
    {
        $value = $form[$field]->getData();
        $value = $value ?: $default;
        if (!$form->isSubmitted()) {
            $form[$field]->setData($value);
        }
        $request->getSession()->set($sessionField, $value);

        return $value;
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @Secure(roles="ROLE_VOYANT")
     */
    public function RechercherDateAction(Request $request)
    {
        $previousBeginSearch = $request->getSession()->get('search_begin');
        $previousEndSearch = $request->getSession()->get('search_end');
        $previousShortcut = $request->getSession()->get('search_shortcut');

        $resultats = null;
        $form = $this->createForm(
            new Form\RDVSearchDateType($this->get('kgc.rdv.planning.service'))
        );

        $form->handleRequest($request);

        if (null !== $previousBeginSearch &&
            null !== $previousEndSearch &&
            null !== $previousShortcut &&
            !$form->isSubmitted()
        ) {
            $form['date_begin']->setData($previousBeginSearch);
            $form['date_end']->setData($previousEndSearch);
            $form['intervalle']->setData($previousShortcut);
        }

        $begin = $this->setFormFieldValue($request, $form, 'date_begin', 'search_begin', new \DateTime(date('Y-m-d', time())));
        $end = $this->setFormFieldValue($request, $form, 'date_end', 'search_end', new \DateTime(date('Y-m-d', time())));
        $this->setFormFieldValue($request, $form, 'intervalle', 'search_shortcut', PlanningService::INTERVALLE_TODAY);

        if ($end) {
            $endClone = clone $end;
            $endClone->add(new \DateInterval('P1D'));
            $resultats = !empty($begin) && !empty($endClone)
                ? $this->getRepository()->searchByIntervalle($begin, $endClone, $this->getUser())
                : $resultats;
        }

        return $this->render('KGCRdvBundle:Consultation:rechercher.date.widget.html.twig', array(
            'form' => $form->createView(),
            'resultats' => $resultats,
        ));
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @Secure(roles="ROLE_VOYANT")
     */
    public function RechercherVoyantAction(Request $request)
    {
        $previousSearch = $request->getSession()->get('search');
        $resultats = null;
        $form = $this->createFormBuilder()
            ->add('recherche', 'search', [
                'required' => false,
                'constraints' => [new NotBlank()],
            ])->getForm();

        $form->handleRequest($request);

        if ($form->isValid() || null !== $previousSearch) {
            if (null !== $previousSearch && !$form->isSubmitted()) {
                $form['recherche']->setData($previousSearch);
            }
            $data = $form['recherche']->getData();

            $request->getSession()->set('search', $data);
            if (null !== $data && strlen($data)) {
                $resultats = $this->getRepository()->search(
                    $data,
                    RDVRepository::BY_CLIENT,
                    $this->getUser()
                );
            }
        }

        return $this->render('KGCRdvBundle:Consultation:rechercher.widget.html.twig', array(
            'form' => $form->createView(),
            'resultats' => $resultats,
        ));
    }

    /**
     * Méthode VoirficheAction
     * Consultation de la fiche client/RDV.
     * @param int $id
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONIST, ROLE_QUALITE, ROLE_VOYANT, ROLE_QUALITE, ROLE_MANAGER_PHONE, ROLE_UNPAID_SERVICE, ROLE_VALIDATION, ROLE_MANAGER_STANDAR, ROLE_J_1, ROLE_PHONISTE")
     */
    public function VoirficheAction(Request $request, $id)
    {
        $forceEmpty = $request->query->get('forceEmpty');
        $forceEmpty = $forceEmpty ?: false;

        $currUser = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $rdv = $this->findById($id);
        if ($rdv) {
            $request = $this->get('request');
            $request->getSession()->set('original_rdv', clone $rdv);
            $form_edit = $this->createForm(new Form\RDVEditType($rdv));
            $form_edithandler = new Handler\RDVEditHandler($form_edit, $request);
            $param_edit = $form_edithandler->process();
            if ($currUser->isVoyant()) { // champs de prise de notes éditables
                $historiqueManager = $this->get('kgc.client.historique.manager');
                $formType = new Form\RDVNotesType($currUser, $em, $historiqueManager, $param_edit);
            } else {
                $formType = new Form\RDVType($currUser, $param_edit, $em, null, false, true, $this->getUser()->getIsDecryptAvailable());
            }
            $form = $this->createForm($formType, $rdv);
            $formhandler = $this->get('kgc.rdv.formhandler');
            $result = $formhandler->process($form, $request);
            $client = $rdv->getClient()->getFullName();
            if ($result) { // form soumis ok
                $this->addFlash('light#pencil-light', $client . '--Consultation mise à jour.');

                $close = $form['fermeture']->getData();
                if ($currUser->isVoyant() && $close) {
                    return $this->jsonResponse(['redirect_uri' => $this->get('router')->generate('kgc_dashboard', [], true)]);
                } elseif (!$close) {
                    return $this->redirect($this->get('router')->generate('kgc_rdv_fiche', ['id' => $rdv->getId()]));
                }
            } elseif ($result === false) { // form soumis erreur
                $this->addFlash('error#pencil', $client . '--Consultation non enregistrée.');
            }
            $request->getSession()->remove('original_rdv');
        }
        $vue = $currUser->isVoyant() ? 'fiche.voyant' : 'fiche';

        return $this->render('KGCRdvBundle:Consultation:' . $vue . '.html.twig', array(
            'rdv' => $rdv,
            'form' => isset($form) ? $form->createView() : null,
            'form_edit' => (isset($form_edit) and !$currUser->isQualite()) ? $form_edit->createView() : null,
            'close' => isset($close) ? $close : null,
            'forceEmpty' => $forceEmpty,
        ));
    }

    /**
     * Mettre en pause une consultation pour la terminer plus tard dans la journée.
     *
     * @param $id
     *
     * @return Response
     *
     * @Secure(roles="ROLE_VOYANT")
     */
    public function PauseAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $rdv = $this->findById($id);
        if ($rdv) {
            $request = $this->get('request');
            $form_edit = $this->createForm(new Form\RDVEditType($rdv));
            $form_edithandler = new Handler\RDVEditHandler($form_edit, $request);
            $param_edit = $form_edithandler->process();
            $currUser = $this->getUser();
            $historiqueManager = $this->get('kgc.client.historique.manager');
            $formType = new Form\RDVPauseType($currUser, $em, $historiqueManager, $param_edit);
            $form = $this->createForm($formType, $rdv);
            $formhandler = $this->get('kgc.rdv.formhandler');
            $result = $formhandler->process($form, $request);
            $client = $rdv->getClient()->getFullName();
            if ($result === true) { // form submit valid
                $this->addFlash('light#ok-light', $client . '--Consultation reconduite.');

                return $this->jsonResponse(['redirect_uri' => $this->get('router')->generate('kgc_dashboard', [], true)]);
            } elseif ($result === false) { // form submit invalid
                $this->addFlash('error#ok', $client . '--Consultation non reconduite.');
            }
        }

        return $this->render('KGCRdvBundle:Consultation:effectuer.html.twig', array(
            'rdv' => isset($rdv) ? $rdv : null,
            'form' => isset($form) ? $form->createView() : null,
            'form_edit' => isset($form_edit) ? $form_edit->createView() : null,
        ));
    }

    /**
     * confirme la réalisation de la consultation avec données de facturation.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * @Secure(roles="ROLE_VOYANT")
     */
    public function EffectuerAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $rdv = $this->findById($id);
        if ($rdv) {
            $request = $this->get('request');
            $form_edit = $this->createForm(new Form\RDVEditType($rdv));
            $form_edithandler = new Handler\RDVEditHandler($form_edit, $request);
            $param_edit = $form_edithandler->process();
            $currUser = $this->getUser();
            $historiqueManager = $this->get('kgc.client.historique.manager');
            $formType = new Form\RDVValidationType($currUser, $em, $historiqueManager, $param_edit);
            $form = $this->createForm($formType, $rdv);
            $formhandler = $this->get('kgc.rdv.formhandler');
            $result = $formhandler->process($form, $request);
            $client = $rdv->getClient()->getFullName();
            if ($result === true) { // form submit valid
                $this->addFlash('light#ok-light', $client . '--Consultation validée.');

                return $this->jsonResponse(['redirect_uri' => $this->get('router')->generate('kgc_dashboard', [], true)]);
            } elseif ($result === false) { // form submit invalid
                $this->addFlash('error#ok', $client . '--Consultation non validée.');
            }
        }

        return $this->render('KGCRdvBundle:Consultation:effectuer.html.twig', array(
            'rdv' => isset($rdv) ? $rdv : null,
            'form' => isset($form) ? $form->createView() : null,
            'form_edit' => isset($form_edit) ? $form_edit->createView() : null,
        ));
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_QUALITE, ROLE_VALIDATION, ROLE_UNPAID_SERVICE, ROLE_MANAGER_PHONE")
     */
    public function BuildMailAction(Request $request)
    {
        $id = $request->request->getInt('id');
        $rdvId = $request->request->getInt('rdv');

        $data = [
            'subject' => '',
            'html' => '',
        ];

        if ($id && $rdvId) {
            $em = $this->getDoctrine()->getManager();
            $mail = $em
                ->getRepository('KGCClientBundle:Mail')
                ->getOneById($id);

            $baseUrl = $request->getSchemeAndHttpHost();
            $transformer = $this->get('kgc.client.mail.transformer');
            $transformer->transform($baseUrl, $mail, $rdvId);

            $data = [
                'subject' => $mail->getSubject(),
                'html' => $mail->getHtml(),
            ];
        }

        return $this->jsonResponse($data);
    }

    /**
     * @param $id
     *
     * @return Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_QUALITE, ROLE_VALIDATION, ROLE_UNPAID_SERVICE, ROLE_MANAGER_PHONE")
     */
    public function PrepareMailAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $rdv = $this->findById($id);

        if ($rdv) {
            $mails = $em->getRepository('KGCClientBundle:MailSent')->getMailHistoryByRdv($rdv->getId());
            $currUser = $this->getUser();
            if ('chat' === $this->get('session')->get('dashboard')) {
                $tchat = 1;
            } else {
                $tchat = 0;
            }
            $form = $this->createForm(new Form\RDVMailType($currUser, $em, array(), $tchat), $rdv);
            $formhandler = $this->get('kgc.rdv.formhandler');
            $result = $formhandler->process($form, $request);
            $client = $rdv->getClient()->getFullName();
            $mail = $form->get('mail_sent')->getData();
            $statusMail = $mail && MailSent::STATUS_ERROR === $mail->getStatus() ? false : null;

            if ($result === true && false !== $statusMail) { // form submit valid
                $this->addFlash('light#enveloppe-light', $client . '--Mail envoyé.');

                return $this->redirect($this->get('router')->generate('kgc_rdv_fiche', array(
                    'id' => $rdv->getId(),
                    'forceEmpty' => true,
                )));
            } elseif ($result === false || false === $statusMail) { // form submit invalid
                $this->addFlash('error#enveloppe', $client . '--Mail non envoyé.');

                $mails = $em->getRepository('KGCClientBundle:MailSent')->getMailHistoryByRdv($rdv->getId());
                $form->addError(new FormError('Une erreur est survenue, le mail nʼa pas été envoyé.'));
            }
        }

        return $this->render('KGCRdvBundle:Consultation:prepare_mail.html.twig', array(
            'rdv' => isset($rdv) ? $rdv : null,
            'form' => isset($form) ? $form->createView() : null,
            'mails' => $mails,
        ));
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_QUALITE, ROLE_VALIDATION, ROLE_UNPAID_SERVICE, ROLE_MANAGER_PHONE")
     */
    public function BuildSmsAction(Request $request)
    {
        $id = $request->request->getInt('id');
        $rdvId = $request->request->getInt('rdv');

        $data = [
            'text' => '',
        ];

        if ($id && $rdvId) {
            $em = $this->getDoctrine()->getManager();
            $sms = $em
                ->getRepository('KGCClientBundle:Sms')
                ->getOneById($id);

            $baseUrl = $request->getSchemeAndHttpHost();
            $transformer = $this->get('kgc.client.sms.transformer');
            $transformer->transform($baseUrl, $sms, $rdvId);

            $data = [
                'text' => $sms->getText(),
            ];
        }

        return $this->jsonResponse($data);
    }

    /**
     * @param $id
     *
     * @return Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_QUALITE, ROLE_VALIDATION, ROLE_UNPAID_SERVICE, ROLE_MANAGER_PHONE")
     */
    public function PrepareSmsAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $rdv = $this->findById($id);

        if ($rdv) {
            $smss = $em->getRepository('KGCClientBundle:SmsSent')->getSmsHistoryByRdv($rdv->getId());
            $currUser = $this->getUser();
            $phone = $rdv->getClient()->getNumtel1() != "" ? $rdv->getClient()->getNumtel1() : $rdv->getClient()->getNumte2();
            if ('chat' === $this->get('session')->get('dashboard')) {
                $tchat = 1;
            } else {
                $tchat = 0;
            }
            $form = $this->createForm(new Form\RDVSmsType($currUser, $em, array(), $phone, $tchat), $rdv);
            $formhandler = $this->get('kgc.rdv.formhandler');
            $result = $formhandler->process($form, $request);
            $client = $rdv->getClient()->getFullName();
            $sms = $form->get('sms_sent')->getData();
            $statusSms = $sms && SmsSent::STATUS_ERROR === $sms->getStatus() ? false : null;

            if ($result === true && false !== $statusSms) { // form submit valid
                $this->addFlash('light#enveloppe-light', $client . '--Sms envoyé.');

                return $this->redirect($this->get('router')->generate('kgc_rdv_fiche', array(
                    'id' => $rdv->getId(),
                    'forceEmpty' => true,
                )));
            } elseif ($result === false || false === $statusSms) { // form submit invalid
                $this->addFlash('error#enveloppe', $client . '--Sms non envoyé.');

                $smss = $em->getRepository('KGCClientBundle:SmsSent')->getSmsHistoryByRdv($rdv->getId());
                $form->addError(new FormError('Une erreur est survenue, le sms nʼa pas été envoyé.'));
            }
        }

        return $this->render('KGCRdvBundle:Consultation:prepare_sms.html.twig', array(
            'rdv' => isset($rdv) ? $rdv : null,
            'form' => isset($form) ? $form->createView() : null,
            'sms' => $smss,
        ));
    }

    /**
     * modifie la date de la consultation.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * @Secure(roles="ROLE_VOYANT, ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONIST, ROLE_QUALITE, ROLE_VALIDATION, ROLE_MANAGER_PHONE")
     */
    public function ReporterAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $rdv = $this->findById($id);
        if ($rdv) {
            $request = $this->get('request');
            $currUser = $this->getUser();
            $form = $this->createForm(new Form\RDVReportType($currUser, $em), $rdv);
            $formhandler = $this->get('kgc.rdv.formhandler');
            $result = $formhandler->process($form, $request);
            $client = $rdv->getClient()->getFullName();
            if ($result === true) { // form submit valid
                $this->addFlash('light#reply-light', $client . '--Consultation reportée.');

                return $currUser->isVoyant()
                    ? $this->jsonResponse(['redirect_uri' => $this->get('router')->generate('kgc_dashboard', [], true)])
                    : $this->redirect($this->get('router')->generate('kgc_rdv_fiche', array('id' => $rdv->getId())));
            } elseif ($result === false) { // form submit invalid
                $this->addFlash('error#reply', $client . '--Consultation non reportée.');
            }
        }

        return $this->render('KGCRdvBundle:Consultation:reporter.html.twig', array(
            'rdv' => isset($rdv) ? $rdv : null,
            'form' => isset($form) ? $form->createView() : null,
        ));
    }

    /**
     * permet de réactiver une consultation annulée.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_QUALITE, ROLE_MANAGER_PHONE")
     */
    public function ReactiverAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $rdv = $this->getRepository()->findOneById($id);
        $manager = $this->get('kgc.rdv.manager');
        if ($rdv && $manager->isReactivable($rdv)) {
            $request = $this->get('request');
            $currUser = $this->getUser();
            $form = $this->createForm(new Form\RDVReactiverType($currUser, $em), $rdv);
            $formhandler = $this->get('kgc.rdv.formhandler');
            $result = $formhandler->process($form, $request);
            $client = $rdv->getClient()->getFullName();
            if ($result === true) { // form submit valid
                $role = $this->getUser()->getMainprofil()->getRoleKey();
                if(in_array($role, ['std_dri_j1', 'j_1'])){
                    $rdv->setProprio($this->getUser());
                    $em->persist($rdv);
                    $em->flush();
                }
                $this->addFlash('light#reply-light', $client . '--Consultation réactivée.');

                return $this->redirect($this->get('router')->generate('kgc_rdv_fiche', array('id' => $rdv->getId())));
            } elseif ($result === false) { // form submit invalid
                $this->addFlash('error#reply', $client . '--Consultation non réactivée.');
            }
        }

        return $this->render('KGCRdvBundle:Consultation:reactiver.html.twig', array(
            'rdv' => isset($rdv) ? $rdv : null,
            'form' => isset($form) ? $form->createView() : null,
        ));
    }

    /**
     * Méthode annuler : annule la consultation.
     *
     * @param $id
     * @param $comportement
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * @Secure(roles="ROLE_VOYANT, ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_QUALITE, ROLE_MANAGER_PHONIST, ROLE_VALIDATION, ROLE_MANAGER_PHONE")
     */
    public function AnnulerAction($id, $comportement)
    {
        $em = $this->getDoctrine()->getManager();
        $rdv = $this->findById($id);
        if ($rdv) {
            $request = $this->get('request');
            $currUser = $this->getUser();
            $form = $this->createForm(new Form\RDVAnnulerType($currUser, $em), $rdv);
            $formhandler = $this->get('kgc.rdv.formhandler');
            $result = $formhandler->process($form, $request);
            $client = $rdv->getClient()->getFullName();
            if ($result === true) {
                $this->addFlash('light#remove-light', $client . '--Consultation annulée.');

                return $currUser->isVoyant()
                    ? $this->jsonResponse(['redirect_uri' => $this->get('router')->generate('kgc_dashboard', [], true)])
                    : $this->redirect($this->get('router')->generate('kgc_rdv_fiche', array('id' => $rdv->getId())));
            } elseif ($result === false) {
                $this->addFlash('error#remove', $client . '--Consultation non annulée.');
            }
        }

        return $this->render('KGCRdvBundle:Consultation:annuler.html.twig', array(
            'rdv' => isset($rdv) ? $rdv : null,
            'form' => isset($form) ? $form->createView() : null,
            'comportement' => $comportement,
        ));
    }

    /**
     * Méthode classement: modifier le classement de la consultation.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_QUALITE, ROLE_MANAGER_PHONIST, ROLE_MANAGER_PHONE, ROLE_MANAGER_PHONE, ROLE_UNPAID_SERVICE, ROLE_VALIDATION, ROLE_MANAGER_STANDAR, ROLE_J_1, ROLE_VALIDATION, ROLE_PHONISTE, ROLE_PHONING_TODAY")
     */
    public function ClasserAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $rdv = $this->findById($id);
        if ($rdv) {
            $request = $this->get('request');
            $currUser = $this->getUser();
            $form = $this->createForm(new Form\RDVClasserType($currUser, $em), $rdv);
            $formhandler = $this->get('kgc.rdv.formhandler');
            $result = $formhandler->process($form, $request);
            $client = $rdv->getClient()->getFullName();
            if ($result === true) { // form submit valid
                $this->addFlash('light#folder-open-light', $client . '--Consultation classée.');

                return $this->redirect($this->get('router')->generate('kgc_rdv_fiche', array('id' => $rdv->getId())));
            } elseif ($result === false) { // form submit invalid
                $this->addFlash('error#folder-open', $client . '--Consultation non classée.');
            }
        }

        return $this->render('KGCRdvBundle:Consultation:classer.html.twig', array(
            'rdv' => isset($rdv) ? $rdv : null,
            'form' => isset($form) ? $form->createView() : null,
        ));
    }

    /**
     * Méthode cloturees : widget liste des dernières consultations clôturées.
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function WidgetClotureesAction()
    {
        $liste = $this->getRepository()->getByState(Etat::CLOSED, self::DASHBOARD_CLOSED_LIMIT);

        return $this->render('KGCRdvBundle:Consultation:cloturees.widget.html.twig', array(
            'liste' => $liste,
        ));
    }

    /**
     * Widget liste des dernières consultations annulées.
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function annuleesWidgetAction()
    {
        $liste = $this->getRepository()->getByState(Etat::CANCELLED, self::DASHBOARD_CANCELLED_LIMIT);

        return $this->render('KGCRdvBundle:Consultation:annulees.widget.html.twig', array(
            'liste' => $liste,
        ));
    }

    /**
     * Widget liste des consultation en pause pour le voyant connecté.
     *
     * @Secure(roles="ROLE_VOYANT")
     */
    public function pauseWidgetAction()
    {
        $liste = $this->getRepository()
            ->getByState(Etat::PAUSED, null, $this->getUser());

        return $this->render('KGCRdvBundle:Consultation:pause.widget.html.twig', array(
            'liste' => $liste,
        ));
    }

}
