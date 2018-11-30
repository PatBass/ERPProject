<?php

namespace KGC\UserBundle\Handler;

use JMS\DiExtraBundle\Annotation as DI;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;
use KGC\ChatBundle\Service\UserManager;

/**
 * @DI\Service("kgc.user.logout_handler")
 */
class LogoutHandler implements LogoutHandlerInterface
{
    /**
     * @var SecurityContextInterface
     */
    protected $security;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @param SecurityContextInterface $security
     *
     * @DI\InjectParams({
     *     "em" = @DI\Inject("doctrine.orm.entity_manager")
     * })
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * If the user is a psychic, set him unavailable.
     *
     * @param Request        $request
     * @param Response       $response
     * @param TokenInterface $authToken
     */
    public function logout(Request $request, Response $response, TokenInterface $authToken)
    {
        $user = $authToken->getUser();
        if (UserManager::staticIsPsychic($user)) {
            $user->setIsChatAvailable(false);
            $this->em->flush();
        }
    }
}
