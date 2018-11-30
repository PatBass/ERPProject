<?php

// src/KGC/ClientBundle/Form/Handler/ClientSmsFormHandler.php


namespace KGC\ClientBundle\Handler;

use KGC\ClientBundle\Elastic\Event\ClientEvent;
use KGC\ClientBundle\Events\ClientActionEvent;
use KGC\ClientBundle\Service\ClientManager;
use KGC\RdvBundle\Elastic\Event\RdvEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;
use KGC\RdvBundle\Service\Encryption;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * ClientSmsFormHandler.
 *
 * Traitement des formulaires concernant les clients
 *
 * @category Form/Handler
 *
 * @author Laurene Dourdin <2aurene@gmail.com>
 *
 * @DI\Service("kgc.client.sms.formhandler")
 */
class ClientSmsFormHandler
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
     * @var KGC\ClientBundle\Service\ClientManager
     */
    protected $clientManager;

    /**
     * @var SecurityContextInterface
     */
    protected $security;

    /**
     * @var KGC\RdvBundle\Service\Encryption
     */
    protected $encryptionService;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param EntityManager $entityManager
     * @param ClientManager $clientManager
     * @param TokenStorageInterface $security
     * @param Encryption $encryptionService
     *
     * @DI\InjectParams({
     *     "eventDispatcher" = @DI\Inject("event_dispatcher"),
     *     "entityManager"   = @DI\Inject("doctrine.orm.entity_manager"),
     *     "clientManager"      = @DI\Inject("kgc.client.manager"),
     *     "security"        = @DI\Inject("security.token_storage"),
     *   "encryptionService" = @DI\Inject("kgc.rdv.encryption.service")
     * })
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        EntityManager $entityManager,
        ClientManager $clientManager,
        TokenStorageInterface $security,
        Encryption $encryptionService
    )
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->entityManager = $entityManager;
        $this->clientManager = $clientManager;
        $this->security = $security;
        $this->encryptionService = $encryptionService;
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
        $client = $form->getData();
        $formRequest = $request->get('kgc_ClientBundle_mail');
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $events = array();
                $aEvents = array('onSmsSend');
                $website = $this->entityManager
                    ->getRepository('KGCSharedBundle:Client')
                    ->getWebsiteByCLient($client)
                ;
                foreach ($aEvents as $eventaction) {
                    $events[] = array(
                        'name' => constant('\KGC\ClientBundle\Events\ClientEvents::' . $eventaction),
                        'object' => new ClientActionEvent($client, $this->getUser(), $form, $website),
                    );
                }

                // Distribution des évènements
                foreach ($events as $event) {
                    if ($this->eventDispatcher->hasListeners($event['name'])) {
                        $this->eventDispatcher->dispatch($event['name'], $event['object']);
                    }
                }

                // persist du rdv si besoin
                if ($persist) {
                    $this->entityManager->persist($client);
                }

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
