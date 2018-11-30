<?php
// src/KGC/DashboardBundle/Controller/DashboardController.php

namespace KGC\DashboardBundle\Controller;

use KGC\RdvBundle\Controller\ConsultationController;
use KGC\RdvBundle\Elastic\Paginator\ElasticPaginator;
use KGC\RdvBundle\Entity\RDV;
use KGC\RdvBundle\Form\RDVAjouterType;
use KGC\UserBundle\Controller\ElasticController;
use KGC\UserBundle\Elastic\Model\ProspectSearch;
use KGC\UserBundle\Form\ProspectEditType;
use KGC\UserBundle\Form\ProspectType;
use KGC\UserBundle\Handler\ProspectEditHandler;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Request;

/**
 * DashboardController.
 *
 * @category Controller
 *
 * @author Laurene Dourdin <2aurene@gmail.com>
 */
class DashboardController extends Controller
{
    /**
     * Méthode index. Page d'accueil de l'application.
     *
     *
     * @param type $varname Description
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     */
    public function indexAction(Request $request)
    {
        $role = $this->getUser()->getMainprofil()->getRoleKey();
        if ($role === 'admin') {
            if ('chat' === $this->get('session')->get('dashboard')) {
                $role = 'chat';
            } else {
                $role = 'standard';
            }
        } elseif ($role === 'manager_standar' || $role === 'std_dri_j1') {
            $role = 'simple_standard';
        } elseif (in_array($role, ['admin_phone'])) {
            $role = 'standard';
        } elseif (in_array($role, ['admin_chat', 'manager_chat'])) {
            $this->get('session')->set('dashboard', 'chat');
            $role = 'chat';
        } elseif ($role === 'dri') {
            return $this->redirect($this->get('router')->generate('kgc_dri_page'));
        } elseif ($role === 'j_1') {
            return $this->redirect($this->get('router')->generate('kgc_service_j1'));
        } elseif ($role === 'manager_phone' || $role === "standard" || $role === 'unpaid_service') {
            $role = 'standard';
        } elseif ($role === 'manager_phonist') {
            $role = 'manager_phoniste';
        }  elseif ($role === 'validation') {
            return $this->redirect($this->get('router')->generate('kgc_service_validation'));
        } elseif ($role === 'voyant') {
            return $this->indexVoyant();
        } elseif ($role === 'qualite') {
            return $this->redirect($this->get('router')->generate('kgc_qualite_page'));
        } elseif ($role === 'affiliate') {
            return $this->redirect($this->get('router')->generate('kgc_admin_specific'));
        }

        return $this->render('KGCDashboardBundle:Dashboard:' . $role . '.html.twig');
    }

    /**
     * Méthode step1. Recherche par prospect.
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_PHONISTE, ROLE_MANAGER_PHONIST")
     */
    public function step1Action(Request $request)
    {
        $controler = new ElasticController();
        $controler->setContainer($this->container);
        return $controler->searchProspectAction('phonist', $request);
    }


