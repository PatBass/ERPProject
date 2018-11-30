<?php

namespace KGC\UserBundle\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use KGC\UserBundle\Entity\Utilisateur;
use KGC\UserBundle\Service\UserManager;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * @DI\Service("kgc.user.listener")
 * @DI\DoctrineListener(
 *     events = {"prePersist"},
 *     connection = "default",
 *     priority = 0,
 * )
 */
class UserListener
{
    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * @param UserManager $userManager
     * @DI\InjectParams({
     *                                 "userManager" = @DI\Inject("kgc.user.manager")
     *                                 })
     */
    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof Utilisateur) {
            $flush = false;
            $this->userManager->updateUser($entity, $flush);
        }
    }
}
