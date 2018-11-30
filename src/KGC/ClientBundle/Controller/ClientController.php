<?php

// src/KGC/ClientBundle/Controller/ClientController.php


namespace KGC\ClientBundle\Controller;

use KGC\ChatBundle\Entity\ChatSubscription;
use KGC\ClientBundle\Form\ClientCardEditType;
use KGC\ClientBundle\Form\ClientCardType;
use KGC\ClientBundle\Handler\ClientCardFormHandler;
use KGC\ClientBundle\Handler\ClientEditHandler;
use KGC\ClientBundle\Repository\HistoriqueRepository;
use KGC\ClientBundle\Service\HistoriqueManager;
use KGC\ClientBundle\Service\HistoriquePaginator;
use KGC\CommonBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\Request;
use KGC\Bundle\SharedBundle\Entity\Client;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * Class ClientController.
 */
class ClientController extends CommonController
{
    /**
     * Return the useful information to get repository.
     *
     * @return mixed
     */
    protected function getEntityRepository()
    {
        return 'KGCSharedBundle:Client';
    }

    /**
     * @return HistoriqueRepository
     */
    protected function getHistoriqueRepository()
    {
        return $this->getDoctrine()->getRepository('KGCClientBundle:Historique');
    }

    /**
     * @return HistoriqueManager
     */
    protected function getHistoriqueManager()
    {
        return $this->get('kgc.client.historique.manager');
    }

    /**
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function historiqueAction($id, $section)
    {
        $client = $this->findById($id);

        return $this->render('KGCClientBundle:historique:main.html.twig', [
            'client' => $client,
            'section' => $section,
        ]);
    }

    /**
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function historiqueContentAction($id, $page, $section)
    {
        $client = $this->findById($id);

        $items = $this
            ->getHistoriqueRepository()
            ->getHistoryPaginatorConfig(
                $id,
                $this->getHistoriqueManager()->getHistoryFieldsBySection($section)
            );

        $paginator = new HistoriquePaginator($items, $page);
        $indexes = $paginator->getIds();

        $historique = $this
            ->getHistoriqueRepository()
            ->getHistoryPaginatorItems(
                $id,
                $indexes['start'],
                $indexes['end'],
                $this->getHistoriqueManager()->getHistoryFieldsBySection($section)
            );

        $formattedHistorique = $this
            ->get('kgc.client.historique.manager')
            ->formatHistoriqueByRdv($historique);

        return $this->render('KGCClientBundle:historique:content.html.twig', [
            'client' => $client,
            'historique' => $formattedHistorique,
            'paginator' => $paginator,
            'section' => $section,
        ]);
    }

    public function ajaxAutoFillDrawInterpretationAction()
    {
        $interpretation = 'Sélectionner une carte pour voir lʼinterprétation...';
        $identifier = $this->get('request')->query->get('identifier');
        if ($identifier !== null) {
            $opt_rep = $this->getDoctrine()->getManager()->getRepository('KGCClientBundle:Option');
            $card = $opt_rep->findOneById($identifier);
            if ($card) {
                $interpretation = $card->getDescription();
            }
        }

        return $this->jsonResponse(['fillWith' => $interpretation]);
    }

    public function ajaxUpdateChatInfoAction(Request $request)
    {
        $client = $this->getRepository()->findOneById(
            $request->request->get('client_id')
        );

        if (null !== $client) {
            $client
                ->setChatInfoSubject($request->request->get('chat_subject'))
                ->setChatInfoPartner($request->request->get('chat_partner'))
                ->setChatInfoAdvice($request->request->get('chat_advice'));

            $em = $this->getDoctrine()->getManager();
            $em->persist($client);
            $em->flush();
        }

        return $this->jsonResponse(['status' => 'ok']);
    }

    public function ajaxUnsubscribeAction(Request $request, $idClient, $referenceWebsite, $idSubscription)
    {
        $json = [];

        $UserManager = $this->container->get('kgc.chat.user.manager');
        $SubscriptionManager = $this->container->get('kgc.chat.subscription.manager');
        $WebsiteSlug = $this->container->get('kgc.shared.website.manager')->getSlugFromReference($referenceWebsite);
        $client = $this->findById($idClient);

        if ($UserManager->isClient($client)) {
            $json = $SubscriptionManager->unsubscribe($WebsiteSlug, $client, $idSubscription, ChatSubscription::SOURCE_ADMIN, $this->getUser());
        } else {
            $json = [
                'status' => 'ko',
                'message' => 'Unable to get client',
            ];
        }

        return $this->jsonResponse($json);
    }

    public function ajaxCancelSubscriptionAction(Request $request, $idClient, $referenceWebsite, $idSubscription)
    {
        $json = [];

        $UserManager = $this->container->get('kgc.chat.user.manager');
        $SubscriptionManager = $this->container->get('kgc.chat.subscription.manager');
        $WebsiteSlug = $this->container->get('kgc.shared.website.manager')->getSlugFromReference($referenceWebsite);
        $client = $this->findById($idClient);

        if ($UserManager->isClient($client)) {
            $json = $SubscriptionManager->disableSubscription($WebsiteSlug, $client, $idSubscription, ChatSubscription::SOURCE_ADMIN, $this->getUser());
        } else {
            $json = [
                'status' => 'ko',
                'message' => 'Unable to get client',
            ];
        }

        return $this->jsonResponse($json);
    }

    public function showAction(Request $request, $id)
    {
        $client = $this->findById($id);
        $em = $this->getDoctrine()->getManager();
        $prospects = $em->getRepository('KGCSharedBundle:Client')->getClientProspects($client);
        foreach ($prospects as $prospect){
            if(is_null($prospect->getClient())){
                $prospect->setClient($client);
                $em->persist($prospect);
                $em->flush();
            }
        }

        return $this->render('KGCDashboardBundle:Client:show.html.twig', [
            'client' => $client,
        ]);
    }

    public function showConsultationsAction(Request $request, $id)
    {
        $consultations = $this->getRepository('KGCRdvBundle:RDV')->findAllByUser($id);

        return $this->render('KGCClientBundle:client:show-consultations.html.twig', [
            'consultations' => $consultations,
        ]);
    }

    /**
     * Méthode ficheAction
     * Consultation de la fiche client.
     * @param int $id
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_QUALITE, ROLE_VOYANT, ROLE_QUALITE, ROLE_MANAGER_PHONE, ROLE_UNPAID_SERVICE, ROLE_VALIDATION, ROLE_MANAGER_STANDAR, ROLE_MANAGER_PHONIST, ROLE_DRI, ROLE_J_1, ROLE_PHONISTE")
     */
    public function ficheAction(Request $request, $id)
    {
        $client = $this->findById($id);
        return $this->render('KGCClientBundle:client:client_fiche.html.twig', array(
            'client' => $client,
        ));
    }

