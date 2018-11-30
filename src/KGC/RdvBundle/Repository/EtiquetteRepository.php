<?php

// src/KGC/RdvBundle/Repository/EtiquetteRepository.php


namespace KGC\RdvBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Etiquette Repository.
 *
 * @category EntityRepository
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class EtiquetteRepository extends EntityRepository
{
    public function getEtiquettesSwitchProfils($profils)
    {
        $idprofils = array();
        foreach ($profils as $profil) {
            $idprofils[] = $profil->getId();
        }
        $qb = $this->createQueryBuilder('e')
                   ->innerJoin('e.profils', 'p')
                   ->where('p.id IN(:profils)')
                   ->setParameter('profils', $idprofils)
                   ->orderBy('e.libelle');

        return $qb;
    }

    public function getEtiquettesChoicesSwitchProfils($profils)
    {
        $qb = $this->getEtiquettesSwitchProfils($profils);
        $qb->select('e.id, e.libelle');
        $result = $qb->getQuery()->getResult();

        $choices = array();
        foreach ($result as $eti) {
            $choices[$eti['id']] = $eti['libelle'];
        }

        return $choices;
    }
}
