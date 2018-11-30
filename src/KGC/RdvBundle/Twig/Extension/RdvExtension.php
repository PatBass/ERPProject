<?php

namespace KGC\RdvBundle\Twig\Extension;

use JMS\DiExtraBundle\Annotation as DI;
use Doctrine\ORM\EntityManager;
use KGC\Rdv\Entity\TPE;
use KGC\RdvBundle\Service\PlanningService;

/**
 * @DI\Service("kgc.rdv.twig.extension")
 *
 * @DI\Tag("twig.extension")
 */
class RdvExtension extends \Twig_Extension
{
    const EMPTY_VALUE_TEXT = ' - ';

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param EntityManager $entityManager
     *
     * @DI\InjectParams({
     *     "entityManager"  = @DI\Inject("doctrine.orm.entity_manager")
     * })
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return array(
            'payment_gateway_to_label' => new \Twig_Filter_Method($this, 'paymentGatewayToLabel'),
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'rdv';
    }

    public function paymentGatewayToLabel($name)
    {
        static $labels = null;

        if ($labels === null) {
            $labels = $this->entityManager->getRepository('KGCRdvBundle:TPE')->getLabelsByGateway();
        }

        return isset($labels[$name]) ? $labels[$name] : 'Unknown ('.$name.')';
    }
}
