<?php

namespace KGC\PaymentBundle\Service\Payment\Gateway;

use Doctrine\Orm\EntityManagerInterface;
use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\Bundle\SharedBundle\Model\CreditCard;
use KGC\PaymentBundle\Entity\Authorization;
use KGC\PaymentBundle\Entity\Payment;
use KGC\PaymentBundle\Entity\PaymentAlias;
use KGC\PaymentBundle\Exception\Payment\AuthenticationFailedException;
use KGC\PaymentBundle\Exception\Payment\AuthorizationExpiredException;
use KGC\PaymentBundle\Exception\Payment\InvalidCardDataException;
use KGC\PaymentBundle\Exception\Payment\InvalidSignatureException;
use KGC\PaymentBundle\Exception\Payment\PaymentRefusedException;
use KGC\PaymentBundle\Exception\Payment\PaymentFailedException;
use KGC\PaymentBundle\Service\Payment\PaymentStatus;
use KGC\RdvBundle\Entity\TPE;
use HiPay\Fullservice\Enum\Transaction\Operation as OperationEnum;
use HiPay\Fullservice\Enum\Transaction\TransactionStatus;
use HiPay\Fullservice\Exception\RuntimeException;
use HiPay\Fullservice\Gateway\Client\GatewayClient;
use HiPay\Fullservice\Gateway\Mapper\TransactionMapper;
use HiPay\Fullservice\Gateway\Model\AbstractTransaction;
use HiPay\Fullservice\Gateway\Model\Operation;
use HiPay\Fullservice\Gateway\Model\Transaction;
use HiPay\Fullservice\Gateway\Request\Info\CustomerBillingInfoRequest;
use HiPay\Fullservice\Gateway\Request\Order\OrderRequest;
use HiPay\Fullservice\Gateway\Request\PaymentMethod\CardTokenPaymentMethod;
use HiPay\Fullservice\Helper\Signature;
use HiPay\Fullservice\HTTP\Configuration\Configuration;
use HiPay\Fullservice\HTTP\SimpleHTTPClient;
use HiPay\Fullservice\Model\AbstractModel;
use HiPay\Fullservice\SecureVault\Client\SecureVaultClient;
use HiPay\Fullservice\SecureVault\Mapper\PaymentCardTokenMapper;
use HiPay\Fullservice\SecureVault\Request\GenerateTokenRequest;
use Symfony\Component\HttpFoundation\RequestStack;

class Hipay extends Gateway
{
    const REASON_LOST_OR_STOLEN_CARD = '4010303';
    const REASON_RESTRICTED_CARD     = '4010304';
    const REASON_CARD_BLACKLISTED    = '4010306';

    /**
     * @var string
     */
    protected $name;

    /**
     * @var GatewayClient
     */
    protected $gatewayClient;

    /**
     * @var SecureVaultClient
     */
    protected $vaultClient;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var int
     */
    protected $account;

    /**
     * @var string
     */
    protected $passPhrase;

    /**
     * @var bool
     */
    protected $isMoto;

    public function __construct($name, EntityManagerInterface $em, Configuration $configuration, RequestStack $requestStack, $account, $passPhrase, $isMoto = false)
    {
        $this->name = $name;

        parent::__construct($em);

        $clientProvider = new SimpleHTTPClient($configuration);

        $this->gatewayClient = new GatewayClient($clientProvider);
        $this->vaultClient = new SecureVaultClient($clientProvider);
        $this->requestStack = $requestStack;
        $this->account = $account;
        $this->passPhrase = $passPhrase;
        $this->isMoto = $isMoto;
    }

    public function getName()
    {
        return $this->name;
    }

    protected function processAuthorize(Client $client, $amount, $data)
    {
        return $this->processPayment($client, $amount, $data, false, true);
    }

