<?php

// src/KGC/ChatBundle/Controller/ApiController.php


namespace KGC\ChatBundle\Controller;

use KGC\CommonBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use KGC\ChatBundle\Entity\ChatRoom;
use KGC\ChatBundle\Entity\ChatType;
use KGC\ChatBundle\Entity\ChatFormulaRate;
use KGC\ChatBundle\Entity\ChatSubscription;
use KGC\ChatBundle\Service\UserManager;

/**
 * Class ApiController.
 */
class ApiController extends CommonController
{
    /**
     * Return the useful information to get repository.
     *
     * @return mixed
     */
    protected function getEntityRepository()
    {
        throw new Exception('Not implemented', 1);
    }

    /**
     * Return the useful information about client.
     *
     * @return JSON
     */
    public function infoAction()
    {
        $user = $this->getUser();
        $json = array(
            'status' => 'OK',
            'message' => 'Informations retrieved',
            'client' => array(
                'id' => $user->getId(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'dateNaissance' => $user->getDateNaissance()->format('Y-m-d'),
                'email' => $user->getEmail(),
                'origin' => $user->getOrigin(),
                'username' => $user->getUsername(),
            ),
        );

        return new JsonResponse($json);
    }

    /**
     * Basically this method is only here to check if user has a valid token for further purpose.
     *
     * @return JSON
     */
    public function checkUserAuthenticationAction()
    {
        $UserManager = $this->container->get('kgc.chat.user.manager');
        $user = $this->getUser();
        $json = array(
            'status' => 'OK',
            'message' => 'This is a valid token',
            'user' => $UserManager->convertUserToJsonArray($user),
        );

        return new JsonResponse($json);
    }

    /**
     * Set a psychic available or not.
     *
     * @param int $is_available Availability : 0 for unavailable, 1 for available
     *
     * @return JSON
     */
    public function setAvailabilityAction(Request $request, $is_available)
    {
        $UserManager = $this->container->get('kgc.chat.user.manager');

        $json = array(
            'status' => 'KO',
            'message' => 'Nothing happend',
        );

        $availability_status = $is_available === '1' ? true : ($is_available === '0' ? false : null);

        if ($availability_status === null) {
            $json['status'] = 'Unknown availability status';

            return new JsonResponse($json);
        }

        $force = $request->query->get('force') === '1' ? true : false;

        $json = $UserManager->setAvailability($this->getUser(), $availability_status, $force);

        return new JsonResponse($json);
    }

    /**
     * Create a room.
     *
     * @param Request $request
     *
     * @return JSON
     */
    public function createRoomAction(Request $request)
    {
        $RoomManager = $this->container->get('kgc.chat.room.manager');

        $json = $RoomManager->createRoom();

        $json['status'] = 'OK';
        $json['message'] = 'Room created';

        if ($room = $json['room']) {
            $json['room'] = $RoomManager->convertRoomToJsonArray($room);
        }

        return new JsonResponse($json);
    }

    /**
     * An user enter in a room.
     *
     * @param Request $request
     * @param int     $id      The room id
     *
     * @return JSON
     */
    public function enterRoomAction(Request $request, $id)
    {
        $RoomManager = $this->container->get('kgc.chat.room.manager');

        $json = $RoomManager->enterRoom($id, $this->getUser());

        return new JsonResponse($json);
    }

    /**
     * An user leave a room.
     *
     * @param Request $request
     * @param int     $id      The room id
     *
     * @return JSON
     */
    public function leaveRoomAction(Request $request, $id)
    {
        $RoomManager = $this->container->get('kgc.chat.room.manager');

        $json = $RoomManager->leaveRoom($id, $this->getUser(), $request->request->get('reason'));

        return new JsonResponse($json);
    }

    /**
     * Add a message in a room.
     *
     * @param Request       $request
     * @param int           $id      The room id
     * @param [POST] string $content The message's content
     *
     * @return JSON
     */
    public function addMessageAction(Request $request, $id)
    {
        $RoomManager = $this->container->get('kgc.chat.room.manager');
        $MessageManager = $this->container->get('kgc.chat.message.manager');

        $json = $RoomManager->addMessage($id, $this->getUser(), $request->request->get('content'));

        if ($message = $json['chat_message']) {
            $json['chat_message'] = $MessageManager->convertMessageToJsonArray($message);
        }

        if ($room = $json['room']) {
            $json['room'] = $RoomManager->convertRoomToJsonArray($room);
        }

        return new JsonResponse($json);
    }

    /**
     * Get the current rooms, with users in it and messages already sent by a specific user.
     *
     * @return JSON
     */
    public function getRoomsAction()
    {
        $RoomManager = $this->container->get('kgc.chat.room.manager');

        $rooms = $RoomManager->getRooms($user = $this->getUser());

        $json = array(
            'status' => 'OK',
            'message' => 'Rooms retrieved',
            'rooms' => $RoomManager->convertRoomsToJsonArray($rooms),
        );

        return new JsonResponse($json);
    }

    /**
     * Get available virtual psychics.
     *
     * @param string $website_slug If specified, restrict result to a specific website
     *
     * @return JSON
     */
    public function getAvailableVirtualPsychicsAction($website_slug)
    {
        $WebsiteManager = $this->container->get('kgc.chat.website.manager');
        $SharedWebsiteManager = $this->container->get('kgc.shared.website.manager');

        $id = null;
        if ($website = $SharedWebsiteManager->getWebsiteBySlug($website_slug)) {
            $id = $website->getId();
        }

        $json = $WebsiteManager->getWebsitesWithAvailableVirtualPsychics($id);

        // Originaly, $json['websites'] is an associative array.
        // When it wiil be converted to JSON, it will become an object with properties instad of an array of objects
        // Then we won't be enable to process tests with JsonSchema
        // So for the needs of test and more reliable logic, we remove this indexing thing here

        foreach ($json['websites'] as &$website) {
            $website['psychics'] = array_values($website['psychics']);
        }
        $json['websites'] = array_values($json['websites']);

        return new JsonResponse($json);
    }

    /**
     * Alias of previous method to get virtual psychics for one website only.
     */
    public function getAvailableVirtualPsychicsByWebsiteAction($website_slug)
    {
        $WebsiteManager = $this->container->get('kgc.chat.website.manager');
        $SharedWebsiteManager = $this->container->get('kgc.shared.website.manager');

        $id = null;
        if ($website = $SharedWebsiteManager->getWebsiteBySlug($website_slug)) {
            $id = $website->getId();
        }

        $json = $WebsiteManager->getWebsitesWithAvailableVirtualPsychics($id);

        // Replace websites by website
        $websites = $json['websites'];
        $json['website'] = array();
        unset($json['websites']);

        foreach ($websites as $website) {
            if ($website['slug'] == $website_slug) {

                // Originaly, $website is an associative array.
                // When it will be converted to JSON, it will become an object with properties instad of an array of objects
                // Then we won't be enable to process tests with JsonSchema
                // So for the needs of test and more reliable logic, we remove this indexing thing here

                $website['psychics'] = array_values($website['psychics']);
                $json['website'] = $website;
                break;
            }
        }

        return new JsonResponse($json);
    }

    /**
     * Ask for room creation with a specific virtual psychic.
     *
     * @param int $virtual_psychic_id
     *
     * @return JSON
     */
    public function askRoomAction(Request $request, $virtual_psychic_id)
    {
        $RoomManager = $this->container->get('kgc.chat.room.manager');
        $UserManager = $this->container->get('kgc.chat.user.manager');

        $utmSource = $request->query->get('utm_source');

        $json = $RoomManager->askRoom($this->getUser(), $virtual_psychic_id, $utmSource);

        if ($room = $json['room']) {
            $json['room'] = $RoomManager->convertRoomToJsonArray($room);
        }

        if ($psychic = $json['psychic']) {
            $json['psychic'] = $UserManager->convertUserToJsonArray($psychic);
        }

        return new JsonResponse($json);
    }

    /**
     * A psychic answer to client's asking conversation.
     *
     * @param int $id       Room's id
     * @param int $decision 1 if psychic accept, 0 if refuse
     *
     * @return JSON
     */
    public function answerRoomAction($id, $decision)
    {
        $RoomManager = $this->container->get('kgc.chat.room.manager');
        $UserManager = $this->container->get('kgc.chat.user.manager');

        $is_accepting = (int) $decision === 1;

        $json = $RoomManager->answerRoom($id, $this->getUser(), $is_accepting);

        if ($room = $json['room']) {
            $json['room'] = $RoomManager->convertRoomToJsonArray($room);
        }

        if ($user = $json['new_psychic']) {
            $json['new_psychic'] = $UserManager->convertUserToJsonArray($user);
        }

        return new JsonResponse($json);
    }

    /**
     * Get all formulas as JSON for a specific website.
     *
     * @param string $website_slug The website's slug
     *
     * @return JSON
     */
    public function getFormulasByWebsiteAction($website_slug, $client_restricted = null)
    {
        $WebsiteManager = $this->container->get('kgc.chat.website.manager');
        $UserManager = $this->container->get('kgc.chat.user.manager');

        $json = array(
            'status' => 'KO',
            'message' => 'Nothing happend',
        );

        $client = null;

        // If user need to be connected, it's to restrict formulas to his history (for recredit level)
        if ($client_restricted !== null) {
            $client = $this->getUser();
            if (!$UserManager->isClient($client)) {
                $json['message'] = 'Unable to get client for restricted formulas';

                return new JsonResponse($json);
            }
        }

        $json = $WebsiteManager->getFormulas($website_slug, false, $client);
        if ($formulas = $json['formulas']) {
            $json['formulas'] = $WebsiteManager->convertFormulasToJsonArray($formulas);
        }

        if (isset($json['cards'])) {
            $json['cards'] = $WebsiteManager->convertCbToJsonArray($json['cards']);
        }

        return new JsonResponse($json);
    }

    /**
     * Get discovery formula as JSON for a specific website.
     *
     * @param string $website_slug The website's slug
     *
     * @return JSON
     */
    public function getDiscoveryFormulaRateByWebsiteAction($website_slug)
    {
        $WebsiteManager = $this->container->get('kgc.chat.website.manager');
        $UserManager = $this->container->get('kgc.chat.user.manager');

        $json = $WebsiteManager->getUniqueFormulaRate($website_slug, ChatFormulaRate::TYPE_DISCOVERY);

        if ($formulaRate = $json['formulaRate']) {
            $json['formulaRate'] = $formulaRate->toJsonArray(true);
        }

        return new JsonResponse($json);
    }

    /**
     * Get free offer formula as JSON for a specific website.
     *
     * @param string $website_slug The website's slug
     *
     * @return JSON
     */
    public function getFreeOfferFormulaRateByWebsiteAction(Request $request, $website_slug)
    {
        $WebsiteManager = $this->container->get('kgc.chat.website.manager');
        $UserManager = $this->container->get('kgc.chat.user.manager');

        $json = $WebsiteManager->getUniqueFormulaRate($website_slug, ChatFormulaRate::TYPE_FREE_OFFER, $request->get('flexible', false));

        if ($formulaRate = $json['formulaRate']) {
            $json['formulaRate'] = $formulaRate->toJsonArray(true);
        }

        return new JsonResponse($json);
    }

    /**
     * Buy a formula rate for a client.
     *
     * @param string $website_slug The website's slug
     * @param int    $id           Formula rate id choosen
     *
     * @return JSON
     */
    public function buyFormulaRateAction(Request $request, $website_slug, $id)
    {
        $PaymentManager = $this->container->get('kgc.chat.payment.manager');

        $json = $PaymentManager->buyFormulaRate($this->getUser(), $website_slug, $id, $request->request->all());

        if ($formula_rate = $json['formula_rate']) {
            $json['formula_rate'] = $formula_rate->toJsonArray();
        }

        return new JsonResponse($json);
    }

    public function usePromotionCodeAction(Request $request)
    {
        $paymentManager = $this->container->get('kgc.chat.payment.manager');

        $json = $paymentManager->usePromotionCode($this->getUser(), $request->request->get('promotionCode'));

        return new JsonResponse($json);
    }

    public function deleteCreditCardAction(Request $request, $id)
    {
        $userManager = $this->container->get('kgc.chat.user.manager');
        $paymentManager = $this->container->get('kgc.chat.payment.manager');

        $client = $this->getUser();
        if ($userManager->isClient($client)) {
            $json = $paymentManager->deleteCreditCard($client, $id);
        } else {
            $json = ['status' => 'KO', 'message' => 'Unable to get client'];
        }

        return new JsonResponse($json);
    }

    /**
     * Get configurations things for node.
     *
     * @return JSON like array
     */
    public function getConfigurationAction()
    {
        $json = array(
            'status' => 'OK',
            'message' => 'Configuration retrieved',
            'configuration' => array(
                'room_status' => array(
                    'not_started' => ChatRoom::STATUS_NOT_STARTED,
                    'on_going' => ChatRoom::STATUS_ON_GOING,
                    'closed' => ChatRoom::STATUS_CLOSED,
                    'refused' => ChatRoom::STATUS_REFUSED,
                ),
                'chat_types' => array(
                    'minute' => ChatType::TYPE_MINUTE,
                    'question' => ChatType::TYPE_QUESTION,
                ),
            ),
        );

        return new JsonResponse($json);
    }

    /**
     * Try to reopen the room.
     *
     * @param int $id The room id
     *
     * @return JSON
     */
    public function reopenRoomAction($id)
    {
        $RoomManager = $this->container->get('kgc.chat.room.manager');

        $json = $RoomManager->reopenRoom($id);

        if ($room = $json['room']) {
            $json['room'] = $RoomManager->convertRoomToJsonArray($room);
        }

        return new JsonResponse($json);
    }

    /**
     * Try to resume the room.
     *
     * @param int $id The room id
     *
     * @return JSON
     */
    public function resumeRoomAction($id)
    {
        $RoomManager = $this->container->get('kgc.chat.room.manager');

        $json = $RoomManager->resumeRoom($id);

        if ($room = $json['room']) {
            $json['room'] = $RoomManager->convertRoomToJsonArray($room);
        }

        return new JsonResponse($json);
    }

    /**
     * Decrement the room.
     * If it's minute room, process from the last consumption
     * If it's question room, decrement 1.
     *
     * @param int $id The room id
     *
     * @return JSON
     */
    public function decrementRoomAction(Request $request, $id)
    {
        $RoomManager = $this->container->get('kgc.chat.room.manager');

        $force_recredit = $request->query->get('force_recredit');
        if ($force_recredit === 1 || $force_recredit === '1' || $force_recredit === true) {
            $force_recredit = true;
        } else {
            $force_recredit = false;
        }

        $json = $RoomManager->decrementRoom($id, $force_recredit);

        if ($room = $json['room']) {
            $json['room'] = $RoomManager->convertRoomToJsonArray($room);
        }

        return new JsonResponse($json);
    }

    /**
     * Get subscriptions for a client if it exists.
     *
     * @param string $website_slug
     *
     * @return JSON like array
     */
    public function getSubscriptionsAction($website_slug)
    {
        $UserManager = $this->container->get('kgc.chat.user.manager');
        $SubscriptionManager = $this->container->get('kgc.chat.subscription.manager');

        $json = array(
            'status' => 'KO',
            'message' => 'Nothing happend',
            'subscription' => null,
        );

        $client = $this->getUser();
        if (!$UserManager->isClient($client)) {
            $json['message'] = 'Unable to get client';

            return new JsonResponse($json);
        }

        $json = $SubscriptionManager->getSubscriptions($website_slug, $client);
        if ($subscriptions = $json['subscriptions']) {
            $json['subscriptions'] = $SubscriptionManager->convertChatSubscriptionsToJsonArray($subscriptions);
        }

        return new JsonResponse($json);
    }

    /**
     * Cancel subscription for a client if it exists.
     *
     * @param string $website_slug
     * @param int    $id           Chat subscription id
     *
     * @return JSON like array
     */
    public function cancelSubscriptionAction(Request $request, $website_slug, $id)
    {
        $UserManager = $this->container->get('kgc.chat.user.manager');
        $SubscriptionManager = $this->container->get('kgc.chat.subscription.manager');

        $json = array(
            'status' => 'KO',
            'message' => 'Nothing happend',
            'subscription' => null,
        );

        $client = $this->getUser();
        if (!$UserManager->isClient($client)) {
            $json['message'] = 'Unable to get client';

            return new JsonResponse($json);
        }

        $json = $SubscriptionManager->unsubscribe($website_slug, $client, $id, ChatSubscription::SOURCE_CLIENT);

        return new JsonResponse($json);
    }

    /**
     * Get chat history for a specific client.
     *
     * @param string $website_slug
     *
     * @return JSON like array
     */
    public function getChatHistoryAction($website_slug)
    {
        $UserManager = $this->container->get('kgc.chat.user.manager');
        $RoomManager = $this->container->get('kgc.chat.room.manager');

        $json = array(
            'status' => 'KO',
            'message' => 'Nothing happend',
            'conversations' => null,
        );

        $client = $this->getUser();
        if (!$UserManager->isClient($client)) {
            $json['message'] = 'Unable to get client';

            return new JsonResponse($json);
        }

        $json = $UserManager->getChatHistory($website_slug, $client);
        if ($conversations = $json['conversations']) {
            $json['conversations'] = $RoomManager->convertConversationsToJsonArray($conversations);
        }

        return new JsonResponse($json);
    }

    /**
     * Get chat consumption for a specific client (remaining credit).
     *
     * @param string $website_slug
     *
     * @return JSON like array
     */
    public function getChatRemainingCreditAction($website_slug)
    {
        $UserManager = $this->container->get('kgc.chat.user.manager');
        $PaymentManager = $this->container->get('kgc.chat.payment.manager');

        $json = array(
            'status' => 'KO',
            'message' => 'Nothing happend',
            'credits_by_chat_type' => array(),
        );

        $client = $this->getUser();
        if (!$UserManager->isClient($client)) {
            $json['message'] = 'Unable to get client';

            return new JsonResponse($json);
        }

        $json = $PaymentManager->getChatRemainingCredit($website_slug, $client);
        if ($credits_by_chat_type = $json['credits_by_chat_type']) {
            $json['credits_by_chat_type'] = array();
            foreach ($credits_by_chat_type as $credit_by_chat_type) {
                $chat_type_json = $credit_by_chat_type['chat_type']->toJsonArray();

                $json['credits_by_chat_type'][] = array(
                    'chat_type' => $chat_type_json,
                    'remaining_credit' => $credit_by_chat_type['remaining_credit'],
                );
            }
        }

        return new JsonResponse($json);
    }
}
