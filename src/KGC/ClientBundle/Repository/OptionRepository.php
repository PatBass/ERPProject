<?php

namespace KGC\ClientBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Class OptionRepository.
 */
class OptionRepository extends EntityRepository
{
    /**
     * @param $type
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function findAllByTypeQB($type, $active = null, $activedate = null)
    {
        $qb = $this->createQueryBuilder('o')
                ->andWhere('o.type = :type')
                ->setParameter('type', $type)
                ->orderBy('o.label')
        ;
        if ($active === true) {
            if ($activedate === null) {
                $activedate = new \DateTime();
            }
            $qb = $this->addActiveCriteria($qb, $activedate);
        }

        return $qb;
    }

    /**
     * @param $type
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function findAllByType($type, $active = null, $activedate = null)
    {
        return $this->findAllByTypeQB($type, $active, $activedate)->getQuery()->getResult();
    }

    public function findForfaitTarificationQB()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select(['ft', 'f', 't'])
            ->from('KGCRdvBundle:ForfaitTarification', 'ft', null)
            ->innerJoin('ft.codeTarification', 't')
            ->innerJoin('ft.forfait', 'f')
            ->orderBy('f.label', 'ASC')
        ;

        return $qb;
    }

    public function findForfaitTarification()
    {
        $qb = $this->findForfaitTarificationQB();

        return $qb->getQuery()->getResult();
    }

    protected function addActiveCriteria($qb, \DateTime $date)
    {
        $qb->andwhere('o.enabled = 1 OR (o.enabled = 0 AND o.disabled_date > :date_rdv)')
            ->setParameter('date_rdv', $date);

        return $qb;
    }
}
