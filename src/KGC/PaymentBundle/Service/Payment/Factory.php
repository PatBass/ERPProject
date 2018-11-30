<?php

namespace KGC\PaymentBundle\Service\Payment;

use Doctrine\ORM\EntityManagerInterface;
use KGC\PaymentBundle\Service\Payment\Gateway\Exception\GatewayNotFoundException;
use KGC\PaymentBundle\Service\Payment\Gateway\GatewayInterface;

class Factory implements \IteratorAggregate
{
    /**
     * @var array
     */
    protected $gateways = [];

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function add(GatewayInterface $gateway)
    {
        $this->gateways[$gateway->getName()] = $gateway;
    }

    /**
     * @param string $gatewayName
     *
     * @return GatewayInterface
     */
    public function get($gatewayName)
    {
        if (!array_key_exists($gatewayName, $this->gateways)) {
            throw new GatewayNotFoundException(
                sprintf('Unable to find gateway "%s"', $gatewayName)
            );
        }

        return $this->gateways[$gatewayName];
    }

    /**
     * @param string $reference
     *
     * @return GatewayInterface
     */
    public function getByWebsiteReference($reference)
    {
        static $gatewayByName = null;

        if ($gatewayByName === null) {
            $gatewayByName = $this->em->getRepository('KGCSharedBundle:Website')->getPaymentGatewaysByReference();
        }

        return $this->get(isset($gatewayByName[$reference]) ? $gatewayByName[$reference] : null);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->gateways);
    }
}
