<?php

namespace KGC\PaymentBundle\Service\Payment\Gateway;

use Doctrine\Orm\EntityManagerInterface;
use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\Bundle\SharedBundle\Model\CreditCard;
use KGC\PaymentBundle\Entity\Authorization;
use KGC\PaymentBundle\Entity\Payment;
use KGC\PaymentBundle\Entity\PaymentAlias;
use KGC\PaymentBundle\Exception\Payment\PaymentRefusedException;
use KGC\CommonBundle\Traits\DayPostponementInterface;

abstract class Gateway implements GatewayInterface, DayPostponementInterface
{
    /**
     * @var EntityManager;
     */
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * return TPE related to payment gateway.
     *
     * @return KGC\RdvBundle\Entity\TPE
     */
    public function getTPE()
    {
        return $this->em->getRepository('KGCRdvBundle:TPE')->findOneByPaymentGateway($this->getName());
    }

    /**
     * @param Client $client
     * @param float $amount
     * @param CreditCard|PaymentAlias $data
     *
     * @return PaymentStatus
     */
    public function authorize(Client $client, $amount, $data)
    {
        if ($data instanceof PaymentAlias) {
            if ($data->getClient() != $client || $data->getGateway() != $this->getName()) {
                throw new Exception\InvalidParameterException('Invalid alias (wrong client and/or gateway)');
            }
        } elseif (!$data instanceof CreditCard) {
            throw new Exception\MissingParametersException('CreditCard required');
        }

        return $this->processAuthorize($client, $amount, $data);
    }

    /**
     * @param Authorization $authorization
     * @param float $amount
     *
     * @return PaymentStatus
     */
    public function capture(Authorization $authorization, $amount)
    {
        if ($authorization->getTpe() != $this->getTPE()) {
            throw new Exception\InvalidParameterException('Invalid authorization (wrong gateway)');
        }

        return $this->processCapture($authorization, $amount);
    }

    /**
     * @param Client $client
     * @param float $amount
     * @param CreditCard|PaymentAlias $data
     *
     * @return PaymentAlias
     */
    public function payment(Client $client, $amount, $data, $isSubscription = false)
    {
        if ($data instanceof PaymentAlias) {
            if ($data->getClient() !== null && $data->getClient()->getId() != $client->getId() || $data->getGateway() != $this->getName()) {
                throw new Exception\InvalidParameterException('Invalid alias (wrong client and/or gateway)');
            }
        } elseif (!$data instanceof CreditCard) {
            throw new Exception\MissingParametersException('CreditCard required');
        }

        return $this->processPayment($client, $amount, $data, $isSubscription);
    }

    /**
     * @param Client $client
     * @param string $name
     * @param CreditCard|PaymentAlias $data
     *
     * @return PaymentStatus
     */
    abstract protected function processAuthorize(Client $client, $amount, $data);

    /**
     * @param Authorization $authorization
     * @param float $amount
     *
     * @return PaymentStatus
     */
    abstract protected function processCapture(Authorization $authorization, $amount);

    /**
     * @param Client $client
     * @param string $name
     * @param CreditCard|PaymentAlias $data
     *
     * @return PaymentStatus
     */
    abstract protected function processPayment(Client $client, $amount, $data, $isSubscription);

    /**
     * @param Client $client
     * @param string $name
     * @param CreditCard|PaymentAlias $data
     * @param \DateTime $expiredAt
     *
     * @return PaymentAlias
     */
    protected function storePaymentAlias(Client $client, $name, $details, \DateTime $expiredAt)
    {
        $this->em->persist(
            $paymentAlias = (new PaymentAlias())
                ->setClient($client)
                ->setGateway($this->getName())
                ->setName($name)
                ->setDetails($details)
                ->setCreatedAt(new \DateTime())
                ->setExpiredAt($expiredAt)
        );

        $this->em->flush($paymentAlias);

        return $paymentAlias;
    }

    protected function findExistingAlias(Client $client, $details)
    {
        return $this->em->getRepository('KGCPaymentBundle:PaymentAlias')
            ->findAliasByClientGatewayAndDetails(
                $client,
                $this->getName(),
                $details
            );
    }

    public function getPaymentException(Payment $payment = null)
    {
        return new PaymentRefusedException;
    }

    /**
     * @param Payment $payment
     *
     * @return string
     */
    public function getPaymentBoUrl(Payment $payment)
    {
        return null;
    }

    /**
     * @param Payment $payment
     *
     * @return string
     */
    public function getPaymentDetails(Payment $payment)
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getFirstMonthNextReceiptDays()
    {
        return [2, 5, 6, 10, 12, 'end'];
    }

    /**
     * @inheritdoc
     */
    public function getOtherMonthsNextReceiptDays()
    {
        return [5, 10, 'end'];
    }

    /**
     * @inheritdoc
     */
    public function getAllowedConsecutiveFails()
    {
        return 10;
    }
}
