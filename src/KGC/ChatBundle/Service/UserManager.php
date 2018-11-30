<?php

namespace KGC\ChatBundle\Service;

use JMS\DiExtraBundle\Annotation as DI;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\TranslatorInterface;
use KGC\UserBundle\Entity\Utilisateur;
use KGC\UserBundle\Entity\Voyant;
use KGC\ChatBundle\Entity\ChatRoom;
use KGC\Bundle\SharedBundle\Service\SharedWebsiteManager;
use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\Bundle\SharedBundle\Entity\Website;

/**
 * @DI\Service("kgc.chat.user.manager")
 */
class UserManager
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var TokenManager
     */
    protected $tokenManager;

    /**
     * @var TranslatorInterface
     */
    protected $t;

    /**
     * @var SharedWebsiteManager
     */
    protected $sharedWebsiteManager;

    /**
     * @param EntityManager $em
     *
     * @DI\InjectParams({
     *     "em" = @DI\Inject("doctrine.orm.entity_manager"),
     *     "tokenManager" = @DI\Inject("kgc.chat.token.manager"),
     *     "t" = @DI\Inject("translator"),
     *     "sharedWebsiteManager" = @DI\Inject("kgc.shared.website.manager")
     * })
     */
    public function __construct(EntityManager $em, TokenManager $tokenManager, TranslatorInterface $t, SharedWebsiteManager $sharedWebsiteManager)
    {
        $this->em = $em;
        $this->tokenManager = $tokenManager;
        $this->t = $t;
        $this->sharedWebsiteManager = $sharedWebsiteManager;
    }
    /**
     * Will be used to check if user is a client or psychic.
     */
    const TYPE_CLIENT = 'client';
    const TYPE_PSYCHIC = 'psychic';

    /**
     * Check if user provided is of good type or not
     * Could be client or psychic.
     *
     * @param mixed $user
     *
     * @return string
     */
    public static function staticExtractType($user)
    {
        $user_type = null;
        if ($user instanceof Utilisateur && $user->isVoyant()) {
            $user_type = self::TYPE_PSYCHIC;
        } elseif ($user instanceof Client) {
            $user_type = self::TYPE_CLIENT;
        }

        return $user_type;
    }

    /**
     * Check if a user is a Client.
     *
     * @param mixed $user
     *
     * @return bool
     */
    public static function staticIsClient($user)
    {
        return self::staticExtractType($user) === self::TYPE_CLIENT;
    }

    /**
     * Check if a user is a Psychic.
     *
     * @param mixed $user
     *
     * @return bool
     */
    public static function staticIsPsychic($user)
    {
        return self::staticExtractType($user) === self::TYPE_PSYCHIC;
    }

    /**
     * Convenience method for self::extractType.
     */
    public function extractType($user)
    {
        return self::staticExtractType($user);
    }

    /**
     * Convenience method for self::isClient.
     */
    public function isClient($user)
    {
        return self::staticIsClient($user);
    }

    /**
     * Convenience method for self::isPsychic.
     */
    public function isPsychic($user)
    {
        return self::staticIsPsychic($user);
    }

    /**
     * Convert user (Voyant, Utilisateur or Client) to json array.
     *
     * @param mixed $user
     *
     * @return JSON like array
     */
    public function convertUserToJsonArray($user)
    {
        $json = array(
            'id' => $user->getId(),
            'type' => $this->extractType($user),
            'username' => $user->getUsername(),
        );

        if ($json['type'] == self::TYPE_PSYCHIC) {
            $json += $this->convertPsychicToJsonArray($user);
        } elseif ($json['type'] == self::TYPE_CLIENT) {
            $json += $this->convertClientToJsonArray($user);
        }

        return $json;
    }

    /**
     * Convert Client to json array.
     *
     * @param Client $client
     *
     * @return JSON like array
     */
    public function convertClientToJsonArray($client)
    {
        $client_json = array(
            'id' => $client->getId(),
            'nom' => $client->getNom(),
            'prenom' => $client->getPrenom(),
            'dateNaissance' => $client->getDateNaissance()->format('Y-m-d'),
            'email' => $client->getEmail(),
            'origin' => $client->getOrigin(),
            'token' => TokenManager::getTokenFromUser($client),
            'isNew' => (0 === count($client->getChatParticipants()) ? true : false),
            'info_subject' => $client->getChatInfoSubject(),
            'info_partner' => $client->getChatInfoPartner(),
            'info_advice' => $client->getChatInfoAdvice(),
        );

        return $client_json;
    }

    /**
     * Convert psychic (Voyant or Utilisateur) to json array.
     *
     * @param mixed $psychic
     *
     * @return JSON like array
     */
    public function convertPsychicToJsonArray($psychic)
    {
        $psychic_json = array(
            'sexe' => $psychic->getSexe(),
            'is_available' => $psychic->getIsChatAvailable(),
            'token' => TokenManager::getTokenFromUser($psychic),
        );

        return $psychic_json;
    }

    /**
     * Convert Voyant to json array.
     *
     * @param Voyant $virtualPsychic
     *
     * @return JSON like array
     */
    public function convertVirtualPsychicToJsonArray($virtualPsychic)
    {
        $virtual_psychic_json = array(
            'id' => (int) $virtualPsychic->getId(),
            'nom' => $virtualPsychic->getNom(),
            'reference' => $virtualPsychic->getReference(),
            'sexe' => $virtualPsychic->getSexe(),
        );

        return $virtual_psychic_json;
    }

    /**
     * Set user availability.
     *
     * @param Utilisateur $user
     * @param bool        $availability_status
     * @param bool        $force               If true, don't check if user is still in rooms
     *
     * @return JSON like array
     */
    public function setAvailability(Utilisateur $user, $availability_status, $force = false)
    {
        $json = array(
            'status' => 'KO',
            'message' => 'Nothing happend',
            'token' => null,
        );

        if (!is_bool($availability_status)) {
            $json['message'] = 'Availability status must be a boolean. '.gettype($availability_status).' received.';
        }

        if (!$availability_status) {
            // If we are force to set availability, don't check if user is still in room(s)
            if (!$force) {
                // If user want to be unavailable, check that user is not currently in any room
                $chatParticipants = $this->em->getRepository('KGCChatBundle:ChatParticipant')->findBy(array(
                    'psychic' => $user,
                    'leaveDate' => null,
                ));
                if (count($chatParticipants) > 0) {
                    $json['message'] = $this->t->trans('chat.you_cant_be_unavailable_if_you_are_in_conversation');

                    return $json;
                }
            }
        } else {
            // If user decide to become available, recreate his token and return him
            $json['token'] = $this->tokenManager->createToken($user);
        }

        $user->setIsChatAvailable($availability_status);
        $this->em->flush();

        $json['status'] = 'OK';
        $json['message'] = 'Availability set to '.($availability_status ? 'Available' : 'Unavailable');

        return $json;
    }

    /**
     * Check if a virtual psychic is available for conversation by tring to select a psychic to converse with.
     *
     * @param Voyant $virtualPsychic
     *
     * @return Utilisateur | null
     */
    public function getPsychicToConverseWith(Voyant $virtualPsychic)
    {
        $chatTypeSelected = null;
        $today = new \DateTime;
        foreach ($virtualPsychic->getWebsite()->getChatFormulas() as $formula) {
            if ($formula->getDesactivationDate() === null || $formula->getDesactivationDate() > $today) {
                $chatTypeSelected = $formula->getChatType();
            }
        }

        if ($chatTypeSelected === null) {
            return;
        }

        // By default, select the first psychic available with the same sexe
        $UtilisateurRepository = $this->em->getRepository('KGCUserBundle:Utilisateur');
        $users = $UtilisateurRepository->findAvailableUsersForChat();

        // Match the selected chat type with the first available psychic corresponding
        foreach ($users as $user) {
            if ($user->getChatType() === null) {
                continue;
            }

            if (
                $user->getChatType()->getId() === $chatTypeSelected->getId()
                && (
                    ($virtualPsychic->getUtilisateur() === null && $user->getSexe() === $virtualPsychic->getSexe())
                    || ($virtualPsychic->getUtilisateur() && $virtualPsychic->getUtilisateur()->getId() == $user->getId())
                )
            ) {
                return $user;
            }
        }

        // Maybe no one is available ...
        return;
    }

    /**
     * Try to get a new psychic to converse in room, in case of the first one has answered "no"
     * We need the old psychic to get a new one exactly like him.
     *
     * @param Room        $room       The room concerned
     * @param Utilisateur $oldPsychic The old psychic
     *
     * @return Utilisateur | null
     */
    public function getNewPsychicToConverseWith(ChatRoom $room, Utilisateur $oldPsychic)
    {
        $already_present_user_ids = [];
        $ignoreOthers = false;

        foreach ($room->getChatParticipants() as $chatParticipant) {
            // if chat room virtual psychic is linked to a specific real psychic, don't propose a new one
            if (
                ($voyant = $chatParticipant->getVirtualPsychic())
                && $voyant->getUtilisateur() !== null
            ) {
                $ignoreOthers = true;
                break;
            }
            if ($this->isPsychic($chatParticipant->getPsychic())) {
                $already_present_user_ids[] = $chatParticipant->getPsychic()->getId();
            }
        }

        if ($ignoreOthers) {
            return;
        }

        // By default, select the first psychic available with the same sexe
        $UtilisateurRepository = $this->em->getRepository('KGCUserBundle:Utilisateur');
        $users = $UtilisateurRepository->findAvailableUsersForChat();

        // Add old psychic to already presents
        $already_present_user_ids[] = $oldPsychic->getId();

        // Match the selected chat type with the first available psychic corresponding
        foreach ($users as $user) {
            if (!in_array($user->getId(), $already_present_user_ids) && $user->getSexe() === $oldPsychic->getSexe() && $user->getChatType()->getId() === $oldPsychic->getChatType()->getId()) {
                return $user;
            }
        }

        // Maybe no one is available ...
        return;
    }

    /**
     * Get chat history for a specific client.
     *
     * @param string $website_slug
     * @param Client $client
     *
     * @return array
     */
    public function getChatHistory($website_slug, Client $client)
    {
        $json = array(
            'status' => 'KO',
            'message' => 'Nothing happend',
            'conversations' => array(),
        );

        $website = $this->sharedWebsiteManager->getWebsiteBySlug($website_slug);
        if (!($website instanceof Website)) {
            $json['message'] = 'Unknown website';

            return $json;
        }

        $conversations = $this->em->getRepository('KGCChatBundle:ChatRoom')->findConversationsByWebsiteAndClient($website, $client);

        $json = array(
            'status' => 'OK',
            'message' => 'Conversations retrieved',
            'conversations' => $conversations,
        );

        return $json;
    }
}
