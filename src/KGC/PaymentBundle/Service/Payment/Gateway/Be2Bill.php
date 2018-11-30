<?php

namespace KGC\PaymentBundle\Service\Payment\Gateway;

use Payum\Core\Reply\HttpResponse;
use Payum\Core\Registry\AbstractRegistry;
use Payum\Core\Request\Authorize;
use Payum\Core\Request\Capture;
use Payum\Core\Request\Convert;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Request\GetToken;
use Payum\Core\Request\Notify;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Payum\Core\Security\SensitiveValue;
use Zol\Payum\Be2BillExtended\Api;
use Doctrine\Orm\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\Bundle\SharedBundle\Model\CreditCard;
use KGC\PaymentBundle\Entity\Authorization;
use KGC\PaymentBundle\Entity\Payment;
use KGC\PaymentBundle\Entity\PaymentAlias;
use KGC\PaymentBundle\Exception\Payment\InvalidCardDataException;
use KGC\PaymentBundle\Service\Payment\PaymentStatus;
use KGC\RdvBundle\Entity\TPE;
use Psr\Log\LoggerInterface;

class Be2Bill extends Gateway
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
        return TPE::PAYMENT_GATEWAY_BE2BILL;
    }

    protected function processAuthorize(Client $client, $amount, $data)
    {
        return $this->processCommon(
            $client,
            $amount,
            $data,
            function (Payment $payment) {
                $gateway = $this->payum->getGateway('be2bill_authorize');

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
                $gateway = $this->payum->getGateway('be2bill_authorize');

                $transactionId = $authorization->getAuthorizePayment()->getDetails()['TRANSACTIONID'];
                $payment->setDetails(['TRANSACTIONID' => $transactionId] + $payment->getDetails());

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

        /*$tokenHash = $this->tokenFactory
            ->createToken(
                $this->getName(),
                $payment,
                'kgc_payment_notify',
                ['gateway' => $this->getName()]
            )
            ->getHash();

        $details['EXTRADATA'] = json_encode(['notify_token' => $tokenHash]);

        $payment->setDetails($details);
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
        $payment->setTotalAmount($amount * 100); // 1.23 EUR
        $payment->setDescription('A description');
        $payment->setClientId($client->getId());
        $payment->setClientEmail($client->getMail());

        if ($data instanceof CreditCard) {
            $details = [
                'LANGUAGE' => 'EN',
                'CARDFULLNAME' => $data->getFirstName().' '.$data->getLastName(),
                'CARDCODE' => new SensitiveValue($data->getNumber()),
                'CARDVALIDITYDATE' => new SensitiveValue($data->getExpireAt()->format('m-y')),
                'CARDCVV' => new SensitiveValue($data->getSecurityCode()),
                'CREATEALIAS' => 'yes',
                // '3DSECURE' => 'yes',
                // '3DSECUREDISPLAYMODE' => 'popup'
            ];
        } else if ($data instanceof PaymentAlias) {
            $payment->setPaymentAlias($data);
            $details = ['ALIASMODE' => $isSubscription ? 'subscription' : 'oneclick'] + $data->getDetails();
        } else {
            $details = $data;
        }

        $details['CLIENTUSERAGENT'] = \GuzzleHttp\default_user_agent();
        $details['CLIENTIP'] = '127.0.0.1';

        $payment->setDetails($details);
        $storage->update($payment);

        try {
            $gateway = $mainProcessFunc($payment);
        } catch (\Exception $e) {
            $payment->setException($e);
            $storage->update($payment);

            throw $e;
        }

        $status = new PaymentStatus($payment);
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
        } else {
            $details = $payment->getDetails();
            if (
                isset($details['EXECCODE'])
                && in_array(
                    $details['EXECCODE'],
                    // card codes considered as "forbidden"
                    [Api::EXECCODE_CARD_LOST, Api::EXECCODE_STOLEN_CARD]
                )
            ) {
                $payment->addTag(Payment::TAG_CBI);

                $storage->update($payment);
            }
        }

        return $status;
    }

    public function notify()
    {
        // not reliable
        return null;
        $gateway = $this->payum->getGateway($this->getName());
        $gateway->execute($httpRequest = new GetHttpRequest());

        //we are back from be2bill site so we have to just update model.
        if (empty($httpRequest->query['EXTRADATA'])) {
            throw new HttpResponse('The notification is invalid. Code Be2Bell1', 400);
        }

        $extraDataJson = $httpRequest->query['EXTRADATA'];
        if (false == $extraData = json_decode($extraDataJson, true)) {
            throw new HttpResponse('The notification is invalid. Code Be2Bell2', 400);
        }

        if (empty($extraData['notify_token'])) {
            throw new HttpResponse('The notification is invalid. Code Be2Bell3', 400);
        }

        $gateway->execute($getToken = new GetToken($extraData['notify_token']));
        try {
            $gateway->execute(new Notify($getToken->getToken()));
        } catch (HttpResponse $e) {
            if ($e->getStatusCode() !== 200) {
                throw $e;
            }
        }

        $gateway->execute($status = new GetHumanStatus($getToken->getToken()));

        $details = $status->getModel();
        if (isset($details['ALIAS'])) {
            $client = $this->em->getRepository('KGCSharedBundle:Client')->find($status->getFirstModel()->getClientId());

            if (!$this->findExistingAlias($client, ['ALIAS' => $details['ALIAS']])) {
                $expirationDate = \DateTime::createFromFormat('d-y', $details['CARDVALIDITYDATE']);

                $payment->setPaymentAlias(
                    $this->storePaymentAlias(
                        $client,
                        $details['CARDCODE'],
                        ['ALIAS' => $details['ALIAS']],
                        $expirationDate->modify('last day of this month')
                    )
                );

                $storage->update($payment);
            }
        }

        return PaymentStatus::toPaymentStatus($status);
    }

    public function cancel(Authorization $authorization, $amount = null)
    {
        return new \Exception('Not implemented method');
    }

    public function getPaymentException(Payment $payment = null)
    {
        $details = isset($payment) ? $payment->getDetails() : null;

        switch (isset($details['EXECCODE']) ? $details['EXECCODE'] : null) {
            case Api::EXECCODE_INVALID_CARD_DATA:
                $exception = new InvalidCardDataException;
                break;
            default:
                $exception = parent::getPaymentException($payment);
        }

        return $exception;
    }
}
