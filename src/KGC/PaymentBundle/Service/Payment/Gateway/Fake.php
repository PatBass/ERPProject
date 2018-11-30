<?php

namespace KGC\PaymentBundle\Service\Payment\Gateway;

use Payum\Core\Registry\AbstractRegistry;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Doctrine\Orm\EntityManagerInterface;
use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\Bundle\SharedBundle\Model\CreditCard;
use KGC\PaymentBundle\Entity\Authorization;
use KGC\PaymentBundle\Entity\Payment;
use KGC\PaymentBundle\Entity\PaymentAlias;
use KGC\PaymentBundle\Exception\Payment\InvalidCardDataException;
use KGC\PaymentBundle\Exception\Payment\PaymentFailedException;
use KGC\PaymentBundle\Service\Payment\PaymentStatus;
use Psr\Log\LoggerInterface;

class Fake extends Gateway
{
    const VALID_CARD_NUMBER = '5555555555554444';
    const INVALID_CARD_NUMBER = '5555557376384001';
    const INVALID_CARD_DATA_NUMBER = '5555554530114002';
    const EXCEPTION_CARD_NUMBER = '5555558726544005';

    /**
     * @var AbstractRegistry;
     */
    protected $payum;

    /**
     * @var GenericTokenFactoryInterface
     */
    protected $tokenFactory;

    /**
     * @var string
     */
    protected $name;

    public function __construct(EntityManagerInterface $em, AbstractRegistry $payum, GenericTokenFactoryInterface $tokenFactory, $name = 'fake')
    {
        parent::__construct($em);

        $this->payum = $payum;
        $this->tokenFactory = $tokenFactory;
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    protected function processAuthorize(Client $client, $amount, $data)
    {
        return $this->processCommon($client, $amount, $data, 'authorized');
    }

    protected function processCapture(Authorization $authorization, $amount)
    {
        return $this->processCommon($authorization->getClient(), $amount, []);
    }

    protected function processPayment(Client $client, $amount, $data, $isSubscription)
    {
        return $this->processCommon($client, $amount, $data);
    }

    public function cancel(Authorization $authorization)
    {
        $status = $this->processCommon($authorization->getClient(), 0, [], 'cancel');

        $authorization->setCapturePayment($payment = $status->getFirstModel());
        $authorization->setCapturedAmount(0);
        $originPayment = $authorization->getAuthorizePayment();
        $originPayment->setLastPayment($payment = $status->getFirstModel());
        $payment->setOriginalPayment($originPayment);

        $this->em->persist($authorization);
        $this->em->persist($originPayment);
        $this->em->persist($payment);
        $this->em->flush([$authorization, $originPayment, $payment]);

        return $status;
    }

    protected function processCommon(Client $client, $amount, $data, $validType = 'captured')
    {
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
                'CREATEALIAS' => 'yes',
            ];
        } elseif ($data instanceof PaymentAlias) {
            $payment->setPaymentAlias($data);
            $details = $data->getDetails();
        } else {
            $details = $data;
        }

        $payment->setDetails($details);
        $storage->update($payment);

        $status = new PaymentStatus($payment);
        $status->setModel($payment->getDetails());

        try {
            if ($data instanceof CreditCard && $data->getNumber() == self::INVALID_CARD_DATA_NUMBER) {
                throw new InvalidCardDataException('Invalid card data');
            } else if ($data instanceof CreditCard && $data->getNumber() == self::EXCEPTION_CARD_NUMBER) {
                throw new PaymentFailedException('Card exception thrown by gateway');
            } else if (
                ($data instanceof CreditCard && $data->getNumber() == self::VALID_CARD_NUMBER) ||
                !$data instanceof CreditCard
            ) {
                if ($validType === 'captured') {
                    $txtStatus = 'captured';
                    $status->markCaptured();
                } else if ($validType === 'cancel') {
                    $txtStatus = 'cancel';
                    $status->markCanceled();
                } else {
                    $txtStatus = 'authorized';
                    $status->markAuthorized();
                }

                if ($data instanceof CreditCard) {
                        $expirationDate = $data->getExpireAt();
                        $expirationDate->modify('last day of this month');

                        if ($expirationDate < new \DateTime) {
                            throw new InvalidCardDataException('Expired card');
                        }

                    $payment->setPaymentAlias(
                        $this->storePaymentAlias(
                            $client,
                            $data->getMaskedNumber(),
                            ['ALIAS' => uniqid()],
                            $expirationDate
                        )
                    );

                }
            } else {
                $txtStatus = 'failed';
                $status->markFailed();
            }
        } catch (PaymentFailedException $e) {
            $payment->setException(json_encode(['code' => $e->getCode(), 'message' => $e->getMessage(), 'exception_details' => $e->__toString()]));

            $this->em->persist($payment);
            $this->em->flush();

            $e->setPayment($payment);
            throw $e;
        }

        $payment->setResponse(json_encode(['status' => $txtStatus]));
        $storage->update($payment);

        return $status;
    }



    public function notify()
    {
        return;
    }
}
