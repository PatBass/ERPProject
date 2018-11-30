<?php

namespace KGC\RdvBundle\Service;

use Doctrine\Common\Persistence\ObjectManager;
use JMS\DiExtraBundle\Annotation as DI;

use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\Bundle\SharedBundle\Model\CreditCard;
use KGC\PaymentBundle\Entity\Authorization;
use KGC\PaymentBundle\Entity\Payment;
use KGC\PaymentBundle\Service\Payment\Factory as PaymentFactory;
use KGC\PaymentBundle\Service\Payment\PaymentStatus;
use KGC\RdvBundle\Entity\CarteBancaire;
use KGC\RdvBundle\Entity\Encaissement;
use KGC\RdvBundle\Entity\RDV;

/**
 * @DI\Service("kgc.rdv.payment_manager")
 */
class PaymentManager implements CarteBancairePayInterface
{
    /**
     * @var ObjectManager
     */
    protected $entityManager;

    /**
     * @var CarteBancaireManager
     */
    protected $cbManager;

    /**
     * @var PaymentFactory
     */
    protected $paymentFactory;

    /**
     * @param ObjectManager        $entityManager
     * @param CarteBancaireManager $cbManager
     * @param PaymentFactory       $paymentFactory
     *
     * @DI\InjectParams({
     *     "entityManager"   = @DI\Inject("doctrine.orm.entity_manager"),
     *     "cbManager"       = @DI\Inject("kgc.rdv.carte_bancaire.manager"),
     *     "paymentFactory"  = @DI\Inject("kgc.payment.factory")
     * })
     */
    public function __construct(
        ObjectManager $entityManager,
        CarteBancaireManager $cbManager,
        PaymentFactory $paymentFactory
    ) {
        $this->entityManager = $entityManager;
        $this->cbManager = $cbManager;
        $this->paymentFactory = $paymentFactory;
    }

    public function getPaymentException(PaymentStatus $status)
    {
        $payment = $status->getFirstModel();
        $gateway = $this->paymentFactory->get($payment->getTpe()->getPaymentGateway());

        return $gateway->getPaymentException($payment);
    }

    protected function getPaymentMean(Client $client, CarteBancaire $carteBancaire, $paymentGateway)
    {
        $paymentMean = null;

        if ($cbAliases = $carteBancaire->getPaymentAliases()) {
            foreach ($cbAliases as $cbAlias) {
                if ($cbAlias->getGateway() == $paymentGateway) {
                    $paymentMean = $cbAlias;
                    break;
                }
            }
        }

        if (!$paymentMean) {
            $this->cbManager->decrypt($carteBancaire);

            $paymentMean = $carteBancaire->toCreditCard($client);
        }

        return $paymentMean;
    }

    public function payWithCartebancaire(Client $client, CarteBancaire $carteBancaire, $paymentGateway, $amount, $isSubscription = false, $persist = true)
    {
        $paymentMean = $this->getPaymentMean($client, $carteBancaire, $paymentGateway);

        $gateway = $this->paymentFactory->get($paymentGateway);

        $status = $gateway->payment($client, $amount, $paymentMean, $isSubscription);
        $payment = $status->getFirstModel();

        if ($status->isCaptured() && $paymentMean instanceof CreditCard && $payment->getPaymentAlias()) {
            $carteBancaire->addPaymentAlias($payment->getPaymentAlias());
        } else if ($payment->hasTag(Payment::TAG_CBI)) {
            $carteBancaire->setInterdite(true);
        }

        if ($persist === true) {
            $this->entityManager->persist($carteBancaire);
            $this->entityManager->flush($carteBancaire);
        }

        return $status;
    }

    public function preAuthorizeWithCartebancaire(Client $client, CarteBancaire $carteBancaire, $paymentGateway, $amount, $persist = true)
    {
        $paymentMean = $this->getPaymentMean($client, $carteBancaire, $paymentGateway);

        $gateway = $this->paymentFactory->get($paymentGateway);

        $status = $gateway->authorize($client, $amount, $paymentMean);
        $payment = $status->getFirstModel();

        if ($status->isAuthorized() && $paymentMean instanceof CreditCard) {
            $carteBancaire->addPaymentAlias($payment->getPaymentAlias());
        } else if ($payment->hasTag(Payment::TAG_CBI)) {
            $carteBancaire->setInterdite(true);
        }

        if ($persist === true) {
            $this->entityManager->persist($carteBancaire);
            $this->entityManager->flush($carteBancaire);
        }

        return $status;
    }

