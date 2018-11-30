<?php

// src/KGC/RdvBundle/Repository/CarteBancaireRepository.php


namespace KGC\RdvBundle\Repository;

use Doctrine\ORM\EntityRepository;
use KGC\Bundle\SharedBundle\Entity\Client;

/**
 * CarteBancaireRepository.
 *
 * @category Entityrepository
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class CarteBancaireRepository extends EntityRepository
{
    /**
     * SearchByNumero.
     *
     * @param string $str_search numéro cb à rechercher
     *
     * @return array
     */
    public function searchByNumero($str_search)
    {
        $str_search = '%'.$str_search.'%';
        $queryBuilder = $this->createQueryBuilder('cb')
                             ->where('cb.numero LIKE :recherche ')
                             ->setParameter('recherche', $str_search);

        return $queryBuilder->getQuery()
                            ->getResult();
    }

    /**
     * Return number of chat credit cards for client
     *
     * @param Client $client
     *
     * @return
     */
    public function countChatCbsByClient(Client $client)
    {
        return $this->createQueryBuilder('cb')
            ->select('COUNT(cb.id)')
            ->innerJoin('cb.clients', 'c')
            ->where('c = :client')->setParameter('client', $client)
            ->andWhere('cb.firstName IS NOT NULL')
            ->getQuery()
            ->getScalarResult();
    }
}
