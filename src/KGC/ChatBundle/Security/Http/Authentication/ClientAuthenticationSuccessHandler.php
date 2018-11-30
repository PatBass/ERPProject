<?php

namespace KGC\ChatBundle\Security\Http\Authentication;

use JMS\DiExtraBundle\Annotation as DI;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler as JWTAuthenticationSuccessHandler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use KGC\ChatBundle\Service\JWTManager;

/**
 * @DI\Service("kgc.chat.client.authentication_success_handler")
 */
class ClientAuthenticationSuccessHandler extends JWTAuthenticationSuccessHandler
{
    /**
     * @var JWTManager
     */
    protected $jwtManager;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @param JWTManager               $jwtManager
     * @param EventDispatcherInterface $dispatcher
     *
     * @DI\InjectParams({
     *     "jwtManager" = @DI\Inject("kgc.chat.jwt.manager"),
     *     "dispatcher" = @DI\Inject("event_dispatcher")
     * })
     */
    public function __construct(JWTManager $jwtManager, EventDispatcherInterface $dispatcher)
    {
        $this->jwtManager = $jwtManager;
        $this->dispatcher = $dispatcher;
    }
}
