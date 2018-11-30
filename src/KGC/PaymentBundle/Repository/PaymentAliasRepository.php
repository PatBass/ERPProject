<?php

namespace KGC\PaymentBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\PaymentBundle\Entity\Payment;

/**
 * PaymentAlias Repository.
 *
 * @category EntityRepository
 */
class PaymentAliasRepository extends EntityRepository
{
    /**
     * @param Client $client
     * @param string $gateway
     *
     * @return PaymentAlias|null
     */
    public function findLastOneByClientAndGateway(Client $client, $gateway, $withNoCbi = true)
    {
        $qb = $this->getQueryBuilderByClientAndGateway($client, $gateway);

        if ($withNoCbi) {
            $qb->leftJoin('KGCPaymentBundle:Payment', 'p', Join::WITH, 'p.paymentAlias = a.id AND BIT_AND(p.tags, '.Payment::TAG_CBI.') > 0')
                ->andWhere('p.id IS NULL')
                ->groupBy('a.id');
        }

        return $qb
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllByClientAndGateway(Client $client, $gateway)
    {
        return $this->getQueryBuilderByClientAndGateway($client, $gateway)
            ->getQuery()
            ->getResult();
    }

    protected function getQueryBuilderByClientAndGateway(Client $client, $gateway)
    {
        return $this->createQueryBuilder('a')
            ->select('a')
            ->where('a.client = :client')
            ->andWhere('a.gateway = :gateway')
            ->setParameter('client', $client)
            ->setParameter('gateway', $gateway)
            ->addOrderBy('a.id', 'DESC');
    }

    public function findAliasByClientGatewayAndDetails(Client $client, $gateway, $details)
    {
        return $this->createQueryBuilder('a')
            ->where('a.client = :client')
            ->andWhere('a.gateway = :gateway')
            ->andWhere('a.details = :details')
            ->setParameter('client', $client)
            ->setParameter('gateway', $gateway)
            ->setParameter('details', json_encode($details))
            ->getQuery()
            ->getOneOrNullResult();
    }
}
