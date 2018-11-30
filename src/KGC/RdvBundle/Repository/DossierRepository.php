<?php

// src/KGC/RdvBundle/Repository/DossierRepository.php


namespace KGC\RdvBundle\Repository;

use Doctrine\ORM\EntityRepository;
use KGC\RdvBundle\Entity\Classement;

/**
 * Dossier Repository.
 *
 * @category EntityRepository
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class DossierRepository extends EntityRepository
{
    /**
     * donne la liste des dossiers attribuables selon le tiroir.
     *
     * @param Classement $classement
     * @param bool|null  $actif
     * @param date|null  $actif_date
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getDossiersSwitchTiroirQB(Classement $classement, $actif = null, $actif_date = null)
    {
        $qb = $this->createQueryBuilder('d')
                   ->where('d.tiroir = :tiroir')
                   ->setParameter('tiroir', $classement->getTiroir())
                   ->orderBy('d.libelle', 'ASC');
        if ($actif === true) {
            if ($actif_date === null) {
                $actif_date = new \DateTime();
            }
            $qb = $this->addActiveCriteria($qb, $actif_date);
        }

        return $qb;
    }
    /**
     * donne la liste des dossiers corespondant à des motifs d'annulation.
     *
     * @param bool|null $actif
     * @param date|null $actif_date
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getDossiersMotifAnnulationQB($actif = null, $actif_date = null)
    {
        $qb = $this->createQueryBuilder('d')
                   ->where('d.motifAnnulation = :motif')
                   ->setParameter('motif', true)
                   ->orderBy('d.libelle', 'ASC');
        if ($actif === true) {
            if ($actif_date === null) {
                $actif_date = new \DateTime();
            }
            $qb = $this->addActiveCriteria($qb, $actif_date);
        }

        return $qb;
    }

    /**
     * donne la liste des dossiers attribuables selon le tiroir.
     *
     * @param string    $tiroir_code
     * @param bool|null $actif
     * @param date|null $actif_date
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getDossiersSwitchTiroirCode($tiroir_code, $actif = null, $actif_date = null)
    {
        $qb = $this->createQueryBuilder('d')
                   ->innerJoin('d.tiroir', 't')
                   ->where('t.idcode = :tiroir_code')
                   ->setParameter('tiroir_code', $tiroir_code)
                   ->orderBy('d.libelle', 'ASC');
        if ($actif === true) {
            if ($actif_date === null) {
                $actif_date = new \DateTime();
            }
            $qb = $this->addActiveCriteria($qb, $actif_date);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param QueryBuilder $qb
     * @param \DateTime    $active_date
     *
     * @return QueryBuilder
     */
    private function addActiveCriteria($qb, \DateTime $active_date)
    {
        $qb->andWhere('d.disabled_date IS NULL OR d.disabled_date > :date_rdv')
           ->setParameter('date_rdv', $active_date);

        return $qb;
    }
}
