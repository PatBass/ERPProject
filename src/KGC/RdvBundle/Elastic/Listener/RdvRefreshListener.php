<?php

namespace KGC\RdvBundle\Elastic\Listener;

use FOS\ElasticaBundle\Doctrine\Listener as BaseListener;
use KGC\RdvBundle\Elastic\Event\RdvEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class RdvRefreshListener.
 */
class RdvRefreshListener extends BaseListener implements EventSubscriberInterface
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
     * @param RdvEvent $event
     */
    public function refresh($event)
    {
        if ($event instanceof RdvEvent) {
            $rdv = $event->getRdv();
            $this->scheduledForUpdate[] = $rdv;
            if ($rdv) {
                $this->objectPersister->replaceOne($rdv);
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
            RdvEvent::REFRESH => 'refresh',
        ];
    }
}
