<?php

namespace KGC\ClientBundle\Handler;

use KGC\ClientBundle\Entity\Contact;
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
 * ListContactHandler.
 *
 *
 * @category Form/Handler
 *
 * @author Laurene Dourdin <2aurene@gmail.com>
 *
 * @DI\Service("kgc.listcontact.formhandler")
 */
class ListContactHandler
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
     * @var KGC\RdvBundle\Service\Encryption
     */
    protected $encryptionService;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param EntityManager $entityManager
     * @param TokenStorageInterface $security
     * @param Encryption $encryptionService
     *
     * @DI\InjectParams({
     *     "eventDispatcher" = @DI\Inject("event_dispatcher"),
     *     "entityManager"   = @DI\Inject("doctrine.orm.entity_manager"),
     *     "security"        = @DI\Inject("security.token_storage"),
     *   "encryptionService" = @DI\Inject("kgc.rdv.encryption.service")
     * })
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        EntityManager $entityManager,
        TokenStorageInterface $security,
        Encryption $encryptionService
    )
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->entityManager = $entityManager;
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
        $list = $form->getData();
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->entityManager->persist($list);
                foreach($request->files as $file) {
                    if (($handle = fopen($file['submitFile']->getRealPath(), "r")) !== FALSE) {
                        while(($row = fgetcsv($handle)) !== FALSE) {
                            $explode = explode(';', $row[0]);
                            $phone = utf8_encode($explode[2]);
                            $firstname = utf8_encode($explode[0]);
                            $lastname = utf8_encode($explode[1]);
                            $contacts = $this->entityManager->getRepository('KGCClientBundle:Contact')->findBy(array('list' => $list,'phone' => $phone, 'firstname' => $firstname, 'lastname' => $lastname));
                            if(count($contacts) == 0) {
                                $contact = new Contact();
                                $contact->setFirstname($firstname);
                                $contact->setLastname($lastname);
                                $contact->setPhone($phone);
                                $contact->setList($list);
                                $this->entityManager->persist($contact);
                            }
                        }
                    }
                }
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
