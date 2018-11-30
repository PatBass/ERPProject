<?php

namespace KGC\ChatBundle\Repository;

use Doctrine\ORM\EntityRepository;
use KGC\ChatBundle\Entity\ChatFormulaRate;
use KGC\Bundle\SharedBundle\Entity\Website;

class ChatFormulaRateRepository extends EntityRepository
{
    /**
     * Find a formula rate but restrict the result to website
     * Also check that this formula is not desactivated.
     *
     * @param int $website_id
     * @param int $formula_rate_id
     */
    public function findByWebsiteAndId($website_id, $formula_rate_id)
    {
        $qb = $this->createQueryBuilder('fr')
                   ->join('fr.chatFormula', 'f')
                   ->join('f.website', 'w', 'WITH', 'w.id = :website_id')
                   ->where('fr.id = :formula_rate_id')
                   ->setParameter('website_id', $website_id)
                   ->setParameter('formula_rate_id', $formula_rate_id)
                   ->setMaxResults(1)
                   ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Find a formula rate by website and type.
     *
     * @param Website $website
     * @param int $type
     * @param bool $isFlexible
     *
     * @return ChatFormulaRate
     */
    public function findOneByWebsiteAndType(Website $website, $type, $isFlexible = false)
    {
        $qb = $this->createQueryBuilder('fr')
            ->select('fr', 'f', 't')
            ->join('fr.chatFormula', 'f')
            ->join('f.chatType', 't')
            ->where('f.website = :website')
            ->andWhere('fr.type = :type')
            ->andWhere('fr.flexible = :flexible')
            ->setParameter('website', $website)
            ->setParameter('type', $type)
            ->setParameter('flexible', $isFlexible)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Find a formula rate by website ref and type.
     *
     * @param string $website
     * @param int $type
     * @param bool $isFlexible
     *
     * @return ChatFormulaRate
     */
    public function findOneByWebsiteRefAndType($websiteRef, $type, $isFlexible = false)
    {
        $qb = $this->createQueryBuilder('fr')
            ->select('fr', 'f', 't')
            ->join('fr.chatFormula', 'f')
            ->join('f.chatType', 't')
            ->join('f.website', 'w')
            ->where('w.reference = :reference')
            ->andWhere('fr.type = :type')
            ->andWhere('fr.flexible = :flexible')
            ->setParameter('reference', $websiteRef)
            ->setParameter('type', $type)
            ->setParameter('flexible', $isFlexible)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getEnabledChatFormulaRatesQueryBuilder()
    {
        return $this->createQueryBuilder('fr')
            ->join('fr.chatFormula', 'f')
            ->join('f.website', 'w')
            ->where('w.enabled = 1');
    }

    /**
     * @return array
     */
    public function getEnabledChatFormulaRatesForSearch()
    {
        $rows = $this->getEnabledChatFormulaRatesQueryBuilder()
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($rows as $formulaRate) {
            $result[$formulaRate->getChatFormula()->getWebsite()->getLibelle()]['#'.$formulaRate->getId().'#'] = $formulaRate->getLibelleRecherche();
        }

        return $result;
    }

    /**
     * @param Website $website
     *
     * @return array
     */
    public function findEditableChatFormulaRatesByWebsite(Website $website = null)
    {
        $qb = $this->getEnabledChatFormulaRatesQueryBuilder();
        if ($website) {
            $qb->andWhere('w = :website')->setParameter('website', $website);
        }
        return $qb
            ->select('fr')
            ->addSelect('CASE WHEN fr.type = :type THEN 1 ELSE 0 END AS HIDDEN is_free_offer')->setParameter('type', ChatFormulaRate::TYPE_FREE_OFFER)
            ->andWhere('fr.flexible = :flexible')->setParameter('flexible', false)
            ->addOrderBy('w.libelle')
            ->addOrderBy('is_free_offer', 'DESC')
            ->addOrderBy('fr.type')
            ->addOrderBy('fr.price')
            ->getQuery()
            ->getResult();
    }
}
