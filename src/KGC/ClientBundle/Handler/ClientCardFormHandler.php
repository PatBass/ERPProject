<?php

namespace KGC\ClientBundle\Handler;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * ProspectFormHandler.
 *
 * Traitement des formulaires concernant les clients
 *
 * @category Form/Handler
 *
 * @author Nicolas Mendez <nicolas.kgcom@gmail.com>
 *
 * @DI\Service("kgc.client.cardformhandler")
 */
class ClientCardFormHandler
{
    /**
     * @var Symfony\Component\EventDispatcher\EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    /**
     * @var SecurityContextInterface
     */
    protected $security;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param EntityManager $entityManager
     * @param TokenStorageInterface $security
     *
     * @DI\InjectParams({
     *     "eventDispatcher" = @DI\Inject("event_dispatcher"),
     *     "entityManager"   = @DI\Inject("doctrine.orm.entity_manager"),
     *     "security"        = @DI\Inject("security.token_storage")
     * })
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        EntityManager $entityManager,
        TokenStorageInterface $security
    )
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    /**
     * Get a user from the Security Token Storage.
     *
     * @return mixed
     *
     * @throws \LogicException If SecurityBundle is not available
     *
     * @see TokenInterface::getUser()
     */
    public function getUser()
    {
        if (!$this->security) {
            throw new \LogicException('The SecurityBundle is not registered in your application.');
        }

        if (null === $token = $this->security->getToken()) {
            return;
        }

        if (!is_object($user = $token->getUser())) {
            return;
        }

        return $user;
    }

    /**
     * Traitement du formulaire.
     *
     *
     * @param Symfony\Component\Form\Form $form formulaire à traiter
     * @param Symfony\Component\HttpFoundation\Request $request requête http
     * @param bool $persist Les entités doivent elles être persistées ?
     *
     * @return bool traitement réussi ?
     */
    public function process(Form $form, Request $request, $persist = false)
    {
        $form->handleRequest($request);
        $prospect = $form->getData();
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->entityManager->persist($prospect);
                // succès flush
                $this->entityManager->flush();
                return true;
            } else {
                return false;
            }
        } else {
            return;
        }
    }

}