    protected function processCapture(Authorization $authorization, $amount = null)
    {
        $originalPayment = $authorization->getAuthorizePayment();

        $authorizeTransaction = $this->responseToModel($originalPayment->getResponse(), 'HiPay\Fullservice\Gateway\Mapper\TransactionMapper');

        $client = $authorization->getClient();

        $payment = new Payment;
        $payment->setTPE($this->getTPE());
        $payment->setNumber(uniqid());
        $payment->setCurrencyCode('EUR');
        $payment->setTotalAmount($amount * 100);
        $payment->setDescription('Hipay capture');
        $payment->setClientId($client->getId());
        $payment->setClientEmail($client->getMail());
        $payment->setOriginalPayment($originalPayment);
        $originalPayment->setLastPayment($payment);

        $details = [
            'operation' => OperationEnum::CAPTURE,
            'transaction_reference' => $reference = $authorizeTransaction->getTransactionReference(),
            'amount' => $amount,
        ];

        $payment->setDetails($details);
        $this->em->persist($originalPayment);
        $this->em->persist($payment);
        $this->em->flush();

        $operation = $this->gatewayClient->requestMaintenanceOperation(OperationEnum::CAPTURE, $reference, $amount);

        $payment->setResponse($this->modelToResponse($operation));
        $this->em->persist($payment);
        $this->em->flush();

        return $this->modelToHumanStatus($operation, $payment);
    }