    public function captureAuthorization(Authorization $authorization, $amount, $persist = true)
    {
        $gateway = $this->paymentFactory->get($authorization->getAuthorizePayment()->getTpe()->getPaymentGateway());

        $status = $gateway->capture($authorization, $amount);

        if ($status->isCaptured()) {
            $authorization->setCapturePayment($status->getFirstModel());
            $authorization->setCapturedAmount($amount);
        }

        if ($persist === true) {
            $this->entityManager->persist($authorization);
            $this->entityManager->flush($authorization);
        }

        return $status;
    }

    public function generatePreAuthorizationWithCreditCard(RDV $rdv, CreditCard $creditCard, $paymentGateway, $amount, $persist = true)
    {
        $carteBancaire = (new CarteBancaire)
            ->setNumero($creditCard->getNumber())
            ->setCryptogramme($creditCard->getSecurityCode())
            ->setExpiration($creditCard->getExpireAt()->format('m/y'));

        $client = $rdv->getClient();
        $creditCard->setFirstName($client->getPrenom());
        $creditCard->setLastName($client->getNom());

        $rdv->addCartebancaires($carteBancaire);

        $status = $this->preAuthorizeWithCartebancaire($client, $carteBancaire, $paymentGateway, $amount, false);

        if ($status->isAuthorized()) {
            $rdv->setPreAuthorization(
                (new Authorization)
                    ->setClient($rdv->getClient())
                    ->setAuthorizePayment($status->getFirstModel())
                    ->setAuthorizedAmount($amount)
            );
        }

        if ($persist === true) {
            $this->entityManager->persist($rdv);
            $this->entityManager->flush($rdv);
        }

        return $status;
    }

    /**
     * @param Authorization $authorization
     *
     * @return PaymentStatus
     */
    public function cancelAuthorization(Authorization $authorization, $flush = true)
    {
        if (
            $authorization->getCapturePayment() !== null
            || !$authorization->getAuthorizePayment()->getTpe()->isCancellable()
        ) {
            return null;
        }

        $gateway = $this->paymentFactory->get($authorization->getAuthorizePayment()->getTpe()->getPaymentGateway());

        $status = $gateway->cancel($authorization);

        if ($status && $status->isCanceled()) {
            $authorization->setCapturePayment($status->getFirstModel());
            $authorization->setCapturedAmount(0);

            $this->entityManager->persist($authorization);
            if ($flush) {
                $this->entityManager->flush($authorization);
            }
        }

        return $status;
    }

    /**
     * @param PaymentStatus $status
     *
     * @return bool true si modifié
     */
    public function updateEncaissementFromStatus(Encaissement $encaissement, PaymentStatus $status)
    {
        $isDifferent = false;

        switch ($encaissement->getEtat()) {
            case Encaissement::DONE:
                $isDifferent = !$status->isCaptured();
                break;
            case Encaissement::DENIED:
                $isDifferent = !$status->isFailed();
                break;
            case Encaissement::CANCELLED:
                $isDifferent = !$status->isCanceled();
                break;
            case Encaissement::REFUNDED:
                $isDifferent = !$status->isRefunded();
                break;
        }

        if ($isDifferent) {
            if ($status->isCaptured()) {
                $encaissement->setEtat(Encaissement::DONE);
            } else if ($status->isFailed()) {
                $encaissement->setEtat(Encaissement::DENIED);
            } else if ($status->isRefunded()) {
                $encaissement->setEtat(Encaissement::REFUNDED);
                $encaissement->setDateOppo(new \DateTime);
            } else if ($status->isOpposed()) {
                $encaissement->setEtat(Encaissement::CANCELLED);
                $encaissement->setDateOppo(new \DateTime);
            } else {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * @param CarteBancaire
     */
    public function decryptCb(CarteBancaire $carteBancaire)
    {
        $this->cbManager->decrypt($carteBancaire);
    }
}