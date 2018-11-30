<?php

namespace KGC\RdvBundle\Elastic\Listener;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use FOS\ElasticaBundle\Doctrine\Listener as BaseListener;
use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\ClientBundle\Entity\Historique;
use KGC\RdvBundle\Entity\CarteBancaire;
use KGC\RdvBundle\Entity\RDV;
use KGC\RdvBundle\Entity\Tarification;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class NestedListener.
 */
class NestedListener extends BaseListener implements EventSubscriber
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

    protected function manageNested(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        if ($entity instanceof RDV) {
            $this->objectPersister->replaceOne($entity);
        }

        if ($entity instanceof Tarification) {
            $this->scheduledForUpdate[] = $entity;
            $rdv = $entity->getRdv();
            if ($rdv) {
                $this->objectPersister->replaceOne($rdv);
            }
        }
        if ($entity instanceof CarteBancaire) {
            $this->scheduledForUpdate[] = $entity;
            foreach ($entity->getRdvs() as $rdv) {
                if (null !== $rdv->getId()) {
                    $this->objectPersister->replaceOne($rdv);
                }
            }
        }
        if ($entity instanceof Historique) {
            $this->scheduledForUpdate[] = $entity;
            $rdv = $entity->getRdv();
            if ($rdv) {
                $this->objectPersister->replaceOne($rdv);
            }
        }
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $this->manageNested($args);
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $this->manageNested($args);
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->manageNested($args);
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            'postUpdate', 'postPersist', 'preRemove',
        ];
    }
}
