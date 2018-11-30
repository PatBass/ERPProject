<?php

namespace KGC\PaymentBundle\Service\Payment;

use KGC\ChatBundle\Entity\ChatFormulaRate;
use KGC\ChatBundle\Entity\ChatPayment;
use KGC\ChatBundle\Entity\ChatType;
use KGC\ChatBundle\Service\PricingCalculator;
use KGC\PaymentBundle\Entity\Payment;
use KGC\PaymentBundle\Service\Payment\Gateway\Exception\GatewayNotFoundException;

class TwigUtils
{
    /**
     * @var Factory
     */
    protected $factory;

    /**
     * @var PricingCalculator
     */
    protected $pricingCalculator;

    public function __construct(Factory $factory, PricingCalculator $pricingCalculator)
    {
        $this->factory = $factory;
        $this->pricingCalculator = $pricingCalculator;
    }

    /**
     * @param Payment $payment
     *
     * @return string
     */
    public function getPaymentBoUrl(Payment $payment)
    {
        try {
            return $this->factory->get($payment->getTpe()->getPaymentGateway())->getPaymentBoUrl($payment);
        } catch (GatewayNotFoundException $e) {
            return null;
        }
    }

    /**
     * @param Payment $payment
     *
     * @return string
     */
    public function getPaymentDetails(Payment $payment)
    {
        try {
            return $this->factory->get($payment->getTpe()->getPaymentGateway())->getPaymentDetails($payment);
        } catch (GatewayNotFoundException $e) {
            return null;
        }
    }

    /**
     * @param ChatPayment $chatPayment
     *
     * @return string
     */
    public function getChatPaymentFormulaString($chatPayment)
    {
        if ($chatPayment instanceof ChatPayment) {
            return $this->pricingCalculator->buildOfferString($chatPayment, true);
        } else if (isset($chatPayment['chatFormulaRate']['type']) && $chatPayment['chatFormulaRate']['type'] == 3) {
            return 'Abonnement';
        } else {
            return null;
        }
    }
}
