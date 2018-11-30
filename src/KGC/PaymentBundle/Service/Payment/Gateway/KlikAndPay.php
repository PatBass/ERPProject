<?php

namespace KGC\PaymentBundle\Service\Payment\Gateway;

use Payum\Core\Registry\AbstractRegistry;
use Payum\Core\Request\Authorize;
use Payum\Core\Request\Capture;
use Payum\Core\Request\Convert;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Payum\Core\Security\SensitiveValue;
use Doctrine\Orm\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use KGC\Bundle\SharedBundle\Entity\Adresse;
use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\Bundle\SharedBundle\Model\CreditCard;
use KGC\PaymentBundle\Entity\Authorization;
use KGC\PaymentBundle\Entity\Payment;
use KGC\PaymentBundle\Entity\PaymentAlias;
use KGC\PaymentBundle\Service\Payment\PaymentStatus;
use KGC\RdvBundle\Entity\TPE;
use Psr\Log\LoggerInterface;

class KlikAndPay extends Gateway
{
    /**
     * @var AbstractRegistry;
     */
    protected $payum;

    /**
     * @var GenericTokenFactoryInterface
     */
    protected $tokenFactory;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    public function __construct(EntityManagerInterface $em, AbstractRegistry $payum, GenericTokenFactoryInterface $tokenFactory, RequestStack $requestStack)
    {
        parent::__construct($em);

        $this->payum = $payum;
        $this->tokenFactory = $tokenFactory;
        $this->requestStack = $requestStack;
    }

    public function getName()
    {
        return TPE::PAYMENT_GATEWAY_KLIKANDPAY;
    }

    protected function processAuthorize(Client $client, $amount, $data)
    {
        return $this->processCommon(
            $client,
            $amount,
            $data,
            function (Payment $payment) {
                $gateway = $this->payum->getGateway($this->getName());

                $gateway->execute($convert = new Convert($payment, 'array'));
                $payment->setDetails($payment->getDetails() + $convert->getResult());

                $gateway->execute(new Authorize($payment));

                return $gateway;
            },
            false,
            true
        );
    }

    protected function processCapture(Authorization $authorization, $amount)
    {
        return $this->processCommon(
            $authorization->getClient(),
            $amount,
            [],
            function (Payment $payment) use ($authorization) {
                $gateway = $this->payum->getGateway($this->getName());

                $transactionId = $authorization->getAuthorizePayment()->getDetails()['TX'];
                $payment->setDetails(['TX' => $transactionId] + $payment->getDetails());

                $gateway->execute(new Capture($payment));

                return $gateway;
            },
            false,
            true
        );
    }

    protected function processPayment(Client $client, $amount, $data, $isSubscription)
    {
        return $this->processCommon(
            $client,
            $amount,
            $data,
            function (Payment $payment) {
                $gateway = $this->payum->getGateway($this->getName());

                $gateway->execute(new Capture($payment));

                return $gateway;
            },
            $isSubscription
        );

        /*$details['RETOUR'] = $this->tokenFactory
            ->createToken(
                $this->getName(),
                $payment,
                'kgc_payment_notify',
                ['gateway' => $this->getName()]
            )
            ->getHash();

        $storage->update($payment);*/
    }

    protected function processCommon(Client $client, $amount, $data, callable $mainProcessFunc, $isSubscription = false, $capture = false)
    {
        $request = $this->requestStack->getCurrentRequest();

        $storage = $this->payum->getStorage('KGC\PaymentBundle\Entity\Payment');

        /* @var \KGC\Bundle\SharedBundle\Entity\Payment */
        $payment = $storage->create();
        $payment->setTPE($this->getTPE());
        $payment->setNumber(uniqid());
        $payment->setCurrencyCode('EUR');
        $payment->setTotalAmount($amount); // 1.23 EUR
        $payment->setDescription('A description');
        $payment->setClientId($client->getId());
        $payment->setClientEmail($client->getMail());

        if ($data instanceof CreditCard) {
            $details = [
                'PRENOM' => $data->getFirstName(),
                'NOM' => $data->getLastName(),
                'TEL' => '-',
                'L' => $request ? $request->getLocale() : 'fr-FR',
                'NUMCARTE' => new SensitiveValue($data->getNumber()),
                'EXPMOIS' => new SensitiveValue($data->getExpireAt()->format('m')),
                'EXPANNEE' => new SensitiveValue($data->getExpireAt()->format('Y')),
                'CVV' => new SensitiveValue($data->getSecurityCode()),
            ];

            $lastAdresse = $client->getLastAdresse();

            if ($lastAdresse instanceof Adresse) {
                $details += [
                    'ADRESSE' => $lastAdresse->getVoie(),
                    'CODEPOSTAL' => $lastAdresse->getCodePostal(),
                    'VILLE' => $lastAdresse->getVille(),
                    'PAYS' => 'FR',
                ];
            } else {
                $details += [
                    'ADRESSE' => '-',
                    'CODEPOSTAL' => '-',
                    'VILLE' => '-',
                    'PAYS' => 'FR',
                ];
            }

            $details['IP'] = '127.0.0.1';
        } else if ($data instanceof PaymentAlias) {
            $payment->setPaymentAlias($data);
            $details = $data->getDetails();
        } else {
            $details = $data;
        }

        $payment->setDetails($details);
        $storage->update($payment);

        try {
            $gateway = $mainProcessFunc($payment);
        } catch (\Exception $e) {
            $payment->setException($e);
            $storage->update($payment);

            throw $e;
        }

        $status = new GetHumanStatus($payment);
        $status->setModel($payment->getDetails());
        $gateway->execute($status);

        if (
            ($status->isCaptured() || $status->isAuthorized())
            && $data instanceof CreditCard
        ) {
            $updatedPaymentDetails = $status->getModel();

            if (isset($updatedPaymentDetails['ALIAS'])) {
                $expirationDate = $data->getExpireAt();

                $payment->setPaymentAlias(
                    $this->storePaymentAlias(
                        $client,
                        $data->getMaskedNumber(),
                        ['ALIAS' => $updatedPaymentDetails['ALIAS']],
                        $expirationDate->modify('last day of this month')
                    )
                );

                $storage->update($payment);
            }
        }

        return PaymentStatus::toPaymentStatus($status);
    }

    public function notify()
    {
        return;
    }

    public function cancel(Authorization $authorization)
    {

    }
}
