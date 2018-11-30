<?php

// src/KGC/RdvBundle/Service/SuiviRdvManager.php


namespace KGC\RdvBundle\Service;

use JMS\DiExtraBundle\Annotation as DI;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Symfony\Component\Security\Core\SecurityContext;
use KGC\RdvBundle\Entity\RDV;
use KGC\RdvBundle\Entity\SuiviRdv;
use KGC\RdvBundle\Entity\ActionSuivi;

/**
 * @DI\Service("kgc.suivirdv.manager")
 * @DI\Tag("doctrine.event_listener", attributes = { "event"="preFlush", "lazy"=true})
 */
class SuiviRdvManager
{
    /**
     * @var ObjectManager
     */
    protected $entityManager;

    /**
     * @var SecurityContext
     */
    protected $securityContext;

    /**
     * @var KGC\RdvBundle\Entity\SuiviRdv
     */
    protected $suiviRdv;

    /**
     * @param ObjectManager $entityManager
     *
     * @DI\InjectParams({
     *     "entityManager"   = @DI\Inject("doctrine.orm.entity_manager"),
     *     "securityContext" = @DI\Inject("security.context")
     * })
     */
    public function __construct(ObjectManager $entityManager, SecurityContext $securityContext)
    {
        $this->entityManager = $entityManager;
        $this->securityContext = $securityContext;
    }

    /**
     * init new suiviRdv object.
     */
    protected function init()
    {
        $this->suiviRdv = new SuiviRdv();
        $this->suiviRdv->setUtilisateur($this->securityContext->getToken()->getUser());
    }

    /**
     * create SuiviRdv from rdv object.
     *
     * @param RDV $rdv
     */
    public function create(RDV $rdv)
    {
        if (!($this->suiviRdv instanceof SuiviRdv)) {
            $this->init();
        }
        $this->fillRdvInfos($rdv);
    }

    /**
     * fill suiviRdv with rdv infos.
     *
     * @param RDV $rdv
     */
    public function fillRdvInfos(RDV $rdv)
    {
        if ($this->suiviRdv instanceof SuiviRdv) {
            $this->suiviRdv
                ->setRdv($rdv)
                ->setEtat($rdv->getEtat())
                ->setClassement($rdv->getClassement());
        }
    }

    /**
     * set main action.
     *
     * @param ActionSuivi|null $action
     */
    public function setMainAction($action)
    {
        if ($action instanceof ActionSuivi) {
            $this->suiviRdv->setMainaction($action);
        }
    }

    /**
     * add actions.
     *
     * @param ActionSuivi $action
     */
    public function addAction($action)
    {
        if ($action !== null) {
            if (!$action instanceof ActionSuivi) {
                $action = $this->entityManager->getRepository('KGCRdvBundle:ActionSuivi')->findOneByIdcode($action);
            }
            if ($action !== $this->suiviRdv->getMainaction()) {
                $test = function ($key, $element) use ($action) {
                    return ($element === $action);
                };
                if (!$this->suiviRdv->getActions()->exists($test)) {
                    $this->suiviRdv->addActions($action);
                }
            }
        }
    }

    /**
     * set donneeliee.
     *
     * @param string $donnee
     */
    public function setDonneeLiee($donnee)
    {
        $this->suiviRdv->setDonneeLiee($donnee);
    }

    /**
     * set commentaire.
     *
     * @param string $commentaire
     */
    public function setCommentaire($commentaire)
    {
        $this->suiviRdv->setCommentaire($commentaire);
    }

    public function preFlush(PreFlushEventArgs $args)
    {
        if ($this->suiviRdv instanceof SuiviRdv) {
            if (!$this->suiviRdv->isEmpty()) {
                $this->entityManager->persist($this->suiviRdv);
            }
        }
    }
}
