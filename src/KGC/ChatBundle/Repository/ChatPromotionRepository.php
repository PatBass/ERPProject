<?php

namespace KGC\ChatBundle\Repository;

use Doctrine\ORM\EntityRepository;
use KGC\Bundle\SharedBundle\Entity\Website;
use KGC\ChatBundle\Entity\ChatFormula;
use KGC\ChatBundle\Entity\ChatFormulaRate;
use KGC\ChatBundle\Entity\ChatPromotion;

class ChatPromotionRepository extends EntityRepository
{
    /**
     * @param Website $website
     */
    public function findChatPromotionsByWebsite(Website $website = null)
    {
        $qb = $this->createQueryBuilder('cp');
        if ($website) {
            $qb->andWhere('cp.website = :website')->setParameter('website', $website);
        }
        return $qb
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Website $website
     * @param string $promotionCode
     *
     * @return ChatPromotion
     */
    public function findOneByWebsiteAndPromotionCode(Website $website, $promotionCode)
    {
        return $this->createQueryBuilder('cp')
            ->where('cp.website = :website')->setParameter('website', $website)
            ->andWhere('cp.promotionCode = :promotionCode')->setParameter('promotionCode', $promotionCode)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param ChatPromotion $promotion
     *
     * @return bool
     */
    public function isChatPromotionWithSameCode(ChatPromotion $promotion)
    {
        $qb = $this->createQueryBuilder('cp')
            ->select('COUNT(cp.id)')
            ->where('cp.website = :website')->setParameter('website', $promotion->getWebsite())
            ->andWhere('cp.promotionCode = :code')->setParameter('code', $promotion->getPromotionCode());
        if ($promotion->getId()) {
            $qb->andWhere('cp.id <> :id')->setParameter('id', $promotion->getId());
        }

        return $qb->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function isChatPromotionWithSameName(ChatPromotion $promotion)
    {
        $qb = $this->createQueryBuilder('cp')
            ->select('COUNT(cp.id)')
            ->where('cp.website = :website')->setParameter('website', $promotion->getWebsite())
            ->andWhere('cp.name = :name')->setParameter('name', $promotion->getName());
        if ($promotion->getId()) {
            $qb->andWhere('cp.id <> :id')->setParameter('id', $promotion->getId());
        }

        return $qb->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /**
     * @param ChatFormula $formula
     *
     * @return ChatFormula
     */
    public function hasPromotionCompatibleWithChatFormula(ChatFormula $formula)
    {
        $formulaFilter = 0;
        $currentDt = new \DateTime;

        foreach ($formula->getChatFormulaRates() as $formulaRate) {
            switch ($formulaRate->getType()) {
                case ChatFormulaRate::TYPE_FREE_OFFER:
                    $formulaFilter |= ChatPromotion::FORMULA_FILTER_NONE;
                    //$allowFlexible = 1;
                    break;
                case ChatFormulaRate::TYPE_DISCOVERY:
                    $formulaFilter |= ChatPromotion::FORMULA_FILTER_DISCOVERY;
                    break;
                case ChatFormulaRate::TYPE_STANDARD:
                    $formulaFilter |= ChatPromotion::FORMULA_FILTER_STANDARD;
                    break;
                case ChatFormulaRate::TYPE_PREMIUM:
                    $formulaFilter |= ChatPromotion::FORMULA_FILTER_PREMIUM;
                    break;
            }
        }

        return $this->createQueryBuilder('cp')
            ->select('COUNT(cp.id)')
            ->where('cp.website = :website')->setParameter('website', $formula->getWebsite())
            ->andWhere('cp.enabled = 1')
            ->andWhere('cp.startDate IS NULL OR cp.startDate <= DATE(:now)')->setParameter('now', $currentDt)
            ->andWhere('cp.endDate IS NULL OR cp.endDate >= DATE(:now)')
            ->andWhere('BIT_AND(cp.formulaFilter, :formulaFilter) > 0')->setParameter('formulaFilter', $formulaFilter)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
