<?php

namespace KGC\PaymentBundle\Service\Payment\Gateway;

use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\PaymentBundle\Entity\Authorization;
use KGC\PaymentBundle\Entity\Payment;

interface GatewayInterface
{
    public function getName();

    /**
     * @param Client $client
     * @param float $amount
     * @param CreditCard|PaymentAlias $data
     *
     * @return PaymentStatus
     */
    public function authorize(Client $client, $amount, $data);

    /**
     * @param Authorization $authorization
     * @param float $amount
     *
     * @return PaymentStatus
     */
    public function capture(Authorization $authorization, $amount);

    /**
     * @param Client $client
     * @param float $amount
     * @param CreditCard|PaymentAlias $data
     * @param bool $isSubscription
     *
     * @return PaymentStatus
     */
    public function payment(Client $client, $amount, $data, $isSubscription);

    /**
     * @return PaymentStatus
     */
    public function notify();

    /**
     * @param Authorization $authorization
     *
     * @return PaymentStatus
     */
    public function cancel(Authorization $authorization);
}
