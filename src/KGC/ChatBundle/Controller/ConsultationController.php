<?php

namespace KGC\ChatBundle\Controller;

use KGC\ChatBundle\Entity\ChatPayment;
use KGC\ChatBundle\Entity\ChatRoom;
use KGC\ChatBundle\Form\ChatPaymentType;
use KGC\ClientBundle\Entity\MailSent;
use KGC\ClientBundle\Entity\SmsSent;
use KGC\ClientBundle\Form\ClientMailType;
use KGC\ClientBundle\Form\ClientSmsType;
use KGC\CommonBundle\Controller\CommonController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * Class ConsultationController.
 */
class ConsultationController extends CommonController
{
    protected function getEntityRepository()
    {
        return '';
    }

    /**
     * @param $status
     *
     * @return Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_CHAT, ROLE_MANAGER_PHONE")
     */
    public function lastByStatusAction($status)
    {
        $rooms = $this->getRepository('KGCChatBundle:ChatRoom')->findAllLastByStatus($status);

        if ($status == ChatRoom::STATUS_CLOSED) {
            $title = 'effectués';
            $headerColor = 'pink';
            $refresh = 'rdv_status_done';
        } else {
            $title = 'refusés';
            $headerColor = 'red';
            $refresh = 'rdv_status_refused';
        }

        return $this->render('KGCChatBundle:Chat:planning.html.twig', [
            'title' => 'Derniers chats '.$title,
            'header_color' => $headerColor,
            'refresh' => $refresh,
            'rooms' => $rooms,
        ]);
    }

    /**
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_CHAT, ROLE_MANAGER_PHONE")
     */
    public function planningWidgetAction()
    {
        $end = new \DateTime();
        $begin = new \DateTime('00:00');

        $rooms = $this->getRepository('KGCChatBundle:ChatRoom')->findAllByInterval($begin, $end);

        return $this->render('KGCChatBundle:Chat:planning.html.twig', [
            'rooms' => $rooms,
        ]);
    }

    /**
     * @param $id
     *
     * @return Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_CHAT, ROLE_VOYANT, ROLE_MANAGER_PHONE")
     */
    public function chatClientShowAction($id)
    {
        $client = $this->getRepository('KGCSharedBundle:Client')->find($id);
        if (null === $client) {
            $this->createNotFoundException();
        }

        $websites = $this->getRepository('KGCChatBundle:ChatRoom')->findAllWebsiteByUserMail($client->getEmail());

        return $this->render('KGCChatBundle:Chat:client_show.html.twig', [
            'websites' => $websites,
            'email' => $client->getEmail(),
        ]);
    }

    /**
     * @param $roomId
     *
     * @return Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_CHAT, ROLE_VOYANT, ROLE_MANAGER_PHONE")
     */
    public function chatClientMessagesShowAction($roomId)
    {
        $room = $this->getRepository('KGCChatBundle:ChatRoom')->findOneById($roomId);

        return $this->render('KGCChatBundle:Chat:client_show_messages.html.twig', [
            'room' => $room,
        ]);
    }

