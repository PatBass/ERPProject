<?php

namespace KGC\UserBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use JMS\DiExtraBundle\Annotation as DI;
use KGC\UserBundle\Entity\SalaryParameter;

/**
 * @DI\Service("kgc.salary.manager")
 */
class SalaryManager
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
     * Return the percentage associated with given params.
     *
     * @param $nature
     * @param $type
     * @param $value
     * @param null $mean
     *
     * @return int
     *
     * @throws \Exception
     */
    public function getPercentage($nature, $type, $value, $mean = null)
    {
        SalaryParameter::checkNature($nature);
        SalaryParameter::checkType($type);

        $params = $this->entityManager
            ->getRepository('KGCUserBundle:SalaryParameter')
            ->findBy([
                'nature' => $nature,
                'type' => $type,
            ]);

        foreach ($params as $p) {
            if ($value >= $p->getCaMin() && $value < $p->getCaMax()) {
                if (null !== $p->getValueMin() && null !== $p->getValueMax()) {
                    if ($mean >= $p->getValueMin() && $mean < $p->getValueMax()) {
                        return $p->getPercentage();
                    }
                } else {
                    return $p->getPercentage();
                }
            }
        }

        return 0;
    }
}
