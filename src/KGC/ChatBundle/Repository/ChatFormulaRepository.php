<?php

namespace KGC\ChatBundle\Repository;

use Doctrine\ORM\EntityRepository;

class ChatFormulaRepository extends EntityRepository
{
    /**
     * Find formulas and chat_type and formula rates associated.
     *
     * @param [OPTIONNAL] integer $website_id   If specified, restrict the result to this website id
     * @param [OPTIONNAL] boolean $even_expired If true, get also expired formulas
     * @param [OPTIONNAL] boolean $isFlexible
     *
     * @return array
     */
    public function findByWebsite($website_id = null, $even_expired = false, $isFlexible = false)
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
                   ->select('formula', 'formula_rate', 'chat_type')
                   ->from('KGCChatBundle:ChatFormula', 'formula')
                   ->join('formula.chatFormulaRates', 'formula_rate')
                   ->join('formula.chatType', 'chat_type')
                   ->where('formula_rate.flexible = :flexible')
                   ->setParameter('flexible', $isFlexible);

        if (is_numeric($website_id)) {
            $qb->join('formula.website', 'website', 'WITH', 'website.id = :website_id')
               ->setParameter('website_id', $website_id);
        }

        if (!$even_expired) {
            $qb->andWhere('formula.desactivationDate IS NULL OR formula.desactivationDate > :today')
               ->andWhere('formula_rate.desactivationDate IS NULL OR formula_rate.desactivationDate > :today')
               ->setParameter('today', new \DateTime())
               ;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param bool $onlyActive
     *
     * @return array
     */
    public function getChatTypesByWebsite($onlyActive = true)
    {
        $qb = $this->createQueryBuilder('f')
            ->select('w.id', 't.type')
            ->join('f.chatType', 't')
            ->join('f.website', 'w');
        if ($onlyActive) {
            $qb->andWhere('w.enabled = 1');
        }
        $result = $qb->getQuery()->getResult();

        $types = [];
        foreach ($result as $row) {
            $types[$row['id']] = $row['type'];
        }

        return $types;
    }
}