    /**
     * @param $websiteId
     * @param $email
     *
     * @return Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_CHAT, ROLE_VOYANT, ROLE_MANAGER_PHONE")
     */
    public function chatClientWebsiteShowAction($websiteId, $email)
    {
        $client = $this->getRepository('KGCSharedBundle:Client')->findOneByWebsiteAndUserMail($websiteId, $email);
        $website = $this->getRepository('KGCSharedBundle:Website')->findOneById($websiteId);
        $formula = $this->getRepository('KGCChatBundle:ChatFormula')->findOneByWebsite($website);
        $rooms = $this->getRepository('KGCChatBundle:ChatRoom')->findAllByWebsiteAndUserMail($websiteId, $email);
        $cards = $client->getCartebancairesForTchat();
        $chatPayments = $this->getRepository('KGCChatBundle:ChatPayment')->findByClientWithChatFormulaRate($client);
        $subscriptions = $this->getRepository('KGCChatBundle:ChatSubscription')->findWithChatType($client, $website, false);

        $chatPaymentsForms = [];
        foreach ($chatPayments as $chatPayment) {
            $chatPaymentsForms[$chatPayment->getId()] = $this->createForm(new ChatPaymentType($chatPayment), $chatPayment)->createView();
        }

        if ($plannedPayments = $this->get('kgc.chat.subscription.manager')->findPlannedPaymentsByClient($client)) {
            $chatPayments = array_merge($chatPayments, $plannedPayments);

            usort($chatPayments, function ($a, $b) {
                $dateA = method_exists($a, 'getDate') ? $a->getDate() : $a['date'];
                $dateB = method_exists($b, 'getDate') ? $b->getDate() : $b['date'];

                if ($dateA == $dateB) {
                    return 0;
                }

                return $dateA < $dateB ? -1 : 1;
            });
        }

        $pricings = $this->get('kgc.chat.calculator.pricing')->buildPricings($websiteId, $email);

        return $this->render('KGCChatBundle:Chat:client_show_website.html.twig', [
            'chatType' => $formula->getChatType()->getType(),
            'rooms' => $rooms,
            'client' => $client,
            'cards' => $cards,
            'website' => $website,
            'pricings' => $pricings,
            'chatPayments' => $chatPayments,
            'chatPaymentsForms' => $chatPaymentsForms,
            'subscriptions' => $subscriptions,
        ]);
    }

    /**
     * @param $clientId
     * @param $roomId
     * @param $websiteId
     *
     * @return Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_CHAT, ROLE_MANAGER_PHONE")
     */
    public function chatClientSingleShowAction($clientId, $roomId, $websiteId)
    {
        $website = $this->getRepository('KGCSharedBundle:Website')->find($websiteId);
        $client = $this->getRepository('KGCSharedBundle:Client')->find($clientId);
        $room = $this->getRepository('KGCChatBundle:ChatRoom')->find($roomId);
        $em = $this->getDoctrine()->getManager();
        $prospects = $em->getRepository('KGCSharedBundle:Client')->getClientProspects($client);
        foreach ($prospects as $prospect){
            if(is_null($prospect->getClient())){
                $prospect->setClient($client);
                $em->persist($prospect);
                $em->flush();
            }
        }

        $pricings = $this->get('kgc.chat.calculator.pricing')->buildPricings($websiteId, $client->getEmail(), $roomId);

        return $this->render('KGCChatBundle:Chat:client_show_single.html.twig', [
            'website' => $website,
            'client' => $client,
            'room' => $room,
            'pricings' => $pricings,
        ]);
    }

    /**
     * @param Request     $request
     * @param ChatPayment $chatPayment
     *
     * @return JsonResponse
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_CHAT, ROLE_MANAGER_PHONE")
     */
    public function editChatPaymentAction(Request $request, ChatPayment $chatPayment)
    {
        $form = $this->createForm(new ChatPaymentType($chatPayment), $chatPayment);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($form->getData());
            $em->flush();

            return new JsonResponse(['status' => 'ok']);
        }

