<?php

namespace KGC\PaymentBundle\Service\Payment;

use Payum\Core\Request\GetHumanStatus;

class PaymentStatus extends GetHumanStatus
{
    const STATUS_OPPOSED = 'opposed';

    /**
     * {@inheritDoc}
     */
    public function markOpposed()
    {
        $this->status = static::STATUS_OPPOSED;
    }

    /**
     * {@inheritDoc}
     */
    public function isOpposed()
    {
        return $this->isCurrentStatusEqualTo(static::STATUS_OPPOSED);
    }

    /**
     * @param GetHumanStatus $status
     *
     * @return PaymentStatus
     */
    public static function toPaymentStatus(GetHumanStatus $status = null)
    {
        if ($status === null) {
            return null;
        }

        $paymentStatus = new self($status->getFirstModel());
        $paymentStatus->setModel($status->getModel());

        switch ($status->getValue())
        {
            case GetHumanStatus::STATUS_CAPTURED:
                $paymentStatus->markCaptured();
                break;
            case GetHumanStatus::STATUS_AUTHORIZED:
                $paymentStatus->markAuthorized();
                break;
            case GetHumanStatus::STATUS_REFUNDED:
                $paymentStatus->markRefunded();
                break;
            case GetHumanStatus::STATUS_FAILED:
                $paymentStatus->markFailed();
                break;
            case GetHumanStatus::STATUS_SUSPENDED:
                $paymentStatus->markSuspended();
                break;
            case GetHumanStatus::STATUS_EXPIRED:
                $paymentStatus->markExpired();
                break;
            case GetHumanStatus::STATUS_PENDING:
                $paymentStatus->markPending();
                break;
            case GetHumanStatus::STATUS_CANCELED:
                $paymentStatus->markCanceled();
                break;
            case GetHumanStatus::STATUS_NEW:
                $paymentStatus->markNew();
                break;
            default: //case GetHumanStatus::STATUS_UNKNOWN:
                $paymentStatus->markUnknown();
        }

        return $paymentStatus;
    }
}