    /**
     * Méthode step2. Fiche client.
     * @param Request $request
     * @param int $id
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_PHONISTE, ROLE_MANAGER_PHONIST")
     */
    public function step2Action(Request $request, $id)
    {
        $forceEmpty = $request->query->get('forceEmpty');
        $forceEmpty = $forceEmpty ?: false;

        $prospect = $this->getDoctrine()->getManager()->getRepository('KGCSharedBundle:LandingUser')->find($id);
        $em = $this->getDoctrine()->getManager();
        $website = null;
        if ($prospect) {
            $request = $this->get('request');
            $request->getSession()->set('original_prospect', clone $prospect);
            $form_edit = $this->createForm(new ProspectEditType($prospect));
            $form_edithandler = new ProspectEditHandler($form_edit, $request);
            $param_edit = $form_edithandler->process();
            $website = $prospect->getWebsite() ?: $this->getDoctrine()->getManager()->getRepository('KGCSharedBundle:Website')->getWebsiteByAssociationName($prospect->getMyastroWebsite(), false);
            $source = $prospect->getSourceConsult() ?: $this->getDoctrine()->getManager()->getRepository('KGCRdvBundle:Source')->getSourceByAssociationName($prospect->getMyastroSource());
            $codePromo = $prospect->getCodePromo() ?: $this->getDoctrine()->getManager()->getRepository('KGCRdvBundle:CodePromo')->findOneByCode(strtoupper($prospect->getMyastroPromoCode()));
            $voyant = $prospect->getVoyant() ?: $this->getDoctrine()->getManager()->getRepository('KGCUserBundle:Voyant')->findOneByNom($prospect->getMyastroPsychic());
            if (!$prospect->getFormurl()) {
                $find = ['label' => strtolower($prospect->getMyastroUrl())];
                if (!empty($website)) {
                    $find['website'] = $website;
                }
                if (!empty($source)) {
                    $find['source'] = $source;
                }
                $formurl = $this->getDoctrine()->getManager()->getRepository('KGCRdvBundle:FormUrl')->findOneBy($find);
            } else {
                $formurl = $prospect->getFormurl();
            }
            $support = $prospect->getSupport() ?: $this->getDoctrine()->getManager()->getRepository('KGCRdvBundle:Support')->findOneByLibelle($prospect->getMyastroSupport());
            $linkEntities = ['website' => $website, 'source' => $source, 'codePromo' => $codePromo, 'formurl' => $formurl, 'voyant' => $voyant, 'state' => $prospect->getState(), 'support' => $support];
            $formType = new ProspectType($this->getUser(), $param_edit, $em, $linkEntities);
            $form = $this->createForm($formType, $prospect);
            $formhandler = $this->get('kgc.prospect.formhandler');
            $result = $formhandler->process($form, $request);
            if ($result !== null) { // submit
                return $this->redirect($this->generateUrl('kgc_dashboard_phoning22', array('id' => $prospect->getId())));
            } else {
                return $this->render('KGCDashboardBundle:Dashboard:phoniste2.html.twig', array(
                    'prospect' => $prospect,
                    'form' => isset($form) ? $form->createView() : null,
                    'form_edit' => (isset($form_edit)) ? $form_edit->createView() : null,
                    'linkEntities' => $linkEntities,
                    'close' => isset($close) ? $close : null,
                    'options' => ['rdv_btn' => false, 'btn_save' => true, 'fermeture' => true],
                    'forceEmpty' => $forceEmpty,
                    'consultation' => false
                ));
            }
        }
    }


    /**
     * Méthode step22
     *
     * @param int $id
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_PHONISTE, ROLE_MANAGER_PHONIST")
     */
    public function step22Action($id)
    {
        $vue = 'widget';
        $prospect = $this->getDoctrine()->getRepository('KGCSharedBundle:LandingUser')->find($id);
        $currUser = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $rdv = new RDV($currUser);
        $toComplete = [];
        if ($prospect) {
            $website = $this->getDoctrine()->getRepository('KGCSharedBundle:Website')->getWebsiteByAssociationName($prospect->getMyastroWebsite(), false);
            if ($website) {
                $rdv->setWebsite($website);
            }
            $source = $this->getDoctrine()->getRepository('KGCRdvBundle:Source')->getSourceByAssociationName($prospect->getMyastroSource());
            if ($source) {
                $rdv->setSource($source);
            }
            $voyant = $this->getDoctrine()->getRepository('KGCUserBundle:Voyant')->findOneByNom($prospect->getMyastroPsychic());
            if ($voyant) {
                $rdv->setVoyant($voyant);
            }
            $codePromo = $this->getDoctrine()->getRepository('KGCRdvBundle:CodePromo')->findOneByCode(strtoupper($prospect->getMyastroPromoCode()));
            if ($codePromo) {
                $rdv->setCodePromo($codePromo);
            }
            $find = ['label' => strtolower($prospect->getMyastroUrl())];
            if (!empty($website)) {
                $find['website'] = $website;
            }
            if (!empty($source)) {
                $find['source'] = $source;
            }
            $formurl = $this->getDoctrine()->getRepository('KGCRdvBundle:FormUrl')->findOneBy($find);
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
            $client = ($prospect->getClient()) ?: $this->getDoctrine()->getRepository('KGCSharedBundle:LandingUser')->getProspectClient($prospect);
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

                $rdvClient = $this->getDoctrine()->getRepository('KGCRdvBundle:RDV')->findOneByClient($client);
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
        $form = $this->createForm(new RDVAjouterType($currUser, $em), $rdv);
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
                $form = $this->createForm(new RDVAjouterType($currUser, $em), new RDV($currUser)); // reset du formulaire
            } elseif ($result === false) { // submit invalid
                $this->addFlash('error#plus', $client . '--Consultation non ajoutée.');
            }
        }

