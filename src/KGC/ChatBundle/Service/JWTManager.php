<?php

namespace KGC\ChatBundle\Service;

use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTManager as LexikJWTManager;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

/**
 * @DI\Service("kgc.chat.jwt.manager")
 */
class JWTManager extends LexikJWTManager
{
    /**
     * @param JWTEncoderInterface      $encoder
     * @param EventDispatcherInterface $dispatcher
     * @param int                      $ttl
     *
     * @DI\InjectParams({
     *     "encoder" = @DI\Inject("lexik_jwt_authentication.encoder"),
     *     "dispatcher" = @DI\Inject("event_dispatcher"),
     *     "ttl" = @DI\Inject("%lexik_jwt_authentication.token_ttl%")
     * })
     */
    public function __construct(JWTEncoderInterface $encoder, EventDispatcherInterface $dispatcher, $ttl)
    {
        parent::__construct($encoder, $dispatcher, $ttl);
    }

    /**
     * Extends JWTManager to override this method and add origin to payload
     * Two users can have the same username if they come from two website.
     *
     * @param UserInterface $user
     * @param array         $payload
     */
    protected function addUserIdentityToPayload(UserInterface $user, array &$payload)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $payload['username'] = $accessor->getValue($user, 'username');
        if (UserManager::staticIsPsychic($user)) {
            $payload['type'] = 'psychic';
        } elseif (UserManager::staticIsClient($user)) {
            $payload['type'] = 'client';
            $payload['origin'] = $accessor->getValue($user, 'origin');
        }
    }
}
