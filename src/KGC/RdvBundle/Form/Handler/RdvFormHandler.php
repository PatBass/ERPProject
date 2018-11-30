<?php
// src/KGC/RdvBundle/Form/Handler/RdvFormHandler.php

namespace KGC\RdvBundle\Form\Handler;

use KGC\ClientBundle\Elastic\Event\ClientEvent;
use KGC\RdvBundle\Elastic\Event\RdvEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;
use KGC\RdvBundle\Service\Encryption;
use KGC\RdvBundle\Service\RdvManager;
use KGC\RdvBundle\Service\SuiviRdvManager;
use KGC\RdvBundle\Events\RDVActionEvent;
use KGC\RdvBundle\Entity\ActionSuivi;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * RdvFormHandler.
 *
 * Traitement des formulaires concernant les consultations
 *
 * @category Form/Handler
 *
 * @author Laurene Dourdin <2aurene@gmail.com>
 *
 * @DI\Service("kgc.rdv.formhandler")
 */
class RdvFormHandler
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
     * @var KGC\RdvBundle\Service\RdvManager
     */
    protected $rdvManager;

    /**
     * @var KGC\RdvBundle\Service\SuiviRdvManager
     */
    protected $suiviRdvManager;

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
     * @param RdvManager $rdvManager
     * @param SuiviRdvManager $suiviRdvManager
     * @param TokenStorageInterface $security
     * @param Encryption $encryptionService
     *
     * @DI\InjectParams({
     *     "eventDispatcher" = @DI\Inject("event_dispatcher"),
     *     "entityManager"   = @DI\Inject("doctrine.orm.entity_manager"),
     *     "rdvManager"      = @DI\Inject("kgc.rdv.manager"),
     *     "suiviRdvManager" = @DI\Inject("kgc.suivirdv.manager"),
     *     "security"        = @DI\Inject("security.token_storage"),
     *   "encryptionService" = @DI\Inject("kgc.rdv.encryption.service")
     * })
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        EntityManager $entityManager,
        RdvManager $rdvManager,
        SuiviRdvManager $suiviRdvManager,
        TokenStorageInterface $security,
        Encryption $encryptionService
    )
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->entityManager = $entityManager;
        $this->rdvManager = $rdvManager;
        $this->suiviRdvManager = $suiviRdvManager;
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
        $rdv = $form->getData();
        $formRequest = $request->get('kgc_RdvBundle_rdv');
        $isPause = !empty($formRequest['pause']);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $mainAction = $form->get('mainaction')->getData();

                $this->checkCardChange($form);

                // Création de l'entrée d'historique
                $this->suiviRdvManager->create($rdv);
                $this->suiviRdvManager->setCommentaire($form->get('commentaire')->getData());

                // Affiliation transparente
                // si le champs est désactivé, il faut envoyer null pour garder la valeur de l'idastro retrouvé
                $idastro = $form['idAstro']->isDisabled() ? null : $form['idAstro']['valeur']->getData();
                $this->rdvManager->preventDuplicate($rdv, $idastro, $persist);

                $this->rdvManager->fixEntities($rdv);

                // Préparation des events d'action formulaire + ajout à l'historique
                $action_rep = $this->entityManager->getRepository('KGCRdvBundle:ActionSuivi');
                $main_action = $action_rep->findOneByIdcode($mainAction);
                $this->suiviRdvManager->setMainAction($main_action);

                $action_list = array_merge(
                    array($main_action),
                    array_keys($form->get('actions')->getConfig()->getOption('choices'))
                );

                $events = array();
                foreach ($action_list as $action) {
                    if ($action !== null) {
                        if (!$action instanceof ActionSuivi) {
                            $search_action = $action;
                            $action = $action_rep->findOneByIdcode($action);
                            if (!$action instanceof ActionSuivi) {
                                throw new NotFoundHttpException(
                                    sprintf('Action with idcode: %s not found', $search_action)
                                );
                            }
                        }
                        $this->suiviRdvManager->addAction($action);
                        $eventaction = $action->getEvent();
                        if ($eventaction !== null) {
                            $events[] = array(
                                'name' => constant('\KGC\RdvBundle\Events\RDVEvents::' . $eventaction),
                                'object' => new RDVActionEvent($rdv, $this->getUser(), $form),
                            );
                        }
                    }
                }

                // Distribution des évènements
                foreach ($events as $event) {
                    if ($this->eventDispatcher->hasListeners($event['name'])) {
                        $this->eventDispatcher->dispatch($event['name'], $event['object']);
                    }
                }

                // persist du rdv si besoin
                if ($persist) {
                    $this->entityManager->persist($rdv);
                }

                // mise à jour de l'historique avec nouveaux paramètres du rdv
                $this->suiviRdvManager->fillRdvInfos($rdv);
                
                // succès flush
                $this->entityManager->flush();

                $this->eventDispatcher->dispatch(RdvEvent::REFRESH, new RdvEvent($rdv));
                $this->eventDispatcher->dispatch(ClientEvent::REFRESH, new ClientEvent($rdv->getClient()));

                return true;
            } else {
                return false;
            }
        } else {
            return;
        }
    }

    protected function checkCardChange($form)
    {
        $uow = $this->entityManager->getUnitOfWork();

        foreach ($form['cartebancaires'] as $cbFormField) {
            $carteBancaire = $cbFormField->getData();

            if ($originalData = $uow->getOriginalEntityData($carteBancaire)) {
                $hasBeenModified = false;

                foreach (['numero', 'expiration', 'cryptogramme'] as $field) {
                    $getterMethod = 'get' . ucfirst($field);
                    if ($carteBancaire->$getterMethod() !== $this->encryptionService->decrypt($originalData[$field])) {
                        $hasBeenModified = true;
                        break;
                    }
                }

                if ($hasBeenModified) {
                    // we reset forbidden status
                    $carteBancaire->setInterdite(false);

                    foreach ($carteBancaire->getPaymentAliases() as $alias) {
                        $carteBancaire->removePaymentAlias($alias);
                    }
                }
            }
        }
    }
}