        return $this->render('KGCDashboardBundle:Dashboard:phoniste3.html.twig', array(
            'prospect' => $prospect,
            'toComplete' => $toComplete
        ));
    }

    /**
     * Méthode step3. Ajout d'une consultation.
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_PHONISTE, ROLE_MANAGER_PHONIST")
     */
    public function step3Action()
    {

        return $this->render('KGCDashboardBundle:Dashboard:phoniste3.html.twig');
    }

    public function switchAction()
    {
        if ('chat' === $this->get('session')->get('dashboard')) {
            $this->get('session')->set('dashboard', 'standard');
        } else {
            $this->get('session')->set('dashboard', 'chat');
        }
        return $this->redirect($this->get('router')->generate('kgc_dashboard'));
    }

    protected function indexVoyant()
    {
        $em = $this->getDoctrine()->getManager();
        $rep_rdv = $em->getRepository('KGCRdvBundle:RDV');
        $rdv = $rep_rdv->getPEC($this->getUser());

        return $rdv
            ? $this->render('KGCDashboardBundle:Dashboard:voyant.rdv.html.twig', ['rdv' => $rdv])
            : $this->render('KGCDashboardBundle:Dashboard:voyant.planning.html.twig');
    }

    /**
     * Méthode version.
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="IS_AUTHENTICATED_REMEMBERED")
     */
    public function versionAction()
    {
        return $this->render('KGCDashboardBundle:Dashboard:version.html.twig');
    }

    /**
     * Méthode de rendu du menu en fonction des droits.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function menuAction($routeName)
    {
        $searchRoutes = [
            'kgc_search_page',
            'kgc_search_client_page',
            'kgc_search_prospect_page',
            'kgc_search_rdv_page'
        ];
        $adminRoutes = [
            'kgc_admin_general',
            'kgc_admin_specific',
            'kgc_admin_psychic',
            'kgc_admin_phoning',
            'kgc_admin_roi',
            'kgc_admin_ca',
            'kgc_admin_unpaid',
            'kgc_admin_customers',
            'kgc_admin_telecollecte',
        ];
        $adminTchatRoutes = [
            'kgc_admin_general_tchat',
            'kgc_admin_specific_tchat',
            'kgc_admin_abo_tchat',
        ];
        $smsRoutes = [
            'kgc_sms_general',
            'kgc_sms_model',
            'kgc_sms_list_model',
        ];

        $configRoutes = [
            'kgc_config_codepromo',
            'kgc_config_etiquette',
            'kgc_config_mail',
            'kgc_config_payment',
            'kgc_config_poste',
            'kgc_config_products',
            'kgc_config_salary',
            'kgc_config_sms',
            'kgc_config_statistic',
            'kgc_config_support',
            'kgc_config_tarification',
            'kgc_config_tracking',
            'kgc_config_website',
        ];

        $pages = [
            'DASHBOARD' => ['route' => 'kgc_dashboard',
                'intitule' => 'Tableau de bord', 'icone' => 'dashboard',
                'active' => 'kgc_dashboard' === $routeName || 'kgc_homepage' === $routeName,
            ],
            'SEARCH' => ['route' => 'kgc_search_page',
                'intitule' => 'Recherche', 'icone' => 'search',
                'active' => in_array($routeName, $searchRoutes),
                'subs' => [
                    [
                        'route' => 'kgc_search_client_page',
                        'intitule' => 'Client',
                        'active' => 'kgc_search_client_page' === $routeName,
                    ],
                    [
                        'route' => 'kgc_search_prospect_page',
                        'intitule' => 'Prospect',
                        'active' => 'kgc_search_prospect_page' === $routeName,
                    ],
                    [
                        'route' => 'kgc_search_rdv_page',
                        'intitule' => 'Consultation',
                        'active' => 'kgc_search_rdv_page' === $routeName,
                    ],
                ],
            ],
            'SEARCH_PHONISTE' => ['route' => 'kgc_search_phoniste_page',
                'intitule' => 'Recherche', 'icone' => 'search',
                'active' => in_array($routeName, ['kgc_search_phoniste_page']),
                'subs' => [
                    [
                        'route' => 'kgc_search_phoniste_page',
                        'intitule' => 'ID consultation',
                        'active' => 'kgc_search_phoniste_page' === $routeName,
                    ],
                ],
            ],
            'VALIDATION' => ['route' => 'kgc_service_validation',
                'intitule' => 'Validation', 'icone' => 'check',
                'active' => 'kgc_service_validation' === $routeName,
            ],
            'CUSTOMERS' => ['route' => 'kgc_clients_page',
                'intitule' => 'Clients', 'icone' => 'shopping-cart',
                'active' => 'kgc_clients_page' === $routeName,
            ],
            'PLANNING' => ['route' => 'kgc_planning_page',
                'intitule' => 'Planning', 'icone' => 'calendar',
                'active' => 'kgc_planning_page' === $routeName,
            ],
            'PLANNING_TCHAT' => ['route' => 'kgc_tchatplanning_page',
                'intitule' => 'Planning', 'icone' => 'calendar',
                'active' => 'kgc_tchatplanning_page' === $routeName,
            ],
            'UNPAID' => ['route' => 'kgc_impaye_page',
                'intitule' => 'Impayés', 'icone' => 'euro',
                'active' => 'kgc_impaye_page' === $routeName,
            ],
            'QUALITY' => ['route' => 'kgc_qualite_page',
                'intitule' => 'Qualité', 'icone' => 'bookmark',
                'active' => 'kgc_qualite_page' === $routeName,
            ],
            'PRODUCTS' => ['route' => 'kgc_products_page',
                'intitule' => 'Envoi des produits', 'icone' => 'truck',
                'active' => 'kgc_products_page' === $routeName,
            ],
            'FORMULES' => ['route' => 'kgc_dashboard_construction',
                'intitule' => 'Formules', 'icone' => 'suitcase',
                'active' => 'kgc_dashboard_construction' === $routeName,
            ],
            'DRI' => ['route' => 'kgc_dri_page',
                'intitule' => 'DRI', 'icone' => 'phone',
                'badge' => $this->getDoctrine()->getRepository('KGCSharedBundle:LandingUser')->getNbDRI(),
                'active' => 'kgc_dri_page' === $routeName,
            ],
            'J1' => ['route' => 'kgc_service_j1',
                'intitule' => 'Service J-1', 'icone' => 'minus',
                'active' => 'kgc_service_j1' === $routeName,
            ],
            'TRACKING' => ['route' => 'kgc_tracking_page',
                'intitule' => 'Tracking', 'icone' => 'retweet',
                'active' => 'kgc_tracking_page' === $routeName,
            ],
            'LEADS' => ['route' => 'kgc_leads_page',
                'intitule' => 'Leads', 'icone' => 'bullseye',
                'active' => 'kgc_leads_page' === $routeName,
            ],
            'USERS' => ['route' => 'kgc_utilisateurs_page',
                'intitule' => 'Utilisateurs', 'icone' => 'group',
                'active' => 'kgc_utilisateurs_page' === $routeName,
            ],
            'SMS' => ['route' => 'kgc_sms_general',
                'intitule' => 'Campagne de SMS', 'icone' => 'comments',
                'active' => in_array($routeName, $smsRoutes),
                'subs' => [
                    [
                        'route' => 'kgc_sms_model',
                        'intitule' => 'Modèles de campagne',
                        'active' => 'kgc_sms_model' === $routeName,
                    ],
                    [
                        'route' => 'kgc_sms_list_model',
                        'intitule' => 'Liste de contact',
                        'active' => 'kgc_sms_list_model' === $routeName,
                    ],
                ],
            ],
            'ADMIN' => ['route' => 'kgc_admin_general',
                'intitule' => 'Statistiques', 'icone' => 'bar-chart',
                'active' => in_array($routeName, $adminRoutes),
                'subs' => [
                    [
                        'route' => 'kgc_admin_specific',
                        'intitule' => 'Stats spécifiques',
                        'active' => 'kgc_admin_specific' === $routeName,
                    ],
                    [
                        'route' => 'kgc_admin_psychic',
                        'intitule' => 'Stats voyants',
                        'active' => 'kgc_admin_psychic' === $routeName,
                    ],
                    [
                        'route' => 'kgc_admin_phoning',
                        'intitule' => 'Stats phonistes',
                        'active' => 'kgc_admin_phoning' === $routeName,
                    ],
                    [
                        'route' => 'kgc_admin_ca',
                        'intitule' => 'Pointage CA',
                        'active' => 'kgc_admin_ca' === $routeName,
                    ],
                    [
                        'route' => 'kgc_admin_telecollecte',
                        'intitule' => 'Pointage télécollectes',
                        'active' => 'kgc_admin_telecollecte' === $routeName,
                    ],
                    [
                        'route' => 'kgc_admin_unpaid',
                        'intitule' => 'Stats impayés',
                        'active' => 'kgc_admin_unpaid' === $routeName,
                    ],
                ],
            ],
            'ADMIN_AFFILIATE' => ['route' => 'kgc_admin_general',
                'intitule' => 'Statistiques', 'icone' => 'bar-chart',
                'active' => in_array($routeName, $adminRoutes),
                'subs' => [
                    [
                        'route' => 'kgc_admin_specific',
                        'intitule' => 'Stats spécifiques',
                        'active' => 'kgc_admin_specific' === $routeName,
                    ],
                ],
            ],
            'ADMIN_TCHAT' => ['route' => 'kgc_admin_general',
                'intitule' => 'Statistiques', 'icone' => 'bar-chart',
                'active' => in_array($routeName, $adminTchatRoutes),
                'subs' => [
//                    [
//                        'route' => 'kgc_admin_specific_tchat',
//                        'intitule' => 'Stats spécifiques',
//                        'active' => 'kgc_admin_specific_tchat' === $routeName,
//                    ],
                    [
                        'route' => 'kgc_admin_abo_tchat',
                        'intitule' => 'Stats abos',
                        'active' => 'kgc_admin_abo_tchat' === $routeName,
                    ],
                ],
            ],
            'CONFIG' => ['route' => 'kgc_config_salary',
                'intitule' => 'Réglages', 'icone' => 'cogs',
                'active' => in_array($routeName, $configRoutes),
                'subs' => [
                    [
                        'route' => 'kgc_config_salary',
                        'intitule' => 'Bonus/Primes',
                        'active' => 'kgc_config_salary' === $routeName,
                    ],
                    [
                        'route' => 'kgc_config_codepromo',
                        'intitule' => 'Codes promo',
                        'active' => 'kgc_config_codepromo' === $routeName,
                    ],
                    [
                        'route' => 'kgc_config_etiquette',
                        'intitule' => 'Étiquettes',
                        'active' => 'kgc_config_etiquette' === $routeName,
                    ],
                    [
                        'route' => 'kgc_config_mail',
                        'intitule' => 'Mails',
                        'active' => 'kgc_config_mail' === $routeName,
                    ],
                    [
                        'route' => 'kgc_config_poste',
                        'intitule' => 'Poste téléphonique',
                        'active' => 'kgc_config_poste' === $routeName,
                    ],
                    [
                        'route' => 'kgc_config_products',
                        'intitule' => 'Produits',
                        'active' => 'kgc_config_products' === $routeName,
                    ],
                    [
                        'route' => 'kgc_config_website',
                        'intitule' => 'Sites',
                        'active' => 'kgc_config_website' === $routeName,
                    ],
                    [
                        'route' => 'kgc_config_sms',
                        'intitule' => 'Sms',
                        'active' => 'kgc_config_sms' === $routeName,
                    ],
                    [
                        'route' => 'kgc_config_statistic',
                        'intitule' => 'Statistiques',
                        'active' => 'kgc_config_statistic' === $routeName,
                    ],
                    [
                        'route' => 'kgc_config_support',
                        'intitule' => 'Supports',
                        'active' => 'kgc_config_support' === $routeName,
                    ],
                    [
                        'route' => 'kgc_config_tarification',
                        'intitule' => 'Tarification',
                        'active' => 'kgc_config_tarification' === $routeName,
                    ],
                    [
                        'route' => 'kgc_config_payment',
                        'intitule' => 'TPE/Paiement',
                        'active' => 'kgc_config_payment' === $routeName,
                    ],
                    [
                        'route' => 'kgc_config_tracking',
                        'intitule' => 'Tracking',
                        'active' => 'kgc_config_tracking' === $routeName,
                    ],
                ],
            ],
            'CONFIG_TCHAT' => ['route' => 'kgc_config_mail',
                'intitule' => 'Réglages', 'icone' => 'cogs',
                'active' => in_array($routeName, $configRoutes),
                'subs' => [
                    [
                        'route' => 'kgc_config_mail',
                        'intitule' => 'Mails',
                        'active' => 'kgc_config_mail' === $routeName,
                    ],
                    [
                        'route' => 'kgc_config_sms',
                        'intitule' => 'Sms',
                        'active' => 'kgc_config_sms' === $routeName,
                    ],
                    [
                        'route' => 'kgc_config_formule',
                        'intitule' => 'Formules',
                        'active' => 'kgc_config_formule' === $routeName,
                    ],
                    [
                        'route' => 'kgc_config_website',
                        'intitule' => 'Sites',
                        'active' => 'kgc_config_website' === $routeName,
                    ],
                    [
                        'route' => 'kgc_config_voyant',
                        'intitule' => 'Voyants',
                        'active' => 'kgc_config_voyant' === $routeName,
                    ],
                ],
            ],
        ];
        $roleKey = $this->getUser()->getMainProfil()->getRoleKey();
        if ('chat' === $this->get('session')->get('dashboard') || in_array($roleKey, ['admin_chat', 'manager_chat'])) {
            $pages['SEARCH'] = ['route' => 'kgc_search_page',
                'intitule' => 'Recherche', 'icone' => 'search',
                'active' => in_array($routeName, $searchRoutes),
                'subs' => [
                    [
                        'route' => 'kgc_search_client_page',
                        'intitule' => 'Client',
                        'active' => 'kgc_search_client_page' === $routeName,
                    ],
                    [
                        'route' => 'kgc_search_prospect_page',
                        'intitule' => 'Prospect',
                        'active' => 'kgc_search_prospect_page' === $routeName,
                    ],
                ],
            ];
        }

        switch ($roleKey) {
            case 'affiliate':
                $liste[] = $pages['ADMIN_AFFILIATE'];
                break;
            case 'phoniste':
            case 'phoning_today':
                $liste[] = $pages['DASHBOARD'];
                $liste[] = $pages['SEARCH_PHONISTE'];
                break;
            case 'validation':
                $liste[] = $pages['SEARCH'];
                $liste[] = $pages['VALIDATION'];
                break;
            case 'voyant':
                $liste[] = $pages['DASHBOARD'];
                $liste[] = ['route' => 'kgc_admin_psychic',
                    'intitule' => 'Statistiques', 'icone' => 'bar-chart',
                    'active' => 'kgc_admin_psychic' === $routeName,
                ];
                break;
            case 'dri':
                $liste[] = $pages['SEARCH'];
                $liste[] = $pages['DRI'];
                break;
            case 'std_dri_j1':
                $liste[] = $pages['DASHBOARD'];
                $liste[] = $pages['SEARCH'];
                $liste[] = $pages['PLANNING'];
                $liste[] = $pages['DRI'];
                $liste[] = $pages['J1'];
                break;
            case 'j_1':
                $liste[] = $pages['SEARCH'];
                $liste[] = $pages['J1'];
                break;
            case 'qualite':
                $liste[] = $pages['SEARCH'];
                $liste[] = $pages['PLANNING'];
                $liste[] = $pages['QUALITY'];
                $liste[] = $pages['PRODUCTS'];
                $liste[] = $pages['FORMULES'];
                break;
            case 'admin':
                if ('chat' === $this->get('session')->get('dashboard')) {
                    $liste[] = $pages['DASHBOARD'];
                    $liste[] = $pages['PLANNING_TCHAT'];
                    $liste[] = $pages['SEARCH'];
                    $liste[] = $pages['SMS'];
                    $liste[] = $pages['ADMIN_TCHAT'];
                    $liste[] = $pages['CONFIG_TCHAT'];
                } else {
                    $liste = $pages;
                    unset($liste['CUSTOMERS']);
                    unset($liste['SEARCH_PHONISTE']);
                    unset($liste['SEARCH_VALIDATION']);
                    unset($liste['VALIDATION']);
                    unset($liste['PLANNING_TCHAT']);
                    unset($liste['CONFIG_TCHAT']);
                    unset($liste['ADMIN_AFFILIATE']);
                    unset($liste['ADMIN_TCHAT']);
                }
                break;
            case 'standard':
                $liste = $pages;
                unset($liste['SEARCH_VALIDATION']);
                unset($liste['SEARCH_PHONISTE']);
                unset($liste['CUSTOMERS']);
                unset($liste['USERS']);
                unset($liste['SMS']);
                unset($liste['LEADS']);
                unset($liste['VALIDATION']);
                unset($liste['PLANNING_TCHAT']);
                unset($liste['CONFIG_TCHAT']);
                unset($liste['ADMIN_TCHAT']);
                unset($liste['ADMIN_AFFILIATE']);
                break;
            case 'admin_phone':
                $liste = $pages;
                unset($liste['SEARCH_VALIDATION']);
                unset($liste['SEARCH_PHONISTE']);
                unset($liste['CUSTOMERS']);
                unset($liste['USERS']);
                unset($liste['SMS']);
                unset($liste['VALIDATION']);
                unset($liste['PLANNING_TCHAT']);
                unset($liste['CONFIG_TCHAT']);
                unset($liste['ADMIN_TCHAT']);
                unset($liste['ADMIN_AFFILIATE']);
                break;
            case 'manager_phone':
            case 'unpaid_service':
                $liste = $pages;
                unset($liste['SEARCH_VALIDATION']);
                unset($liste['SEARCH_PHONISTE']);
                unset($liste['CUSTOMERS']);
                unset($liste['USERS']);
                unset($liste['SMS']);
                unset($liste['VALIDATION']);
                unset($liste['PLANNING_TCHAT']);
                unset($liste['CONFIG_TCHAT']);
                unset($liste['ADMIN_TCHAT']);
                unset($liste['LEADS']);
                unset($liste['ADMIN_AFFILIATE']);
                break;
            case 'admin_chat':
                $liste[] = $pages['DASHBOARD'];
                $liste[] = $pages['PLANNING_TCHAT'];
                $liste[] = $pages['SEARCH'];
                $liste[] = $pages['CONFIG_TCHAT'];
                $liste[] = $pages['ADMIN_TCHAT'];
                break;
            case 'manager_chat':
                $liste[] = $pages['DASHBOARD'];
                $liste[] = $pages['PLANNING_TCHAT'];
                $liste[] = $pages['SEARCH'];
                $liste[] = $pages['ADMIN_TCHAT'];
                break;
            case 'manager_standar':
                $liste[] = $pages['DASHBOARD'];
                $liste[] = $pages['SEARCH'];
                $liste[] = $pages['PLANNING'];
                $liste[] = ['route' => 'kgc_admin_ca',
                    'intitule' => 'Pointage CA', 'icone' => 'bar-chart',
                    'active' => 'kgc_admin_ca' === $routeName,
                ];
                break;
            case 'manager_phonist':
                $liste[] = $pages['DASHBOARD'];
                $liste[] = $pages['SEARCH_PHONISTE'];
                $liste[] = $pages['PLANNING'];
                $liste[] = ['route' => 'kgc_admin_phoning',
                    'intitule' => 'Stats phonistes', 'icone' => 'bar-chart',
                    'active' => 'kgc_admin_phoning' === $routeName,
                    'subs' => [
                        [
                            'route' => 'kgc_admin_specific',
                            'intitule' => 'Stats spécifiques',
                            'active' => 'kgc_admin_specific' === $routeName,
                        ],
                        [
                            'route' => 'kgc_admin_phoning',
                            'intitule' => 'Stats phonistes',
                            'active' => 'kgc_admin_phoning' === $routeName,
                        ],
                    ]
                ];
                $liste[] = ['route' => 'kgc_config_mail',
                    'intitule' => 'Réglages', 'icone' => 'cogs',
                    'active' => in_array($routeName, $configRoutes),
                    'subs' => [
                        [
                            'route' => 'kgc_config_salary',
                            'intitule' => 'Bonus/Primes',
                            'active' => 'kgc_config_salary' === $routeName,
                        ],
                    ]
                ];
                break;
            default:
                throw new \Exception(sprintf('Unknown role "%s" to build menu', $roleKey));
        }

        return $this->render('KGCDashboardBundle:Dashboard:menu.html.twig', [
            'liste_liens' => $liste,
        ]);
    }

    /**
     * Génère le rendu des messages flash en session.
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     */
    public function renderFlashBagAction()
    {
        $session = $this->get('session');
        $flashbag = array();
        $modal = null;
        foreach ($session->getFlashBag()->all() as $type => $messages) {
            if ($type === 'modal') {
                $modal = $messages; // = url à charger
            } else {
                $type = explode('#', $type);
                $class = $type[0];
                $img = $type[1];
                foreach ($messages as $content) {
                    $content = explode('--', $content);
                    $titre = $content[0];
                    $msg = $content[1];
                    $flashbag[] = array(
                        'class' => $class,
                        'img' => $img,
                        'titre' => $titre,
                        'msg' => $msg,
                    );
                }
            }
        }

        return $this->render('KGCDashboardBundle:Dashboard:flashbags.html.twig', array(
            'flashbag' => $flashbag,
            'modal' => $modal,
        ));
    }

    /**
     * Page de recherche
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     */
    public function searchAction()
    {
        $tchat = false;
        $role = $this->getUser()->getMainprofil()->getRoleKey();
        if ('chat' === $this->get('session')->get('dashboard') || in_array($role, ['admin_chat', 'manager_chat'])) {
            $tchat = true;
        }
        return $this->render('KGCDashboardBundle:Elastic:index.html.twig', ['tchat' => $tchat]);
    }

    /**
     * Page de recherche par id consultation (phoniste)
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     */
    public function searchPhonisteAction()
    {
        return $this->render('KGCDashboardBundle:Elastic:phoniste.search.html.twig');
    }

    /**
     * Page de recherche client
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     */
    public function searchClientAction()
    {
        $tchat = false;
        $role = $this->getUser()->getMainprofil()->getRoleKey();
        if ('chat' === $this->get('session')->get('dashboard') || in_array($role, ['admin_chat', 'manager_chat'])) {
            $tchat = true;
        }
        return $this->render('KGCDashboardBundle:Elastic:client.search.html.twig', ['tchat' => $tchat]);
    }

    /**
     * Page de recherche prospect
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     */
    public function searchProspectAction()
    {
        return $this->render('KGCDashboardBundle:Elastic:prospect.search.html.twig');
    }

    /**
     * Page de recherche rdv
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     */
    public function searchRdvAction()
    {
        return $this->render('KGCDashboardBundle:Elastic:rdv.search.html.twig');
    }
}
