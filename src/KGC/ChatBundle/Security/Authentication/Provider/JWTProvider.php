<?php

namespace KGC\ChatBundle\Security\Authentication\Provider;

use JMS\DiExtraBundle\Annotation as DI;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Provider\JWTProvider as LexikJWTProvider;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTManagerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @DI\Service("kgc.chat.jwt.provider")
 */
class JWTProvider extends LexikJWTProvider
{
    /**
     * @var UserProviderInterface
     */
    protected $userProvider;

    /**
     * @var JWTManagerInterface
     */
    protected $jwtManager;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var string
     */
    protected $userIdentityField;

    /**
     * @DI\InjectParams({
     *     "userProvider" = @DI\Inject("kgc.chat.client.provider"),
     *     "jwtManager" = @DI\Inject("kgc.chat.jwt.manager"),
     *     "dispatcher" = @DI\Inject("event_dispatcher")
     * })
     */
    public function __construct(UserProviderInterface $userProvider, JWTManagerInterface $jwtManager, EventDispatcherInterface $dispatcher)
    {
        $this->userProvider = $userProvider;
        $this->jwtManager = $jwtManager;
        $this->dispatcher = $dispatcher;
        $this->userIdentityField = 'username';
    }

    /**
     * Load user from payload, using username by default.
     * Override this to load by another property.
     *
     * @param array $payload
     *
     * @return \Symfony\Component\Security\Core\User\UserInterface
     */
    protected function getUserFromPayload(array $payload)
    {
        if (!isset($payload[$this->userIdentityField])) {
            throw new AuthenticationException('Invalid JWT Token');
        }

        $user = $this->userProvider->loadUserByUsernameAndOrigin($payload['username'], $payload['origin']);

        if (!$user) {
            throw new AuthenticationException('User not found');
        }

        return $user;
    }
}
