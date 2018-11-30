<?php

namespace KGC\ChatBundle\Service;

use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @DI\Service("kgc.chat.token.manager")
 */
class TokenManager
{
    /**
     * Key that will be used to store token in session.
     */
    const TOKEN_KEY = 'chat_jwt_token';

    /**
     * @var Session
     */
    protected $session;

    /**
     * @param Session    $session
     * @param JWTManager $jwtManager
     *
     * @DI\InjectParams({
     *     "session" = @DI\Inject("session"),
     *     "jwtManager" = @DI\Inject("kgc.chat.jwt.manager")
     * })
     */
    public function __construct(Session $session, JWTManager $jwtManager)
    {
        $this->session = $session;
        $this->jwtManager = $jwtManager;
    }

    /**
     * Create and store a jwt token for the current user.
     *
     * @param UserInterface $user
     *
     * @return string The token created
     */
    public function createToken(UserInterface $user)
    {
        $tokensArray = $this->getTokensArray();

        $token = $this->jwtManager->create($user);

        if (UserManager::staticIsClient($user)) {
            $tokensArray['client'][$user->getOrigin()][$user->getUsername()] = $token;
        } elseif (UserManager::staticIsPsychic($user)) {
            $tokensArray['psychic'][$user->getUsername()] = $token;
        }

        $this->storeTokensArray($tokensArray);

        return $token;
    }

    /**
     * Get the current storred token.
     *
     * @return string
     */
    public function getToken(UserInterface $user)
    {
        $tokensArray = $this->getTokensArray();
        $token = null;
        if (UserManager::staticIsClient($user)) {
            if (isset($tokensArray['client'][$user->getOrigin()][$user->getUsername()])) {
                $token = $tokensArray['client'][$user->getOrigin()][$user->getUsername()];
            }
        } elseif (UserManager::staticIsPsychic($user)) {
            if (isset($tokensArray['psychic'][$user->getUsername()])) {
                $token = $tokensArray['psychic'][$user->getUsername()];
            }
        }

        return $token;
    }

    /**
     * Create a user's token and set him a property containing it.
     *
     * @param UserInterface $user
     *
     * @return token
     */
    public function setTokenOnUser(UserInterface &$user)
    {
        $token = $this->createToken($user);
        $property = self::TOKEN_KEY;
        $user->$property = $token;

        return $token;
    }

    /**
     * Search in user if he has the property containing token defined
     * If yes, return the token.
     *
     * @param UserInterface $user
     */
    public static function getTokenFromUser(UserInterface $user)
    {
        $property = self::TOKEN_KEY;
        if (property_exists($user, $property)) {
            return $user->$property;
        }

        return;
    }

    /**
     * Retrieve the token array stored.
     *
     * @return array
     */
    private function getTokensArray()
    {
        return $this->session->get(self::TOKEN_KEY, array());
    }

    /**
     * Store the token array.
     */
    private function storeTokensArray($tokensArray)
    {
        $this->session->set(self::TOKEN_KEY, $tokensArray);
    }
}
