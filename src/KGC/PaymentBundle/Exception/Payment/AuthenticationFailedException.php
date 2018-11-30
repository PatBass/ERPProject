<?php

namespace KGC\PaymentBundle\Exception\Payment;

class AuthenticationFailedException extends PaymentFailedException
{
    public function __construct($message = "Authentication échouée", $code = 0, $previous = NULL)
    {
        parent::__construct($message, $code, $previous);
    }
}