        return new JsonResponse(['status' => 'error']);
    }

    /**
     * @param $id
     *
     * @return Response
     *
     * @Secure(roles="ROLE_ADMIN, ROLE_MANAGER_CHAT, ROLE_ADMIN_CHAT")
     */
    public function PrepareMailAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $client = $em->getRepository("KGCSharedBundle:Client")->find($id);
        $close = false;

        if ($client) {
            $mails = $em->getRepository('KGCClientBundle:MailSent')->getMailHistoryByClient($client->getId());
            $currUser = $this->getUser();
            if ('chat' === $this->get('session')->get('dashboard')) {
                $tchat = 1;
            } else {
                $tchat = 0;
            }
            $form = $this->createForm(new ClientMailType($tchat), $client);
            $formhandler = $this->get('kgc.client.formhandler');
            $result = $formhandler->process($form, $request);
            $mail = $form->get('mail_sent')->getData();
            $statusMail = $mail && MailSent::STATUS_ERROR === $mail->getStatus() ? false : null;

            if ($result === true && false !== $statusMail) { // form submit valid
                $this->addFlash('light#enveloppe-light', $client->getFullName() . '--Mail envoyé.');
                $close = true;
            } elseif ($result === false || false === $statusMail) { // form submit invalid
                $this->addFlash('error#enveloppe', $client->getFullName() . '--Mail non envoyé.');

                $mails = $em->getRepository('KGCClientBundle:MailSent')->getMailHistoryByClient($client->getId());
                $form->addError(new FormError('Une erreur est survenue, le mail nʼa pas été envoyé.'));
            }
        }

        return $this->render('KGCChatBundle:Chat:prepare_mail.html.twig', array(
            'client' => isset($client) ? $client : null,
            'form' => isset($form) ? $form->createView() : null,
            'mails' => $mails,
            'close' => $close
        ));
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @Secure(roles="ROLE_ADMIN, ROLE_MANAGER_CHAT, ROLE_ADMIN_CHAT")
     */
    public function BuildMailAction(Request $request)
    {
        $id = $request->request->getInt('id');
        $clientId = $request->request->getInt('client');

        $data = [
            'subject' => '',
            'html' => '',
        ];

        if ($id && $clientId) {
            $em = $this->getDoctrine()->getManager();
            $mail = $em
                ->getRepository('KGCClientBundle:Mail')
                ->getOneById($id);

            $baseUrl = $request->getSchemeAndHttpHost();
            $transformer = $this->get('kgc.client.mail.transformer');
            $transformer->transformChat($baseUrl, $mail, $clientId);

            $data = [
                'subject' => $mail->getSubject(),
                'html' => $mail->getHtml(),
            ];
        }

        return $this->jsonResponse($data);
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @Secure(roles="ROLE_ADMIN, ROLE_MANAGER_CHAT, ROLE_ADMIN_CHAT")
     */
    public function BuildSmsAction(Request $request)
    {
        $id = $request->request->getInt('id');
        $clientId = $request->request->getInt('client');

        $data = [
            'text' => '',
        ];

        if ($id && $clientId) {
            $em = $this->getDoctrine()->getManager();
            $sms = $em
                ->getRepository('KGCClientBundle:Sms')
                ->getOneById($id);

            $baseUrl = $request->getSchemeAndHttpHost();
            $transformer = $this->get('kgc.client.sms.transformer');
            $transformer->transformChat($baseUrl, $sms, $clientId);

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
     * @Secure(roles="ROLE_ADMIN, ROLE_MANAGER_CHAT, ROLE_ADMIN_CHAT")
     */
    public function PrepareSmsAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $client = $em->getRepository("KGCSharedBundle:Client")->find($id);
        $close = false;

        if ($client) {
            $smss = $em->getRepository('KGCClientBundle:SmsSent')->getSmsHistoryByClient($client->getId());
            $currUser = $this->getUser();
            $phone = $client->getNumtel1() != "" ? $client->getNumtel1() : $client->getNumte2();
            if ('chat' === $this->get('session')->get('dashboard')) {
                $tchat = 1;
            } else {
                $tchat = 0;
            }
            $form = $this->createForm(new ClientSmsType($phone, $tchat), $client);
            $formhandler = $this->get('kgc.client.sms.formhandler');
            $result = $formhandler->process($form, $request);
            $sms = $form->get('sms_sent')->getData();
            $statusSms = $sms && SmsSent::STATUS_ERROR === $sms->getStatus() ? false : null;

            if ($result === true && false !== $statusSms) { // form submit valid
                $this->addFlash('light#enveloppe-light', $client->getFullName() . '--Sms envoyé.');
                $close = true;
            } elseif ($result === false || false === $statusSms) { // form submit invalid
                $this->addFlash('error#enveloppe', $client->getFullName() . '--Sms non envoyé.');

                $smss = $em->getRepository('KGCClientBundle:SmsSent')->getSmsHistoryByClient($client->getId());
                $form->addError(new FormError('Une erreur est survenue, le sms nʼa pas été envoyé.'));
            }
        }

        return $this->render('KGCChatBundle:Chat:prepare_sms.html.twig', array(
            'client' => isset($client) ? $client : null,
            'form' => isset($form) ? $form->createView() : null,
            'sms' => $smss,
            'close' => $close
        ));
    }
}