    protected function processPayment(Client $client, $amount, $data, $isSubscription, $authorize = false)
    {
        $payment = new Payment;
        $payment->setTPE($this->getTPE());
        $payment->setNumber(uniqid());
        $payment->setCurrencyCode('EUR');
        $payment->setTotalAmount($amount * 100);
        $payment->setDescription('Hipay '.($authorize ? 'authorization' : 'payment'));
        $payment->setClientId($client->getId());
        $payment->setClientEmail($client->getMail());

        $details = [
            'orderid' => null,
            'payment_product' => 'cb',
            'currency' => 'EUR',
            'cid' => $client->getId(),
            'ipaddr' => '127.0.0.1',
            'language' => 'fr_FR',
            'firstname' => $client->getPrenom(),
            'lastname' => $client->getNom(),
            'country' => 'FR'
        ];

        $payment->setDetails($details);

        try {
            $recurringPayment = false;

            if ($data instanceof CreditCard) {
                $details['credit_card'] = '################';

                $paymentCardToken = $this->generatePaymentCardToken($data);

                $expirationDate = clone $data->getExpireAt();
            } else if ($data instanceof PaymentAlias) {
                $recurringPayment = true;
                $payment->setPaymentAlias($data);

                $pctMapper = new PaymentCardTokenMapper($data->getDetails());
                $paymentCardToken = $pctMapper->getModelObjectMapped();

                // TODO: relancer hipay sur le souci de cvc not found quand on réutilise les tokens
                // if card type does not allow token reusability
                //if (!in_array($paymentCardToken->getBrand(), ['VISA', 'MASTERCARD', 'AMERICAN EXPRESS'])) {
                    foreach ($data->getCartebancaires() as $cartebancaire) {
                        $recurringPayment = false;
                        $details['credit_card'] = '################';
                        $paymentCardToken = $this->generatePaymentCardToken($cartebancaire->toCreditCard($data->getClient()));
                        break;
                    }
                /*} else {
                    $details['token'] = $paymentCardToken->getToken();
                }*/
            } else {
                throw new \Exception(__METHOD__.' unsupported data');
            }
        } catch (RuntimeException $e) {
            $payment->setDetails($details);
            if ($e->getCode() > 0) {
                $payment->setException(json_encode(['code' => $e->getCode(), 'message' => $e->getMessage(), 'exception_details' => $e->__toString()]));
            }
            $this->em->persist($payment);
            $this->em->flush();

            $genericEx = new InvalidCardDataException($e->getMessage(), $e->getCode());
            $genericEx->setPayment($payment);
            throw $genericEx;
        }

        $payment->setDetails($details);
        $this->em->persist($payment);
        $this->em->flush();

        $orderRequest = new OrderRequest;
        $orderRequest->orderid = uniqid($details['orderid'] = $payment->getId());
        $orderRequest->operation = $authorize === true ? 'Authorization' : 'Sale';
        $orderRequest->payment_product = 'cb';
        $orderRequest->description = $authorize === true ? 'Hipay authorization' : 'Hipay sale';
        //$orderRequest->currency = 'EUR';
        $orderRequest->amount = $amount;
        $orderRequest->cid = $client->getId();
        $orderRequest->ipaddr = '127.0.0.1';
        $orderRequest->language = 'fr_FR';

        $customerBillingInfo = new CustomerBillingInfoRequest;
        $customerBillingInfo->email = $payment->getClientEmail();
        $customerBillingInfo->firstname = $client->getPrenom();
        $customerBillingInfo->lastname = $client->getNom();
        if (!$client->isChatClient()) {
            $customerBillingInfo->gender = $client->getGenre();

            if ($client->getDateNaissance()) {
                $customerBillingInfo->birthdate = $client->getDateNaissance()->format('Ymd');
            }
            if ($adresse = $client->getLastAdresse()) {
                if ($adresse->getVoie()) {
                    $customerBillingInfo->streetaddress = $adresse->getVoie();
                }
                if ($adresse->getComplement()) {
                    $customerBillingInfo->streetaddress2 = $adresse->getComplement();
                }
                if ($adresse->getCodepostal()) {
                    $customerBillingInfo->zipcode = $adresse->getCodepostal();
                }
                if ($adresse->getVille()) {
                    $customerBillingInfo->city = $adresse->getVille();
                }
            }
        }

        $paymentMethod = new CardTokenPaymentMethod;
        $paymentMethod->cardtoken = $paymentCardToken->getToken();
        if ($recurringPayment) {
            $paymentMethod->eci = 9;
        }
        if ($this->isMoto) {
            $paymentMethod->eci = $recurringPayment ? 2 : 1;
        } else {
            $paymentMethod->eci = $recurringPayment ? 9 : 7;
        }
        $paymentMethod->authentication_indicator = 0;

        $orderRequest->customerBillingInfo = $customerBillingInfo;
        $orderRequest->paymentMethod = $paymentMethod;

        try {
            $transaction = $this->gatewayClient->requestNewOrder($orderRequest);
        } catch (RuntimeException $e) {
            $this->rethrowRuntimeException($e, $payment);
        }

        $payment->setDetails($details);
        $payment->setResponse($this->modelToResponse($transaction));

        if ($reason = $transaction->getReason()) {
            if (in_array($reason['code'], [self::REASON_LOST_OR_STOLEN_CARD, self::REASON_RESTRICTED_CARD, self::REASON_CARD_BLACKLISTED])) {
                $payment->addTag(Payment::TAG_CBI);
            }
        }

        $this->em->persist($payment);
        $this->em->flush();

        $model = $this->modelToHumanStatus($transaction, $payment);

        // we store payment alias only if payment is successful
        if (isset($expirationDate) && ($model->isAuthorized() || $model->isCaptured())) {
            $payment->setPaymentAlias(
                $this->storePaymentAlias(
                    $client,
                    $data->getMaskedNumber(),
                    $paymentCardToken->toArray(),
                    $expirationDate->modify('last day of this month')
                )
            );
        }

        return $model;
    }

    public function cancel(Authorization $authorization, $amount = null)
    {
        $originalPayment = $authorization->getAuthorizePayment();
        $authorizeTransaction = $this->responseToModel($originalPayment->getResponse(), 'HiPay\Fullservice\Gateway\Mapper\TransactionMapper');

        $client = $authorization->getClient();

        $payment = new Payment;
        $payment->setTPE($this->getTPE());
        $payment->setNumber(uniqid());
        $payment->setCurrencyCode('EUR');
        $payment->setTotalAmount($amount * 100);
        $payment->setDescription('Hipay authorization cancel');
        $payment->setClientId($client->getId());
        $payment->setClientEmail($client->getMail());
        $payment->setOriginalPayment($originalPayment);
        $originalPayment->setLastPayment($payment);

        $details = [
            'operation' => OperationEnum::CANCEL,
            'transaction_reference' => $reference = $authorizeTransaction->getTransactionReference(),
            'amount' => $amount,
        ];

        $payment->setDetails($details);

        $this->em->persist($originalPayment);
        $this->em->persist($payment);
        $this->em->flush();

        try {
            $operation = $this->gatewayClient->requestMaintenanceOperation(OperationEnum::CANCEL, $reference, $amount);
        } catch (RuntimeException $e) {
            $this->rethrowRuntimeException($e, $payment);
        }

        $payment->setResponse($this->modelToResponse($operation));
        $this->em->persist($payment);
        $this->em->flush();

        return $this->modelToHumanStatus($operation, $payment);
    }

