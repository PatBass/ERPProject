<?php

namespace KGC\ChatBundle\Security;

use JMS\DiExtraBundle\Annotation as DI;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use KGC\Bundle\SharedBundle\Entity\Client;

// Because more than one client can have the same username (loging from 2 or 3 websites), we have to restrict the connection to origin too
// This provider should be link only to chat api
// Client have too authenticate with "$username-$origin"
// The "-" is the delimiter used, defined in the entity Client

/**
 * @DI\Service("kgc.chat.client.provider")
 */
class ClientProvider implements UserProviderInterface
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @param EntityManager $em
     *
     * @DI\InjectParams({
     *     "em" = @DI\Inject("doctrine.orm.entity_manager")
     * })
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    protected function findUser($usernameOrigin)
    {
        $credentials = explode(Client::USERNAME_ORIGIN_DELIMITER, $usernameOrigin);
        $user = null;
        if (is_array($credentials)) {
            if (count($credentials) >= 2) {
                // If username contains also the delimiter, implode the credentials after extract the origin
                $origin = array_pop($credentials);
                $username = implode(Client::USERNAME_ORIGIN_DELIMITER, $credentials);

                $user = $this->em->getRepository('KGCSharedBundle:Client')->findOneBy(array(
                    'username' => $username,
                    'origin' => $origin,
                ));
            }
        }

        return $user;
    }

    public function loadUserByUsernameAndOrigin($username, $origin)
    {
        return $this->loadUserByUsername($username.Client::USERNAME_ORIGIN_DELIMITER.$origin);
    }

    public function loadUserByUsername($usernameOrigin)
    {
        $user = $this->findUser($usernameOrigin);

        if (!$user) {
            throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $usernameOrigin));
        }

        return $user;
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof Client) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        return $this->loadUserByUsername($user->getUsernameOrigin());
    }

    public function supportsClass($class)
    {
        return $class === 'KGC\Bundle\SharedBundle\Entity\Client';
    }
}
