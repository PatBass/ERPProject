<?php

namespace KGC\RdvBundle\Elastic\Listener;

use Doctrine\ORM\EntityManagerInterface;
use FOS\ElasticaBundle\Event\TypePopulateEvent;
use JMS\DiExtraBundle\Annotation as DI;
use KGC\RdvBundle\Listener\CarteBancaireListener;

/**
 * Class DeactivateListener.
 *
 * @DI\Service("kgc.elastic.rdv.deactivate.listener")
 */
class DeactivateListener
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     *
     * @DI\InjectParams({
     *      "entityManager" = @DI\Inject("doctrine.orm.entity_manager"),
     * })
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param TypePopulateEvent $event
     */
    protected function removeEventListener($event)
    {
        $listenerInst = null;
        $listeners = $this->entityManager->getEventManager()->getListeners();
        $evm = $this->entityManager->getEventManager();
        foreach ($listeners as $event => $listeners) {
            foreach ($listeners as $hash => $listener) {
                if ($listener instanceof CarteBancaireListener) {
                    $evm->removeEventListener(['postLoad'], $listener);
                    break 2;
                }
            }
        }
    }

    /**
     * @DI\Observe(TypePopulateEvent::PRE_TYPE_POPULATE)
     */
    public function deactivateCBPostLoadPreType(TypePopulateEvent $event)
    {
        $this->removeEventListener($event);
    }
}
