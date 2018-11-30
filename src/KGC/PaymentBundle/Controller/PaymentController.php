<?php

namespace KGC\PaymentBundle\Controller;

use KGC\PaymentBundle\Exception\Payment\InvalidSignatureException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    /**
     * @param Request $request
     */
    public function notifyAction(Request $request)
    {
        try {
            $status = $this->get('kgc.payment.factory')
                ->get($request->get('gateway'))
                ->notify();

            if ($status) {
                $this->get('kgc.rdv.manager')->updateRdvFromStatus($status)
                    || $this->get('kgc.rdv.manager')->updateAuthorizationFromStatus($status)
                    || $this->get('kgc.chat.payment.manager')->updateChatPaymentFromStatus($status);
            }
        } catch (InvalidSignatureException $e) {
            return new Response('KO');
        }



        return new Response('OK');
    }
}
