<?php

namespace KGC\UserBundle\Service;

use Doctrine\Common\Persistence\ObjectManager;
use JMS\DiExtraBundle\Annotation as DI;
use KGC\UserBundle\Entity\Journal;
use KGC\UserBundle\Entity\Utilisateur;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

/**
 * @DI\Service("kgc.user.manager")
 */
class UserManager
{
    /**
     * @var ObjectManager
     */
    protected $entityManager;
    /**
     * @var EncoderFactoryInterface
     */
    protected $encoderFactory;

    /**
     * @param int $saltLength
     *
     * @return string
     */
    protected function generateSalt($saltLength = 15)
    {
        return substr(sha1(uniqid(mt_rand(), true)), 0, $saltLength);
    }
    /**
     * @param EncoderFactoryInterface $encoderFactory
     *
     * @DI\InjectParams({
     *     "encoderFactory" = @DI\Inject("security.encoder_factory"),
     *     "entityManager"  = @DI\Inject("doctrine.orm.entity_manager")
     * })
     */
    public function __construct(EncoderFactoryInterface $encoderFactory, ObjectManager $entityManager)
    {
        $this->encoderFactory = $encoderFactory;
        $this->entityManager = $entityManager;
    }

    /**
     * @param Utilisateur $user
     */
    public function updateUser(Utilisateur $user, $flush = true)
    {
        if (null === $user->getPlainText()) {
            $user->setPlainText($user->getPassword());
        }
        $user->setSalt($this->generateSalt($saltLength = 15));
        $user->encodePassword($this->encoderFactory->getEncoder($user));

        if ($flush) {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }

    /**
     * @param Utilisateur $user
     */
    public function removeUser(Utilisateur $user)
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    /**
     * @param Utilisateur $user
     * @param Journal     $log
     */
    public function addLog(Utilisateur $user, Journal $log)
    {
        $user->addJournal($log);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
