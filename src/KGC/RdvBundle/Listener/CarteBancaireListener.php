<?php

namespace KGC\RdvBundle\Listener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use KGC\RdvBundle\Entity\CarteBancaire;
use KGC\RdvBundle\Service\CarteBancaireManager;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * @DI\Service("kgc.rdv.carte_bancaire.listener")
 *
 * @DI\Tag("doctrine.event_subscriber", attributes = {"event" = "postLoad"})
 * @DI\Tag("doctrine.event_subscriber", attributes = {"event" = "prePersist"})
 * @DI\Tag("doctrine.event_subscriber", attributes = {"event" = "preUpdate"})
 */
class CarteBancaireListener implements EventSubscriber
{
    /**
     * @var CarteBancaireManager
     */
    protected $carteBancaireManager;

    /**
     * @param CarteBancaireManager $carteBancaireManager
     *
     * @DI\InjectParams({
     *      "carteBancaireManager" = @DI\Inject("kgc.rdv.carte_bancaire.manager")
     * })
     */
    public function __construct(CarteBancaireManager $carteBancaireManager)
    {
        $this->carteBancaireManager = $carteBancaireManager;
    }

    /**
     *  If inserting or updating a CB, encrypt the needed fields.
     *
     * @param LifecycleEventArgs $args
     *
     * @return CarteBancaire|void
     */
    protected function beforeSave(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if (!$entity instanceof CarteBancaire) {
            return;
        }

        return $this->carteBancaireManager->encrypt($entity);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postLoad(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof CarteBancaire) {
            if (strlen($entity->getExpiration()) > 10) {
                $this->carteBancaireManager->decrypt($entity);
            }
        }
    }

    /**
     * @param LifecycleEventArgs $args
     *
     * @return CarteBancaire|void
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        return $this->beforeSave($args);
    }

    /**
     * @param LifecycleEventArgs $args
     *
     * @return CarteBancaire|void
     */
    public function preUpdate(LifecycleEventArgs $args)
    {
        return $this->beforeSave($args);
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return ['postLoad', 'preUpdate', 'prePersist'];
    }
}
