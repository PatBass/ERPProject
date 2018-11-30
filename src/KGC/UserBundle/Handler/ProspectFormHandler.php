<?php

namespace KGC\UserBundle\Handler;

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
 * ProspectFormHandler.
 *
 * Traitement des formulaires concernant les prospects
 *
 * @category Form/Handler
 *
 * @author Nicolas Mendez <nicolas.kgcom@gmail.com>
 *
 * @DI\Service("kgc.prospect.formhandler")
 */
class ProspectFormHandler
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
                if (!empty($form->get('website')) && !empty($form->get('website')->getData())) {
                    $website = $form->get('website')->getData();
                    $prospect->setMyastroWebsite(strtolower($website->getLibelle()));
                }
                if (!empty($form->get('source')) && !empty($form->get('source')->getData())) {
                    $source = $form->get('source')->getData();
                    $prospect->setMyastroSource(strtolower($source->getLabel()));
                }
                if (!empty($form->get('formurl')) && !empty($form->get('formurl')->getData())) {
                    $formurl = $form->get('formurl')->getData();
                    $prospect->setMyastroUrl(strtolower($formurl->getLabel()));
                }
                if (!empty($form->get('codepromo')) && !empty($form->get('codepromo')->getData())) {
                    $codepromo = $form->get('codepromo')->getData();
                    $prospect->setMyastroPromoCode(strtolower($codepromo->getCode()));
                }


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
