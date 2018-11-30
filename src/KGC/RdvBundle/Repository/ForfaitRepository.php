<?php

// src/KGC/RdvBundle/Repository/ForfaitRepository.php


namespace KGC\RdvBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Forfait Repository.
 *
 * @category Repository
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class ForfaitRepository extends EntityRepository
{
    /**
     * donne la liste des forfaits valides du client.
     *
     * @param $client
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function findAvailableByClientQB($client)
    {
        $qb = $this->findByClientQB($client);
        $qb->andWhere('f.epuise = 0');

        return $qb;
    }

    public function findAvailableByClient($client)
    {
        return $this->findAvailableByClientQB($client)->getQuery()->getResult();
    }

    public function findByClientQB($client)
    {
        $qb = $this->createQueryBuilder('f')
                   ->where('f.client = :client')
                   ->setParameter('client', $client);

        return $qb;
    }
}
