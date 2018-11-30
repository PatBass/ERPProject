<?php

namespace KGC\ClientBundle\Elastic\Listener;

use FOS\ElasticaBundle\Doctrine\Listener as BaseListener;
use KGC\ClientBundle\Elastic\Event\ClientEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ClientRefreshListener.
 */
class ClientRefreshListener extends BaseListener implements EventSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param ClientEvent $event
     */
    public function refresh($event)
    {
        if ($event instanceof ClientEvent) {
            $client = $event->getClient();
            $this->scheduledForUpdate[] = $client;
            if ($client) {
                $this->objectPersister->replaceOne($client);
            }
        }
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ClientEvent::REFRESH => 'refresh',
        ];
    }
}
