<?php

namespace KGC\StatBundle\Ruler;

use Doctrine\ORM\EntityManagerInterface;
use JMS\DiExtraBundle\Annotation as DI;
use KGC\StatBundle\Entity\PhonisteParameter;
use KGC\StatBundle\Entity\BonusParameter;
use KGC\UserBundle\Entity\Utilisateur;

/**
 * Class PhonisteRuler.
 *
 * @DI\Service("kgc.stat.ruler.phoning")
 */
class PhonisteRuler
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var PhonisteParameter
     */
    protected $phonisteParams;

    /**
     * Return Phoning params from database.
     *
     * @return PhonisteParameter
     */
    public function getPhonisteParams()
    {
        if (null === $this->phonisteParams) {
            $this->phonisteParams = $this->em
                ->getRepository('KGCStatBundle:PhonisteParameter')
                ->findLastParamters()
            ;
        }

        return $this->phonisteParams;
    }

    /**
     * @param EntityManagerInterface $em
     *
     * @DI\InjectParams({
     *      "em" = @DI\Inject("doctrine.orm.entity_manager"),
     * })
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param PhonisteParameter $params
     * @param $done
     *
     * @return array|int
     */
    public function getCurrentBonus(PhonisteParameter $params = null, $done)
    {
        $params = $params ?: $this->getPhonisteParams();

        switch (true) {
            case $done >= $params->getFourthThreshold():
                return [$params->getFourthThreshold(), $params->getBonusFourthThreshold()];
            case $done >= $params->getThirdThreshold():
                return [$params->getThirdThreshold(), $params->getBonusThirdThreshold()];
            case $done >= $params->getSecondThreshold():
                return [$params->getSecondThreshold(), $params->getBonusSecondThreshold()];
            case $done >= $params->getFirstThreshold():
                return [$params->getFirstThreshold(), $params->getBonusFirstThreshold()];
            default:
                return [0, 0];
        }
    }
    
    public function getBonusParameters($bonusCode, \DateTime $begin = null, \DateTime $end = null, Utilisateur $user = null)
    {
        $repo = $this->em->getRepository('KGCStatBundle:BonusParameter');
        
        return $repo->getBonus($bonusCode, $begin, $end, $user);
    }
}
