<?php

namespace KGC\PaymentBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Payment Repository.
 *
 * @category EntityRepository
 */
class PaymentRepository extends EntityRepository
{
    /**
     * Find payment by client and response
     *
     * @param int $client
     * @param string $responseLike
     *
     * @return Payment
     */
    public function findOneByClientIdAndResponse($clientId, $responseLike)
    {
        return $this->createQueryBuilder('p')
            ->where('p.clientId = :clientId')->setParameter('clientId', $clientId)
            ->andWhere('p.originalPayment IS NULL')
            ->andWhere('p.response LIKE :responseLike')->setParameter('responseLike', $responseLike)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