    public function refund(Payment $paymentToRefund, $amount)
    {
        $authorizeTransaction = $this->responseToModel($paymentToRefund->getResponse(), 'HiPay\Fullservice\Gateway\Mapper\OperationMapper');

        $payment = new Payment;
        $payment->setTPE($this->getTPE());
        $payment->setNumber(uniqid());
        $payment->setCurrencyCode('EUR');
        $payment->setTotalAmount($amount * 100);
        $payment->setDescription('Hipay payment refund');
        $payment->setClientId($paymentToRefund->getClientId());
        $payment->setClientEmail($paymentToRefund->getClientEmail());
        $payment->setOriginalPayment($paymentToRefund);
        $paymentToRefund->setLastPayment($payment);

        $details = [
            'operation' => OperationEnum::REFUND,
            'transaction_reference' => $reference = $authorizeTransaction->getTransactionReference(),
            'amount' => $amount
        ];

        $payment->setDetails($details);
        $this->em->persist($paymentToRefund);
        $this->em->persist($payment);
        $this->em->flush();

        try {
            $operation = $this->gatewayClient->requestMaintenanceOperation(OperationEnum::REFUND, $reference, $amount);
        } catch (RuntimeException $e) {
            $this->rethrowRuntimeException($e, $payment);
        }

        $payment->setResponse($this->modelToResponse($operation));
        $this->em->persist($payment);
        $this->em->flush();

        return $this->modelToHumanStatus($operation, $payment);
    }

    /**
     * @param RuntimeException $e
     * @param Payment $payment
     *
     * @throws PaymentFailedException
     */
    protected function rethrowRuntimeException(RuntimeException $e, Payment $payment)
    {
        if ($e->getCode() > 0) {
            $payment->setException(json_encode(['code' => $e->getCode(), 'message' => $e->getMessage(), 'exception_details' => $e->__toString()]));

            $genericEx = new PaymentFailedException($e->getMessage(), $e->getCode());
            $genericEx->setPayment($payment);
        }
        $this->em->persist($payment);
        $this->em->flush();

        throw isset($genericEx) ? $genericEx : $e;
    }

    /**
     * @param CreditCard
     *
     * @return PaymentCardToken
     */
    protected function generatePaymentCardToken(CreditCard $creditCard)
    {
        $generateTokenRequest = new GenerateTokenRequest;
        $generateTokenRequest->card_number = preg_replace('/[^\d]/s', '', $creditCard->getNumber());
        $generateTokenRequest->card_expiry_month = $creditCard->getExpireAt()->format('m');
        $generateTokenRequest->card_expiry_year = $creditCard->getExpireAt()->format('Y');
        $generateTokenRequest->card_holder = $creditCard->getFirstName().' '.$creditCard->getLastName();
        $generateTokenRequest->cvc = $creditCard->getSecurityCode();
        $generateTokenRequest->multi_use = 1;

        return $this->vaultClient->requestGenerateToken($generateTokenRequest);
    }

