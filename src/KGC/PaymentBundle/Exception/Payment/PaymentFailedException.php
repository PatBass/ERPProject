<?php

namespace KGC\PaymentBundle\Exception\Payment;

use KGC\PaymentBundle\Entity\Payment;

class PaymentFailedException extends \Exception
{
    protected $payment = null;

    /**
     * @return Payment
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * @param Payment
     */
    public function setPayment(Payment $payment)
    {
        $this->payment = $payment;
    }
}