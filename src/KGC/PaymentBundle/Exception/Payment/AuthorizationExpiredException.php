<?php

namespace KGC\PaymentBundle\Exception\Payment;

class AuthorizationExpiredException extends PaymentRefusedException
{
    public function __construct($message = "Autorisation expirée", $code = 0, $previous = NULL)
    {
        parent::__construct($message, $code, $previous);
    }
}