    /**
     * Convert model to human status
     *
     * @param AbstractTransaction $transaction
     * @param Payment $payment
     *
     * @return PaymentStatus
     */
    protected function modelToHumanStatus(AbstractTransaction $transaction, Payment $payment)
    {
        $status = new PaymentStatus($payment);

        switch ($transaction->getStatus()) {
            // TODO: think about a pending state updated with notification
            case TransactionStatus::AUTHORIZED:
                $status->markAuthorized();
                break;
            case TransactionStatus::CANCELLED:
                $status->markCanceled();
                break;
            case TransactionStatus::CAPTURE_REQUESTED:
            case TransactionStatus::CAPTURED:
                $status->markCaptured();
                break;
            case TransactionStatus::EXPIRED:
                $status->markExpired();
                break;
            case TransactionStatus::REFUND_REQUESTED:
            case TransactionStatus::REFUNDED:
                $status->markRefunded();
                break;
            case TransactionStatus::CHARGED_BACK:
                $status->markOpposed();
                break;
            case TransactionStatus::REFUSED:
            case TransactionStatus::BLOCKED:
            case TransactionStatus::DENIED:
            case TransactionStatus::AUTHORIZATION_REFUSED:
            case TransactionStatus::REFUND_REFUSED:
            case TransactionStatus::CAPTURE_REFUSED:
                $status->markFailed();
                break;
            default:
                $status->markUnknown();
        }

        return $status;
    }

    /**
     * Convert transaction to human status
     *
     * @param Transaction $transaction
     * @param Payment $payment
     *
     * @return PaymentStatus
     */
    protected function transactionToHumanStatus(Transaction $transaction, Payment $payment)
    {
        $status = new PaymentStatus($payment);

        switch ($transaction->getState()) {
            case 'completed':
                if ($authorize === true) {
                    $status->markAuthorized();
                } else {
                    $status->markCaptured();
                }
                break;
            case 'pending':
                $status->markPending();
                break;
            case 'declined':
                $status->markFailed();
                break;
            default:
                $status->markUnknown();
                break;
        }

        return $status;
    }

    /**
     * Convert model to payment response
     *
     * @param AbstractModel
     *
     * @return string
     */
    protected function modelToResponse(AbstractModel $model, $toString = true)
    {
        $result = [];
        foreach ($model->toArray() as $key => $value) {
            if ($value instanceof AbstractModel) {
                $result[$key] = $this->modelToResponse($value, false);
            } else {
                $result[$key] = $value;
            }
        }
        return $toString ? json_encode($result) : $result;
    }

    /**
     * Convert payment response string to model
     *
     * @param string
     *
     * @return Transaction
     */
    protected function responseToModel($responseStr, $mapperClass)
    {
        return (new $mapperClass(json_decode($responseStr, true)))->getModelObjectMapped();
    }

    public function notify()
    {
        if ((new Signature)->isValidHttpSignature($this->passPhrase) === false) {
            throw new InvalidSignatureException('Invalid integrity check');
        }

        $request = $this->requestStack->getCurrentRequest();

        $transaction = (new TransactionMapper($request->request->all()))->getModelObjectMapped();

        $originalPayment = $this->em->getRepository('KGCPaymentBundle:Payment')
            ->findOneByClientIdAndResponse($transaction->getOrder()->getCustomerId(), '%"transactionReference":"'.$transaction->getTransactionReference().'"%');

        if ($originalPayment instanceof Payment) {
            $payment = new Payment;
            $payment->setTPE($originalPayment->getTPE());
            $payment->setNumber(uniqid());
            $payment->setCurrencyCode('EUR');
            $payment->setTotalAmount(0);
            $payment->setDescription('Hipay notification');
            $payment->setClientId($originalPayment->getClientId());
            $payment->setClientEmail($originalPayment->getClientEmail());
            $payment->setDetails([
                'operation' => 'notification',
                'transaction_reference' => $reference = $transaction->getTransactionReference()
            ]);
            $payment->setResponse($this->modelToResponse($transaction));

            $payment->setOriginalPayment($originalPayment);
            $originalPayment->setLastPayment($payment);

            $this->em->persist($payment);
            $this->em->persist($originalPayment);
            $this->em->flush();

            return $this->modelToHumanStatus($transaction, $payment);
        } else {
            return null;
        }
    }

