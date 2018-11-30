<?php

namespace KGC\ChatBundle\Service;

use JMS\DiExtraBundle\Annotation as DI;
use Doctrine\ORM\EntityManager;
use KGC\ChatBundle\Entity\ChatRoom;
use KGC\ChatBundle\Entity\ChatParticipant;
use KGC\ChatBundle\Entity\ChatMessage;
use KGC\ChatBundle\Entity\ChatPayment;
use KGC\ChatBundle\Entity\ChatRoomFormulaRate;
use KGC\ChatBundle\Entity\ChatRoomConsumption;
use KGC\ChatBundle\Entity\ChatType;
use KGC\UserBundle\Entity\Voyant;
use KGC\Bundle\SharedBundle\Entity\Client;

/**
 * @DI\Service("kgc.chat.room.manager")
 */
class RoomManager
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * @var MessageManager
     */
    protected $messageManager;

    /**
     * @var PaymentManager
     */
    protected $paymentManager;

    /**
     * @var TokenManager
     */
    protected $tokenManager;

    /**
     * @param EntityManager  $em
     * @param UserManager    $userManager
     * @param MessageManager $messageManager
     *
     * @DI\InjectParams({
     *     "em" = @DI\Inject("doctrine.orm.entity_manager"),
     *     "userManager" = @DI\Inject("kgc.chat.user.manager"),
     *     "messageManager" = @DI\Inject("kgc.chat.message.manager"),
     *     "paymentManager" = @DI\Inject("kgc.chat.payment.manager"),
     *     "tokenManager" = @DI\Inject("kgc.chat.token.manager")
     * })
     */
    public function __construct(EntityManager $em, UserManager $userManager, MessageManager $messageManager, PaymentManager $paymentManager, TokenManager $tokenManager)
    {
        $this->em = $em;
        $this->userManager = $userManager;
        $this->messageManager = $messageManager;
        $this->paymentManager = $paymentManager;
        $this->tokenManager = $tokenManager;
    }

    /**
     * Make a user enter in a room.
     *
     * @param int   $room_id        The room's id we want to join
     * @param mixed $user           The user who wants to join (could be Utilisateur or Client)
     * @param mixed $virtualPsychic If specified, set virtual psychic for this chat participant
     *
     * @return JSON like array
     */
    public function enterRoom($room_id, $user, $virtualPsychic = null)
    {
        $json = array(
            'status' => 'KO',
            'message' => 'Nothing happend',
            'room_start_date' => null,
        );

        $room = $this->em->getRepository('KGCChatBundle:ChatRoom')->find($room_id);
        if ($room === null) {
            $json['message'] = 'Unknown room';

            return $json;
        }

        $user_type = $this->userManager->extractType($user);
        if ($user_type === null) {
            $json['message'] = 'Unknown type';

            return $json;
        }

        if (!($virtualPsychic === null || $virtualPsychic instanceof Voyant)) {
            $json['message'] = 'Unknown virtual psychic';

            return $json;
        }

        // If user was already in the room, return OK but change message
        if (($chatParticipant = $this->em->getRepository('KGCChatBundle:ChatParticipant')->findOneBy(array(
            $user_type => $user,
            'chatRoom' => $room,
            'leaveDate' => null,
            'virtualPsychic' => $virtualPsychic,
        ))) === null) {
            $chatParticipant = new ChatParticipant();
            if ($user_type === UserManager::TYPE_PSYCHIC) {
                $chatParticipant->setPsychic($user);
                $chatParticipant->setVirtualPsychic($virtualPsychic);

                // If room start date is not yet defined, take advantage of the situation to do it now
                if ($room->getStartDate() === null) {
                    $room->setStartDate(new \DateTime());
                }
            } else {
                $chatParticipant->setClient($user);
            }

            $chatParticipant->setChatRoom($room);
            $room->addChatParticipant($chatParticipant);

            $this->em->persist($chatParticipant);
            $this->em->flush();

            $json['message'] = 'User has entered in the room';
        } else {
            $json['message'] = 'User was already in the room';
        }

        if ($startDate = $room->getStartDate()) {
            $json['room_start_date'] = $startDate->getTimestamp();
        }

        $json['status'] = 'OK';

        return $json;
    }

    /**
     * Make a user leave a room.
     *
     * @param int   $room_id The room's id we want to leave
     * @param mixed $user    The user who wants to leave (could be Utilisateur or Client)
     *
     * @return JSON like array
     */
    public function leaveRoom($room_id, $user, $reason = null)
    {
        $ChatParticipantRepository = $this->em->getRepository('KGCChatBundle:ChatParticipant');

        $json = array(
            'status' => 'KO',
            'message' => 'Nothing happend',
            'room_end_date' => null,
        );

        $room = $this->em->getRepository('KGCChatBundle:ChatRoom')->find($room_id);
        if ($room === null) {
            $json['message'] = 'Unknown room';

            return $json;
        }

        $user_type = $this->userManager->extractType($user);
        if ($user_type === null) {
            $json['message'] = 'Unknown type';

            return $json;
        }

        $chatParticipant = $ChatParticipantRepository->findOneBy(array(
            $user_type => $user,
            'chatRoom' => $room,
            'leaveDate' => null,
        ));
        if (!($chatParticipant instanceof ChatParticipant)) {
            $json['message'] = 'Chat participant not found';

            return $json;
        }

        if ($reason !== null && !in_array($reason, ChatParticipant::getLeaveReasonsList())) {
            $reason = null;
        }

        // If it's a client and it's a chat minute room, update time consumptions
        $this->updateTimeConsumed($room);

        $chatParticipant
            ->setLeaveDate(new \DateTime())
            ->setLeaveReason($reason);

        $participants = $ChatParticipantRepository->findBy(array(
            'chatRoom' => $room,
            'leaveDate' => null,
        ));

        // At this point, there is at least one participant (because we found him just before and didn't yet flushed em)
        // But if there is only one participant, it's our, so close the room
        // By security, check the id (but not really needed, I'm just a bit parano)
        if (count($participants) == 1 && $participants[0]->getId() == $chatParticipant->getId()) {
            $this->closeRoom($room);
        } else {
            // If there is only clients remaining, close the room
            // If this is a question room, don't close it if there is psychic, because they may want to decredit client
            $has_psychic = $has_client = false;
            foreach ($participants as $participant) {
                if ($participant_user = $participant->getUser()) {
                    // Don't take current user into account
                    if ($participant_user->getId() === $user->getId()) {
                        continue;
                    }
                    if ($this->userManager->isPsychic($participant_user)) {
                        $has_psychic = true;
                    } elseif ($this->userManager->isClient($participant_user)) {
                        $has_client = true;
                    }
                }
            }

            // Close the room if :
            // - There is no psychic left
            // - There is no client and the room is not started (client canceled the process)
            if (!$has_psychic || (!$has_client && $room->isNotStarted())) {
                $this->closeRoom($room);
            }
        }

        $this->em->flush();
        $this->em->refresh($room);

        if ($endDate = $room->getEndDate()) {
            $json['room_end_date'] = $endDate->getTimestamp();
        }

        $json['message'] = 'User has left the room';
        $json['status'] = 'OK';

        return $json;
    }

    /**
     * Add a message from a user to a room.
     *
     * @param int    $room_id The room's id we want to leave
     * @param mixed  $user    The user who wants to leave (could be Utilisateur or Client)
     * @param string $content The message's content
     *
     * @return JSON like array containing the message
     */
    public function addMessage($room_id, $user, $content)
    {
        $ChatParticipantRepository = $this->em->getRepository('KGCChatBundle:ChatParticipant');
        $ChatPaymentRepository = $this->em->getRepository('KGCChatBundle:ChatPayment');

        $json = array(
            'status' => 'KO',
            'message' => 'Nothing happend',
            'chat_message' => null,
            'room' => null,
        );

        $room = $this->em->getRepository('KGCChatBundle:ChatRoom')->find($room_id);
        if ($room === null) {
            $json['message'] = 'Unknown room';

            return $json;
        }

        if (!is_string($content) || $content === '' || strlen($content) > $room->getChatType()->getMaxChars()) {
            $json['message'] = 'Content length have to respect chat type limits';

            return $json;
        }

        $json['room'] = $room;

        if ($room->getStatus() !== ChatRoom::STATUS_ON_GOING) {
            $json['message'] = 'This room is not in the good status to receive message';

            return $json;
        }

        $user_type = $this->userManager->extractType($user);
        if ($user_type === null) {
            $json['message'] = 'Unknown type';

            return $json;
        }

        $chatParticipant = $ChatParticipantRepository->findOneBy(array(
            $user_type => $user,
            'chatRoom' => $room,
            'leaveDate' => null,
        ));
        if (!($chatParticipant instanceof ChatParticipant)) {
            $json['message'] = 'Chat participant not found';

            return $json;
        }

        if ($room->getChatType()->isMinute()) {
            $update_json = $this->updateTimeConsumed($room);
            if ($update_json['status'] !== 'OK') {
                // There was a problem in update time consumed
                // We should return the last error received
                $json['message'] = $update_json['message'];

                return $json;
            }
        }

        $message = new ChatMessage();

        $message->setContent($this->messageManager->sanitize($content))
                ->setChatParticipant($chatParticipant)
                ->setChatRoom($room);
        $this->em->persist($message);

        $this->em->flush();
        $json['room']->addChatMessage($message);

        $json['chat_message'] = $message;

        $json['message'] = 'Message added';
        $json['status'] = 'OK';

        return $json;
    }

    /**
     * Update for a room the time consumed (only if it's a minute chat type of course).
     *
     * @param ChatRoom $room
     * @param bool     $force_recredit If true, close chatRoomFormulaRate
     *
     * @return JSON like array
     */
    public function updateTimeConsumed(ChatRoom $room, $force_recredit = false)
    {
        $ChatPaymentRepository = $this->em->getRepository('KGCChatBundle:ChatPayment');

        $json = array(
            'status' => 'KO',
            'message' => 'Nothing happend',
            'recredit_need' => false,
        );

        if ($room->getChatType()->getType() !== ChatType::TYPE_MINUTE) {
            $json['message'] = 'Time consumed can be updated only on minute rooms';

            return $json;
        }

        $client = $room->getClient();
        if (!$this->userManager->isClient($client)) {
            $json['message'] = 'Unable to find the client associated to this room to decrease his credit.';

            return $json;
        }

        $chatRoomFormulaRate = $this->em->getRepository('KGCChatBundle:ChatRoomFormulaRate')->getCurrentWithConsumptions($room);
        if (!($chatRoomFormulaRate instanceof ChatRoomFormulaRate)) {
            // Maybe chatRoomFormulaRate have been closed by a previous operation where there were no more credit
            $json['message'] = 'There is no formula rate associated at this moment. Check you have enough credit to perform this operation.';
            $json['recredit_need'] = true;

            return $json;
        }

        // How many units we have to consume ?
        // Get amounts of seconds already consumed
        $persisted_already_consumed = $chatRoomFormulaRate->getConsumedUnits();

        $endDate = new \DateTime();
        $psychicParticipant = null;

        foreach ($room->getChatParticipants() as $participant) {
            if ($participant->getPsychic() !== null) {
                if ($psychicParticipant === null || $participant->getId() > $psychicParticipant->getId()) {
                    $psychicParticipant = $participant;
                }
            } else {
                $participantLeaveDate = $participant->getLeaveDate();
                if ($participantLeaveDate !== null && $participantLeaveDate < $endDate) {
                    $endDate = $participantLeaveDate;
                }
            }
        }

        if ($psychicParticipant && $psychicParticipant->getLeaveDate() && $psychicParticipant->getLeaveDate() < $endDate) {
            $endDate = $psychicParticipant->getLeaveDate();
        }

        $total_already_consumed = $endDate->getTimestamp() - $chatRoomFormulaRate->getStartDate()->getTimestamp();
        $need_to_be_persisted = $total_already_consumed - $persisted_already_consumed;

        // While we still have units to persit and there is a payment available .... persist them !
        while ($need_to_be_persisted > 0) {
            // How many units we CAN consume ?
            $oldestChatPayment = $ChatPaymentRepository->getOldestNotEmptyPayment($client, $room->getChatType(), $room->getWebsite());

            if (!($oldestChatPayment instanceof ChatPayment)) {
                $json['message'] = 'This client has no credit available for this room.';
                $json['recredit_need'] = true;

                // User have not enough money to pay for this extra time
                // It's part of refresh work to inform user he has no more credit
                // At least, we can close this chatRoomFormulaRate, which can't continue without credit
                $chatRoomFormulaRate->setEndDate($endDate);
                $this->em->flush();

                return $json;
            }

            $can_be_persisted = $oldestChatPayment->getRemainingUnits();

            // Create a consumption based on theses results, don't forget to store the result if we need more payments
            $will_be_persisted = $need_to_be_persisted <= $can_be_persisted ? $need_to_be_persisted : $can_be_persisted;
            if ($will_be_persisted > 0) {
                $chatRoomConsumption = new ChatRoomConsumption();
                $chatRoomConsumption->setUnit($will_be_persisted)
                                    ->setChatPayment($oldestChatPayment)
                                    ->setChatRoomFormulaRate($chatRoomFormulaRate)
                                    ;
                $this->em->persist($chatRoomConsumption);
                $oldestChatPayment->addChatRoomConsumption($chatRoomConsumption);
            }
            $this->em->flush();
            $need_to_be_persisted -= $will_be_persisted;
        }

        if ($force_recredit) {
            $chatRoomFormulaRate->setEndDate($endDate);
            $this->em->flush();
        }

        $json['status'] = 'OK';
        $json['message'] = 'Time consumed updated';

        return $json;
    }

    /**
     * Consume a question from a room.
     *
     * @param ChatRoom $room
     *
     * @return JSON like array
     */
    public function consumeQuestion(ChatRoom $room)
    {
        $ChatPaymentRepository = $this->em->getRepository('KGCChatBundle:ChatPayment');

        $json = array(
            'status' => 'KO',
            'message' => 'Nothing happend',
            'recredit_need' => false,
        );

        if (!$room->getChatType()->isQuestion()) {
            $json['message'] = 'Consumed questions can be updated only on question rooms';

            return $json;
        }

        $client = $room->getClient();
        if (!$this->userManager->isClient($client)) {
            $json['message'] = 'Unable to find the client associated to this room to decrease his credit.';

            return $json;
        }

        $chatRoomFormulaRate = $this->em->getRepository('KGCChatBundle:ChatRoomFormulaRate')->getCurrentWithConsumptions($room);
        if (!($chatRoomFormulaRate instanceof ChatRoomFormulaRate)) {
            $json['message'] = 'There is no formula rate associated at this moment. Check you have enough credit to perform this operation.';
            $json['recredit_need'] = true;

            return $json;
        }

        $oldestChatPayment = $ChatPaymentRepository->getOldestNotEmptyPayment($client, $room->getChatType(), $room->getWebsite());
        $now = new \DateTime();

        if (!($oldestChatPayment instanceof ChatPayment)) {
            $json['message'] = 'This client has no credit available for this room.';
            $json['recredit_need'] = true;

            // User have not enough money to pay for this extra time
            // It's part of refresh work to inform user he has no more credit
            // At least, we can close this chatRoomFormulaRate, which can't continue without credit
            $chatRoomFormulaRate->setEndDate($now);
            $this->em->flush();

            return $json;
        }

        // If we are here, we have a non empty payment, and a place to store the consumption
        $chatRoomConsumption = new ChatRoomConsumption();
        $chatRoomConsumption->setUnit(1)
                            ->setChatPayment($oldestChatPayment)
                            ->setChatRoomFormulaRate($chatRoomFormulaRate)
                            ;
        $this->em->persist($chatRoomConsumption);
        $oldestChatPayment->addChatRoomConsumption($chatRoomConsumption);
        $this->em->flush();

        $json['status'] = 'OK';
        $json['message'] = 'Time consumed updated';

        return $json;
    }

    /**
     * Get rooms for a specific user.
     *
     * @param mixe $user
     *
     * @return array containing rooms object
     */
    public function getRooms($user)
    {
        $ChatParticipantRepository = $this->em->getRepository('KGCChatBundle:ChatParticipant');

        $user_type = $this->userManager->extractType($user);
        if ($user_type === null) {
            $json['message'] = 'Unknown type';

            return $json;
        }

        $rooms = $this->em->getRepository('KGCChatBundle:ChatRoom')->findFullyRoomsByUser($user_type, $user);

        return $rooms;
    }

    /**
     * Convert room's object array to json array.
     *
     * @param ChatRoom $room
     *
     * @return JSON like array
     */
    public function convertRoomToJsonArray(ChatRoom $room)
    {
        $json_room = $room->toJsonArray();
        $json_room['users'] = array();
        $chatType = $this->findOutChatTypeFromChatRoom($room);
        $json_room['chat_type'] = $chatType->toJsonArray();
        $json_room['room_formula_rates'] = $this->convertChatRoomFormulaRatesToJsonArray($room->getChatRoomFormulaRates());
        $json_room['client_leave_reason'] = null;

        // Because the dates are important here, let the object know what time it is here, to calculate amount of seconds the room as started
        $json_room['now'] = (new \DateTime())->getTimestamp();

        $website = $this->findOutWebsiteFromChatRoom($room);
        $json_room['remaining_credit'] = 0;
        // If we know the room type and website, get his remaining credit
        if (($user = $room->getClient()) !== null && $chatType !== null && $website !== null) {
            $json_room['remaining_credit'] = $this->paymentManager->getRemainingCredit($user, $chatType, $website);
        }

        foreach ($room->getChatParticipants() as $chatParticipant) {
            if ($user = $chatParticipant->getUser()) {
                // If user is a psychic, we need to get also the virtual psychic
                $json_user = $this->userManager->convertUserToJsonArray($user);
                if ($virtualPsychic = $chatParticipant->getVirtualPsychic()) {
                    $json_user['virtual_psychic'] = $this->userManager->convertVirtualPsychicToJsonArray($virtualPsychic);
                } else {
                    $json_room['client_leave_reason'] = $chatParticipant->getLeaveReason() ?: null;
                }
                $json_user['has_left'] = $chatParticipant->getLeaveDate() !== null;

                $json_room['users'][] = $json_user;
            }
        }

        if ($previousRoom = $this->em->getRepository('KGCChatBundle:ChatRoom')->findPreviousChatRoomWithMessages($room)) {
            //throw new \Exception(get_class($previousRoom->getChatMessages()).' - '.get_class($room->getChatMessages()));

            $messages = array_merge(
                $previousRoom->getChatMessages()->toArray(),
                $room->getChatMessages()->toArray()
            );
        } else {
            $messages = $room->getChatMessages();
        }

        $json_room['messages'] = $this->messageManager->convertMessagesToJsonArray($messages);

        return $json_room;
    }

    /**
     * Convenience method to convert conversation array.
     *
     * @param array $conversations
     *
     * @return JSON like array
     */
    public function convertConversationsToJsonArray($conversations)
    {
        $json_conversation = array();
        foreach ($conversations as $conversation) {
            $json_conversation[] = $this->convertConversationToJsonArray($conversation);
        }

        return $json_conversation;
    }

    /**
     * Convert conversation room's object array to json array.
     *
     * @param ChatRoom $room
     *
     * @return JSON like array
     */
    public function convertConversationToJsonArray(ChatRoom $room)
    {
        $json_room = $room->toJsonArray();
        $json_room['users'] = array();

        foreach ($room->getChatParticipants() as $chatParticipant) {
            if ($user = $chatParticipant->getUser()) {
                // If user is a psychic, we need to get also the virtual psychic
                $json_user = $this->userManager->convertUserToJsonArray($user);
                if ($virtualPsychic = $chatParticipant->getVirtualPsychic()) {
                    $json_user['virtual_psychic'] = $this->userManager->convertVirtualPsychicToJsonArray($virtualPsychic);
                }
                $json_user['has_left'] = $chatParticipant->getLeaveDate() !== null;

                $json_room['users'][] = $json_user;
            }
        }

        $messages = $room->getChatMessages();

        $json_room['messages'] = $this->messageManager->convertMessagesToJsonArray($messages);

        return $json_room;
    }

    /**
     * Convert chat room formula rates to JSON like array.
     *
     * @param array $chatRoomFormulaRates
     *
     * @return JSON like array
     */
    public function convertChatRoomFormulaRatesToJsonArray($chatRoomFormulaRates = array())
    {
        $json = array();
        foreach ($chatRoomFormulaRates as $rate) {
            $json[] = $rate->toJsonArray();
        }

        return $json;
    }

    /**
     * Find out the chat type of the room.
     *
     * @param ChatRoom $room
     *
     * @return ChatType | null
     */
    public function findOutChatTypeFromChatRoom(ChatRoom $chatRoom)
    {
        $chatType = null;
        // Try to figure out wich chat type is the room thanks to psychic in it
        foreach ($chatRoom->getChatParticipants() as $chatParticipant) {
            if ($user = $chatParticipant->getUser()) {
                if ($this->userManager->isPsychic($user)) {
                    $chatType = $user->getChatType();
                    break;
                }
            }
        }

        return $chatType;
    }

    /**
     * Find out the website of the room.
     *
     * @param ChatRoom $room
     *
     * @return Website | null
     */
    public function findOutWebsiteFromChatRoom(ChatRoom $chatRoom)
    {
        return $chatRoom->getWebsite();
    }

    /**
     * Just an alias for previous method convertRoomToJsonArray.
     */
    public function convertRoomsToJsonArray($rooms = array())
    {
        $json_rooms = array();
        foreach ($rooms as $room) {
            $json_rooms[] = $this->convertRoomToJsonArray($room);
        }

        return $json_rooms;
    }

    /**
     * Ask a room with a specific virtual psychic.
     *
     * @param int $virtual_psychic_id
     *
     * @return JSON like array
     */
    public function askRoom($user, $virtual_psychic_id, $utm_source)
    {
        $json = array(
            'status' => 'KO',
            'message' => 'Nothing happend',
            'room' => null,
            'psychic' => null,
        );

        $user_type = $this->userManager->extractType($user);
        if ($user_type !== UserManager::TYPE_CLIENT) {
            $json['message'] = 'Only client can ask for a room';

            return $json;
        }

        $virtualPsychic = $this->em->getRepository('KGCUserBundle:Voyant')->find($virtual_psychic_id);

        if (!($virtualPsychic instanceof Voyant)) {
            $json['message'] = 'Unknown virtual psychic';

            return $json;
        }

        $psychic = $this->userManager->getPsychicToConverseWith($virtualPsychic);

        if ($psychic === null) {
            $json['message'] = 'There is no psychic available for this conversation.';

            return $json;
        }

        $json['psychic'] = $psychic;

        $source = $this->em->getRepository('KGCSharedBundle:Source')
            ->findOneBy(['code' => $utm_source])
        ;

        $room = new ChatRoom();
        $room->setEntitled($virtualPsychic->getNom().'\'s room')
            ->setWebsite($virtualPsychic->getWebsite())
            ->setChatType($psychic->getChatType())
            ->setSource($source)
        ;
        $this->em->persist($room);
        $this->em->flush();

        // Make the psychic and the client enter directly in the room
        $json_enter_room = $this->enterRoom($room->getId(), $psychic, $virtualPsychic);
        if ($json_enter_room['status'] == 'KO') {
            $json['message'] = $json_enter_room['message'];

            return $json;
        }

        $json_enter_room = $this->enterRoom($room->getId(), $user);
        if ($json_enter_room['status'] == 'KO') {
            $json['message'] = $json_enter_room['message'];

            return $json;
        }

        $json['room'] = $room;

        $json['status'] = 'OK';
        $json['message'] = 'Room created';

        return $json;
    }

    /**
     * A psychic answer to a client request for a room.
     *
     * @param int         $room_id      Room's id
     * @param Utilisateur $psychic
     * @param bool        $is_accepting
     *
     * @return JSON like array
     */
    public function answerRoom($room_id, $user, $is_accepting)
    {
        $ChatParticipantRepository = $this->em->getRepository('KGCChatBundle:ChatParticipant');

        $json = array(
            'status' => 'KO',
            'message' => 'Nothing happend',
            'is_accepting' => null,
            'new_psychic' => null,
            'room' => null,
        );

        $room = $this->em->getRepository('KGCChatBundle:ChatRoom')->find($room_id);
        if ($room === null) {
            $json['message'] = 'Unknown room';

            return $json;
        }

        $user_type = $this->userManager->extractType($user);
        if ($user_type !== UserManager::TYPE_PSYCHIC) {
            $json['message'] = 'Not a psychic';

            return $json;
        }

        $chatParticipant = $ChatParticipantRepository->findOneBy(array(
            $user_type => $user,
            'chatRoom' => $room,
            'leaveDate' => null,
        ));
        if (!($chatParticipant instanceof ChatParticipant)) {
            $json['message'] = 'Chat participant not found';

            return $json;
        }

        if ($room->getStatus() !== ChatRoom::STATUS_NOT_STARTED) {
            $json['message'] = 'Room is not in the good status to be accepted or refused.';

            return $json;
        }

        if ($is_accepting) {
            // Normaly if we are here, client has fullfilled his credit, or else psychic should not have received the possibility to answer
            // Either client had not enough credit when he started the conversation,
            // so chatRoomFormulaRate have already been created because he triggered "resumeRoom", we just have to update the startDate here
            // Or he had enough credit, and we create it now

            $client = $room->getClient();
            if (!($client instanceof Client)) {
                $json['message'] = 'Unable to find the client associated to this room.';

                return $json;
            }

            $chatRoomFormulaRate = $this->em->getRepository('KGCChatBundle:ChatRoomFormulaRate')->findOneBy(array(
                'chatRoom' => $room,
                'endDate' => null,
            ));

            if (!($chatRoomFormulaRate instanceof ChatRoomFormulaRate)) {
                // Create a chat room formula rate by taking the oldest chat payment not yet consumed
                $oldestChatPayment = $this->em->getRepository('KGCChatBundle:ChatPayment')->getOldestNotEmptyPayment($client, $room->getChatType(), $room->getWebsite());

                if (!($oldestChatPayment instanceof ChatPayment)) {
                    $json['message'] = 'This client has no credit available for this room.';

                    return $json;
                }

                $chatRoomFormulaRate = new ChatRoomFormulaRate();
                $chatRoomFormulaRate->setChatRoom($room)
                                    ->setChatFormulaRate($oldestChatPayment->getChatFormulaRate())
                                    ;
                $this->em->persist($chatRoomFormulaRate);
                $room->addChatRoomFormulaRate($chatRoomFormulaRate);
            }

            // Update the start date : if user had not enough credit when he started the conversation, he has gone into the logic to create a chatRoomFormulaRate
            // But unfortunatly for him, the startDate have been set ! To be fair, reset it now (or else he would have paid for the time the psychic has wait to answer ...)
            $chatRoomFormulaRate->setStartDate(new \DateTime());

            $room->setStatus(ChatRoom::STATUS_ON_GOING);
            $this->em->flush();
        } else {
            // The psychic refuse the conversation

            // If user refuse the conversation, search for another psychic available
            $chatParticipant->setLeaveDate(new \DateTime());
            $new_psychic = $this->userManager->getNewPsychicToConverseWith($room, $user);

            if ($new_psychic !== null) {
                $json_enter_room = $this->enterRoom($room->getId(), $new_psychic, $chatParticipant->getVirtualPsychic());
                $json['enter_room'] = $json_enter_room;
                if ($json_enter_room['status'] == 'KO') {
                    $json['message'] = $json_enter_room['message'];

                    return $json;
                }

                // Be sure the new psychic has a good token
                $this->tokenManager->setTokenOnUser($new_psychic);
                $json['new_psychic'] = $new_psychic;
            } else {
                // There is no chance to make room continue because there is no one to keep converse with client
                // Make all chat participants leave the room
                $this->closeRoom($room, ChatRoom::STATUS_REFUSED);
            }
            $this->em->flush();
        }

        $json['is_accepting'] = $is_accepting;
        $json['room'] = $room;
        $json['status'] = 'OK';
        $json['message'] = 'Psychic has answered the request';

        return $json;
    }

    /**
     * Close a room with all its chat participants and chat room formula rates.
     *
     * @param ChatRoom $room
     * @param int      $status If specified, the status will be set with the value. Else it will be ChatRoom::STATUS_CLOSED
     */
    public function closeRoom(ChatRoom &$room, $status = null)
    {
        $now = new \DateTime();
        $chatParticipants = $this->em->getRepository('KGCChatBundle:ChatParticipant')->findBy(array(
            'chatRoom' => $room,
            'leaveDate' => null,
        ));
        foreach ($chatParticipants as $chatParticipant) {
            $chatParticipant->setLeaveDate($now);
        }

        $chatRoomFormulaRates = $this->em->getRepository('KGCChatBundle:ChatRoomFormulaRate')->findBy(array(
            'chatRoom' => $room,
            'endDate' => null,
        ));
        foreach ($chatRoomFormulaRates as $chatRoomFormulaRate) {
            $chatRoomFormulaRate->setEndDate($now);
        }

        if ($status === null) {
            $status = ChatRoom::STATUS_CLOSED;
        }
        $room->setStatus($status);
        $room->setEndDate($now);
        $this->em->flush();
    }

    public function reopenRoom($room_id)
    {
        $json = array(
            'status' => 'KO',
            'message' => 'Nothing happend',
            'enough_credit' => null,
            'room' => null,
        );

        $room = $this->em->getRepository('KGCChatBundle:ChatRoom')->findOneById($room_id);
        if ($room === null) {
            $json['message'] = 'Unknown room';

            return $json;
        }

        $clientParticipant = null;
        $psychicParticipant = null;
        $debug = '';
        foreach ($room->getChatParticipants() as $participant) {
            if ($participant->getClient()) {
                $clientParticipant = $participant;
            } else if ($participant->getPsychic() !== null && $participant->getLeaveDate() ===  null) {
                $psychicParticipant = $participant;
            }
        }

        if ($clientParticipant === null) {
            $json['message'] = 'Unable to find the client associated to this room.';

            return $json;
        }

        if ($psychicParticipant === null) {
            $json['message'] = 'No more psychic active in this room.';

            return $json;
        }

        $oldestChatPayment = $this->em->getRepository('KGCChatBundle:ChatPayment')->getOldestNotEmptyPayment($clientParticipant->getClient(), $room->getChatType(), $room->getWebsite());

        $clientParticipant->setLeaveDate(null);
        $clientParticipant->setLeaveReason(null);
        $this->em->persist($clientParticipant);

        if ($oldestChatPayment instanceof ChatPayment) {
            // add new chat room formula rate to ensure time consumed will be calculated from proper time
            $chatRoomFormulaRate = new ChatRoomFormulaRate();
            $chatRoomFormulaRate->setChatRoom($room)
                                ->setChatFormulaRate($oldestChatPayment->getChatFormulaRate())
                                ;
            $this->em->persist($chatRoomFormulaRate);
            $room->addChatRoomFormulaRate($chatRoomFormulaRate);
        }

        $this->em->flush();

        $json['status'] = 'OK';
        $json['enough_credit'] = $oldestChatPayment instanceof ChatPayment;
        $json['room'] = $room;
        $json['message'] = 'Room reopened';

        return $json;
    }

    /**
     * Try to resume the room after new credit.
     *
     * @param int $room_id
     *
     * @return JSON like array
     */
    public function resumeRoom($room_id)
    {
        $json = array(
            'status' => 'KO',
            'message' => 'Nothing happend',
            'enough_credit' => null,
            'room' => null,
        );

        $room = $this->em->getRepository('KGCChatBundle:ChatRoom')->find($room_id);
        if ($room === null) {
            $json['message'] = 'Unknown room';

            return $json;
        }

        // Normally if we are here, client has (tried to) fullfilled his credit
        // Create a chat room formula rate by taking the oldest chat payment not yet consumed
        $client = $room->getClient();
        if (!($client instanceof Client)) {
            $json['message'] = 'Unable to find the client associated to this room.';

            return $json;
        }

        // If there is a current chatRoomFormulaRate, get it
        $chatRoomFormulaRate = $this->em->getRepository('KGCChatBundle:ChatRoomFormulaRate')->findOneBy(array(
            'chatRoom' => $room,
            'endDate' => null,
        ));
        if ($chatRoomFormulaRate instanceof ChatRoomFormulaRate) {
            // If there is one chatRoomFormulaRate still opened, close it
            $chatRoomFormulaRate->setEndDate(new \DateTime());
            $this->em->flush();
        }

        $oldestChatPayment = $this->em->getRepository('KGCChatBundle:ChatPayment')->getOldestNotEmptyPayment($client, $room->getChatType(), $room->getWebsite());

        if (!($oldestChatPayment instanceof ChatPayment)) {
            $json['message'] = 'This client has no credit available for this room.';
            $json['enough_credit'] = false;

            return $json;
        }

        $chatRoomFormulaRate = new ChatRoomFormulaRate();
        $chatRoomFormulaRate->setChatRoom($room)
                            ->setChatFormulaRate($oldestChatPayment->getChatFormulaRate())
                            ;
        $this->em->persist($chatRoomFormulaRate);
        $room->addChatRoomFormulaRate($chatRoomFormulaRate);

        $this->em->flush();

        $json['status'] = 'OK';
        $json['enough_credit'] = true;
        $json['room'] = $room;
        $json['message'] = 'Room resumed';

        return $json;
    }

    /**
     * Decrement the room by his chat type
     * If it's minute room, process from the last consumption
     * If it's question room, decrement 1.
     *
     * @param int  $room_id
     * @param bool $force_recredit If true, chatRoomFormulaRate will have an endDate
     *
     * @return JSON like array
     */
    public function decrementRoom($room_id, $force_recredit = false)
    {
        $json = array(
            'status' => 'KO',
            'message' => 'Nothing happend',
            'room' => null,
        );

        $room = $this->em->getRepository('KGCChatBundle:ChatRoom')->find($room_id);
        if ($room === null) {
            $json['message'] = 'Unknown room';

            return $json;
        }

        $json['room'] = $room;

        if ($room->getChatType()->isMinute()) {
            $update_json = $this->updateTimeConsumed($room, $force_recredit);
            if ($update_json['status'] === 'KO') {
                $json['message'] = $update_json['message'];

                return $json;
            }
        } elseif ($room->getChatType()->isQuestion()) {
            $update_json = $this->consumeQuestion($room);
            if ($update_json['status'] === 'KO') {
                $json['message'] = $update_json['message'];

                return $json;
            }
        } else {
            $json['message'] = 'Unknown room type';

            return $json;
        }

        $json['status'] = 'OK';
        $json['message'] = 'Room decremented';

        return $json;
    }
}
