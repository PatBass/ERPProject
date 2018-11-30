<?php

namespace KGC\PaymentBundle\Exception\Payment;

class InvalidCardDataException extends PaymentRefusedException
{
    public function __construct($message = null, $code = 0, $previous = NULL)
    {
        parent::__construct($message, $code, $previous);
    }
}