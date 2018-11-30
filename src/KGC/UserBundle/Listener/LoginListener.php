<?php

namespace KGC\UserBundle\Listener;

use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use KGC\ChatBundle\Service\TokenManager;
use KGC\UserBundle\Entity\Utilisateur;

/**
 * @DI\Service("kgc.user.login_listener")
 * @DI\Tag("kernel.event_listener", attributes = {
 *       "event" = "security.interactive_login",
 *       "method" = "onSecurityInteractiveLogin"
 *   })
 */
class LoginListener
{
    /**
     * @var SecurityContextInterface
     */
    protected $security;

    /**
     * @var TokenManager
     */
    protected $tokenManager;

    /**
     * @param SecurityContextInterface $security
     *
     * @DI\InjectParams({
     *     "security" = @DI\Inject("security.context"),
     *     "tokenManager" = @DI\Inject("kgc.chat.token.manager")
     * })
     */
    public function __construct(SecurityContextInterface $security, TokenManager $tokenManager)
    {
        $this->security = $security;
        $this->tokenManager = $tokenManager;
    }

    /**
     * Add listener to set jwt token for psychics when they connect to kgestion.
     *
     * @param InteractiveLoginEvent $event
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        // Because every one logging (Client, Psychic) will trigger this listener, filter request with $user type.
        // It must be an instance of Utilisateur 
        $user = $this->security->getToken()->getUser();
        if ($user instanceof Utilisateur && $user->isVoyant()) {
            $this->tokenManager->createToken($user);
        }
    }
}
