<?php

namespace KGC\PaymentBundle\Exception\Payment;

class PaymentSuspiciousException extends PaymentRefusedException
{
    public function __construct($message = "Paiement suspect", $code = 0, $previous = NULL)
    {
        parent::__construct($message, $code, $previous);
    }
}