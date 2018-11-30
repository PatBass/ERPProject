<?php

namespace KGC\CommonBundle\Listener;

use Gedmo\Loggable\LoggableListener as BaseListener;
use Gedmo\Loggable\Mapping\Event\LoggableAdapter;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class LoggableListener extends BaseListener
{
    /**
     * @var TokenStorageInterface
     */
    var $tokenStorage;

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @inheritdoc
     */
    protected function createLogEntry($action, $object, LoggableAdapter $ea)
    {
        if (empty($this->username)) {
            if ($token = $this->tokenStorage->getToken()) {
                if (($user = $token->getUser()) && method_exists($user, 'getUsername')) {
                    $this->setUsername($user->getUsername());
                }
            }
        }

        return parent::createLogEntry($action, $object, $ea);
    }
}