    /**
     * Méthode VoirficheAction
     * Consultation de la fiche client.
     * @param int $id
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_QUALITE, ROLE_VOYANT, ROLE_QUALITE, ROLE_MANAGER_PHONE, ROLE_UNPAID_SERVICE, ROLE_VALIDATION, ROLE_MANAGER_STANDAR, ROLE_MANAGER_PHONIST, ROLE_DRI, ROLE_J_1, ROLE_PHONISTE")
     */
    public function VoirficheAction(Request $request, $id)
    {
        $forceEmpty = $request->query->get('forceEmpty');
        $forceEmpty = $forceEmpty ?: false;

        $currUser = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $client = $this->findById($id);
        if ($client) {
            $request = $this->get('request');
            $request->getSession()->set('original_client', clone $client);
            $form_edit = $this->createForm(new ClientCardEditType($client));
            $form_edithandler = new ClientEditHandler($form_edit, $request);
            $param_edit = $form_edithandler->process();
            $prospects = $em->getRepository('KGCSharedBundle:Client')->getClientProspects($client);
            foreach ($prospects as $prospect){
                if(is_null($prospect->getClient())){
                    $prospect->setClient($client);
                    $em->persist($prospect);
                    $em->flush();
                }
            }

            $formType = new ClientCardType($this->getUser(), $param_edit, $em, true, $this->getUser()->getIsDecryptAvailable());
            $form = $this->createForm($formType, $client);
            $formhandler = $this->get('kgc.client.cardformhandler');
            $result = $formhandler->process($form, $request);
            if($result){
                $this->addFlash('light#user-light', $client->getFullName() . '--Client modifié.');
            }
            $request->getSession()->remove('original_client');

        }

        return $this->render('KGCClientBundle:client:fiche.html.twig', array(
            'client' => $client,
            'form' => isset($form) ? $form->createView() : null,
            'form_edit' => (isset($form_edit) and !$currUser->isQualite()) ? $form_edit->createView() : null,
            'close' => isset($close) ? $close : null,
            'forceEmpty' => $forceEmpty,
        ));
    }

    /**
     * Méthode makeCallAction
     * Réalise un appel.
     * @param int $id
     * @param string $type
     * @param string $phone
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_QUALITE, ROLE_VOYANT, ROLE_QUALITE, ROLE_MANAGER_PHONE, ROLE_UNPAID_SERVICE, ROLE_VALIDATION, ROLE_MANAGER_STANDAR, ROLE_MANAGER_PHONIST, ROLE_DRI, ROLE_J_1, ROLE_PHONISTE")
     */
    public function makeCallAction(Request $request, $id, $type, $phone)
    {
        $user = $this->getUser();
        if($type == 'rdv') {
            $rdv = $this->getRepository('KGCRdvBundle:RDV')->find($id);
            $telephone = ($phone == "Téléphone") ? $rdv->getNumtel1() : $rdv->getNumtel2();
            $array = array('rdv' => $rdv);
        } else {
            $client = $this->getRepository('KGCSharedBundle:Client')->find($id);
            $telephone = ($phone == "Téléphone") ? $client->getNumtel1() : $client->getNumtel2();
            $array = array();
        }
        if($user->isAllowedToMakeCall() && !is_null($user->getPoste())) {
            $callService = $this->get('kgc.client.sms.service');
            $call = false;
            if($user->getPoste()) {
                $call = $callService->makeCall($user->getPoste(), $telephone);
            }
            if($call) {
                return $this->render('KGCClientBundle:client:success-call.html.twig', $array);
            } else {
                return $this->render('KGCClientBundle:client:error-call.html.twig', $array);
            }
        } else {
            return $this->render('KGCClientBundle:client:error-call.html.twig', $array);
        }

    }
}