    public function getPaymentException(Payment $payment = null)
    {
        if ($payment === null || $payment->getResponse() === null) {
            if ($payment) {
                $exception = json_decode($payment->getException(), true);
            }

            if (isset($exception['exception_details'])) {
                return new PaymentFailedException($exception['message'], $exception['code']);
            } else {
                return parent::getPaymentException();
            }
        }

        try {
            $transaction = $this->responseToModel($payment->getResponse(), 'HiPay\Fullservice\Gateway\Mapper\TransactionMapper');

            if ($reason = $transaction->getReason()) {
                $errorMessage = $reason['code'].' - '.$reason['message'];
            } else {
                $errorMessage = $transaction->getStatus().' - '.$transaction->getMessage();
            }

            switch ($transaction->getStatus()) {
                // BLOCKED The transaction has been rejected for reasons of suspected fraud.
                case TransactionStatus::BLOCKED:
                // DENIED Merchant denied the payment attempt.
                case TransactionStatus::DENIED:
                    $exception = new PaymentSuspiciousException($errorMessage);
                    break;
                // EXPIRED The validity period of the payment authorization has expired.
                case TransactionStatus::EXPIRED:
                    $exception = new AuthorizationExpiredException($errorMessage);
                    break;
                // REFUSED The financial institution refused to authorize the payment.
                case TransactionStatus::REFUSED:
                // AUTHORIZATION_REFUSED The authorization was refused by the financial institution
                case TransactionStatus::AUTHORIZATION_REFUSED:
                // REFUND_REFUSED The refund operation was refused by the financial institution
                case TransactionStatus::REFUND_REFUSED:
                // CAPTURE_REFUSED The capture was refused by the financial institution.
                case TransactionStatus::CAPTURE_REFUSED:
                    $exception = new PaymentRefusedException($errorMessage);
                    break;
                case TransactionStatus::UNABLE_TO_AUTHENTICATE:
                case TransactionStatus::COULD_NOT_AUTHENTICATE:
                case TransactionStatus::AUTHENTICATION_FAILED:
                    $exception = new AuthenticationFailedException($errorMessage);
                    break;
                default:
                    $exception = new PaymentFailedException($errorMessage);
            }
        } catch (\Exception $e) {
            $exception = new PaymentFailedException($e->getMessage());
        }

        return $exception;
    }

    protected function getModelForPaymentUtils(Payment $payment)
    {
        static $models = [];

        if (empty($models[$id = $payment->getId()])) {
            if ($payment->getResponse()) {
                $models[$id] = json_decode($payment->getResponse(), true);
            } else if ($exception = $payment->getException()) {
                $details = json_decode($exception, true);
                if (isset($details['exception_details'])) {
                    $models[$id] = $details;
                }
            }
        }

        return isset($models[$id]) ? $models[$id] : null;
    }

    /**
     * @inheritDoc
     */
    public function getPaymentBoUrl(Payment $payment)
    {
        $model = $this->getModelForPaymentUtils($payment);

        if (isset($model['transactionReference'])) {
            return 'https://merchant.hipay-tpp.com/maccount/'.$this->account.'/transaction/detail/index/trxid/'.$model['transactionReference'];
        } else {
            return null;
        }
    }

    /**
     * @inheritDoc
     */
    public function getPaymentDetails(Payment $payment)
    {
        $model = $this->getModelForPaymentUtils($payment);

        if (isset($model['transactionReference'])) {
            $response = 'Transaction ref: '.$model['transactionReference']."\nCode retour: ".$model['status'].' - '.$model['message'];
            if ($reason = isset($model['reason']) ? $model['reason'] : null) {
                $response .= "\nErreur: ".$reason['code'].' - '.$reason['message'];
            }

            return $response;
        } else if (isset($model['exception_details'])) {
            return "Impossible de procéder au paiement\nErreur: ".$model['code'].' - '.$model['message'];
        } else {
            return 'Réponse du paiement invalide';
        }
    }

    /**
     * @inheritdoc
     */
    public function getFirstMonthNextReceiptDays()
    {
        return [1, 2, 4, 5, 6, 10, 12, 25, 'end'];
    }

    /**
     * @inheritdoc
     */
    public function getOtherMonthsNextReceiptDays()
    {
        return [2, 3, 5, 8, 10, 25, 'end'];
    }

    /**
     * @inheritdoc
     */
    public function getAllowedConsecutiveFails()
    {
        return 20;
    }
}
