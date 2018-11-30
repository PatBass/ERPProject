<?php

// src/KGC/RdvBundle/Repository/SupportRepository.php


namespace KGC\RdvBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Support Repository.
 *
 * @category EntityRepository
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class SupportRepository extends EntityRepository
{
    /**
     * @param bool      $active
     * @param \DateTime $activedate
     *
     * @return QuerBuilder
     */
    public function findAllQB($active = null, $activedate = null)
    {
        $qb = $this->createQueryBuilder('s')
            ->addOrderBy('s.libelle', 'ASC')
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
     * @param array $ids
     * @param bool $active
     * @param \DateTime $activedate
     * @return QuerBuilder
     */
    public function findByIdsQB($ids, $active = null, $activedate = null)
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where($qb->expr()->in('s.id', $ids))
            ->addOrderBy('s.libelle', 'ASC')
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
     * @param bool      $active
     * @param \DateTime $activedate
     *
     * @return array
     */
    public function findAll($active = null, $activedate = null)
    {
        $qb = $this->findAllQB($active, $activedate);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array     $profils
     * @param bool      $active
     * @param \DateTime $activedate
     *
     * @return array
     */
    public function findByProfilesQB($profils = array(), $active = null, $activedate = null)
    {
        $qb = $this->findAllQB($active, $activedate);
        $idprofils = array();
        foreach ($profils as $profil) {
            $idprofils[] = $profil->getId();
        }
        $qb->innerJoin('s.profils', 'p')
           ->andWhere('p.id IN(:profils)')
           ->setParameter('profils', $idprofils);

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @param \DateTime    $active_date
     *
     * @return QueryBuilder
     */
    private function addActiveCriteria($qb, \DateTime $active_date)
    {
        $qb->andWhere('s.enabled = 1 OR (s.enabled = 0 AND s.disabled_date > :date_rdv)')
           ->setParameter('date_rdv', $active_date);

        return $qb;
    }

    /**
     * Essaie de trouver un support d'après son libelle avec une certaine marge d'erreur sur l'orthographe.
     *
     * @param $libelle
     *
     * @return bool
     */
    public function getApproxi($libelle)
    {
        $supp_list = $this->findAll();
        $find = false;
        $i = 0;
        while ($i < count($supp_list) and !$find) {
            $similarity_pct = 0;
            $lib_test = strtoupper($supp_list[$i]->getLibelle());
            similar_text(strtoupper($libelle), $lib_test, $similarity_pct);
            if (number_format($similarity_pct) > 90) {
                $find = $supp_list[$i];
            }
            ++$i;
        }

        return $find;
    }
}
