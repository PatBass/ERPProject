<?php

namespace KGC\ChatBundle\Service;

use JMS\DiExtraBundle\Annotation as DI;
use KGC\ChatBundle\Entity\ChatMessage;

/**
 * @DI\Service("kgc.chat.message.manager")
 */
class MessageManager
{
    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * @param UserManager $userManager
     *
     * @DI\InjectParams({
     *     "userManager" = @DI\Inject("kgc.chat.user.manager")
     * })
     */
    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * Sanitize a content to store it in db.
     *
     * @param string $content
     *
     * @return string
     */
    public function sanitize($content)
    {
        return htmlspecialchars(trim($content));
    }

    /**
     * Convert message's object array to json array.
     *
     * @param ChatMessage $message
     *
     * @return JSON like array
     */
    public function convertMessageToJsonArray(ChatMessage $message)
    {
        $json_message = $message->toJsonArray();
        if ($user = $message->getChatParticipant()->getUser()) {
            $json_message['sender'] = $this->userManager->convertUserToJsonArray($user);

            $json_room['messages'][] = $json_message;
        }

        return $json_message;
    }

    /**
     * Alias for convertMessageToJsonArray.
     */
    public function convertMessagesToJsonArray($messages = array())
    {
        $json_messages = array();

        foreach ($messages as $message) {
            $json_messages[] = $this->convertMessageToJsonArray($message);
        }

        return $json_messages;
    }
}
