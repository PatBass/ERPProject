<?php

namespace KGC\CommonBundle\Service;

use JMS\DiExtraBundle\Annotation as DI;
use KGC\CommonBundle\Logger\ConsoleOutputLoggerTrait;
use KGC\CommonBundle\Traits\NextReceiptDate;
use KGC\PaymentBundle\Entity\Payment;
use KGC\PaymentBundle\Exception\Payment\PaymentFailedException;
use KGC\PaymentBundle\Service\Payment\Factory as PaymentFactory;
use KGC\RdvBundle\Entity\Dossier;
use KGC\RdvBundle\Entity\Encaissement;
use KGC\RdvBundle\Entity\Etiquette;
use KGC\RdvBundle\Entity\RDV;
use KGC\RdvBundle\Entity\TPE;
use KGC\RdvBundle\Exception\Payment\CardException;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

/**
 * Class UnpaidCalculator
 * @package KGC\CommonBundle\Service
 *
 * @DI\Service("kgc.unpaid.calculator")
 * @DI\Tag("monolog.logger", attributes = {"channel" = "unpaid"})
 */
class UnpaidCalculator
{
    use NextReceiptDate;
    use ConsoleOutputLoggerTrait;

    const SMALLEST_AMOUNT_DIVISOR = 2;
    const SMALLEST_AMOUNT_MINIMUM = 50;
    const AMOUNT_PRECISION = 2;
    const DECIMAL_DIFF_ALLOWED = 0.0000000001;
    const UNPAID_GATEWAY = TPE::PAYMENT_GATEWAY_HIPAY;

    protected $dryRun = false;

    protected $em;

    protected $encaissementRepository;

    protected $rdvManager;

    protected $paymentManager;

    /**
     * @var PaymentFactory
     */
    protected $paymentFactory;

    /**
     * @param $entityManager
     * @param $rdvManager
     * @param $paymentManager
     *
     * @DI\InjectParams({
     *     "entityManager" = @DI\Inject("doctrine.orm.default_entity_manager"),
     *     "rdvManager" = @DI\Inject("kgc.rdv.manager"),
     *     "paymentManager" = @DI\Inject("kgc.rdv.payment_manager"),
     *     "paymentFactory"  = @DI\Inject("kgc.payment.factory")
     * })
     */
    public function __construct($entityManager, $rdvManager, $paymentManager, $paymentFactory, LoggerInterface $logger)
    {
        $this->em = $entityManager;
        $this->encaissementRepository = $entityManager->getRepository('KGCRdvBundle:Encaissement');
        $this->rdvManager = $rdvManager;
        $this->paymentManager = $paymentManager;
        $this->paymentFactory = $paymentFactory;
        $this->setLogger($logger);
    }

    /**
     * Really persist receipts into database or not
     *
     * @param $dryRun
     */
    public function setDryRun($dryRun)
    {
        $this->dryRun = $dryRun;
    }

    protected function getTpeForReceipt()
    {
        static $tpe = null;

        if ($tpe === null) {
            $tpe = $this->em->getRepository('KGCRdvBundle:TPE')->findOneByPaymentGateway(self::UNPAID_GATEWAY);

            if (! $tpe instanceof TPE) {
                throw new \Exception('No active tpe with payment gateway available');
            }
        }

        return $tpe;
    }

    /**
     * Tries to process a receipt with a TPE for a CB/client
     *
     * @param Encaissement $receipt
     * @param $amount
     *
     * @return bool
     */
    protected function processBankReceipt(Encaissement $receipt, $amount)
    {
        static $delayNeeded = false;

        $rdv = $receipt->getConsultation();
        $carteBancaire = null;

        // get last non forbidden card
        foreach ($rdv->getCartebancaires() as $rdvCb) {
            $this->paymentManager->decryptCb($rdvCb);
            if ($rdvCb->isValid()) {
                $carteBancaire = $rdvCb;
            }
        }

        if ($carteBancaire === null) {
            throw new CardException('No valid card found for receipt #'.$receipt->getId());
        }

        $tpe = $this->getTpeForReceipt();

        if (($expectedDelay = $tpe->getDelay()) && $delayNeeded === true) {
            sleep($expectedDelay);
        }

        $receipt->setTpe($tpe);

        try {
            $status = $this->paymentManager->payWithCartebancaire($rdv->getClient(), $carteBancaire, $tpe->getPaymentGateway(), $amount);

            $receipt->setPayment($status->getFirstModel());
        } catch (PaymentFailedException $e) {
            if ($payment = $e->getPayment()) {
                $receipt->setPayment($payment);

                $this->em->persist($receipt);
                $this->em->flush();
            }

            throw $e;
        }
        $receipt->setFromBatch(true);

        if ($expectedDelay && $delayNeeded === false) {
            $delayNeeded = true;
        }

        return $status->isCaptured();
    }

    protected function applyDateRuleForMonth($monthDiffCount, $dateE, $dateC, $month, callable $dateCalculator)
    {
        if ($monthDiffCount < $month) {
            return ['finished' => false , 'date' => $dateCalculator()];
        }

        return null;
    }

    protected function setReceiptProcessStatusFinished(Encaissement $enc, $state)
    {
        $enc->setDate(new \DateTime);
        $enc->setEtat($state);

        if ($state === Encaissement::DONE) {
            $enc->setPsychicAsso(true);
        }

        $this->em->persist($enc);
        $this->em->flush();
    }

    protected function cloneReceipt(Encaissement $enc, $amount, $status = null, \DateTime $date = null)
    {
        $e = clone $enc;
        $e->setId(null);
        $e->setMontant($amount);
        $e->setDate($date);
        $e->setEtat($status ?: Encaissement::PLANNED);
        $e->setPsychicAsso(true);
        $e->setPayment(null);

        // if status is planned, we remove reference to payment
        // otherwise, we keep it, because original receipt related payment has been updated
        if ($status == Encaissement::PLANNED) {
            $e->setFromBatch(false);
        }

        return $e;
    }


    /**
     * Return all receipts for today whose date is post consultation date.
     * Result set can be limited
     *
     * @param \DateTime $date
     * @param $limit
     * @return mixed
     */
    public function getAllReceiptsToProcess(\DateTime $date, $limit)
    {
        $toProcess = $this->encaissementRepository->getForBatchUnpaidProcess($date, $limit);

        return $toProcess;
    }

    public function setRdvProcessStatusProcessing(RDV $rdv)
    {
        $rdv->setBatchStatus(RDV::BATCH_PROCESSING);
        $this->em->persist($rdv);
        $this->em->flush();
    }

    public function setRdvProcessStatusFinished(RDV $rdv)
    {
        $rdv->setBatchStatus(null);
        $rdv->setBatchEndDate(new \DateTime('now'));

        $this->rdvManager->processBillingChanges($rdv, false);

        $this->em->persist($rdv);
        $this->em->flush();
    }

    public function setRdvAsAbandoned(Rdv $rdv, $persist = false)
    {
        $classement = $this->em->getRepository('KGCRdvBundle:Dossier')->findOneByIdcode(Dossier::ABANDON);

        $rdv->setClassement($classement);

        if ($persist) {
            $this->em->persist($rdv);
            $this->em->flush();
        }
    }

    public function setRdvAsCBI(Rdv $rdv, $persist = false)
    {
        $classement = $this->em->getRepository('KGCRdvBundle:Dossier')->findOneByIdcode(Dossier::LITIGE);
        $etiquette = $this->em->getRepository('KGCRdvBundle:Etiquette')->findOneByIdcode(Etiquette::CBI);

        $rdv->setClassement($classement);
        $rdv->addEtiquettes($etiquette);

        if ($persist) {
            $this->em->persist($rdv);
            $this->em->flush();
        }
    }

    public function planAnotherReceipt(Encaissement $enc, \DateTime $dateC, $amount)
    {
        $gateway = $this->paymentFactory->get(self::UNPAID_GATEWAY);
        $next = $this->getNextReceiptDate($gateway, $dateC, $enc->getDate());

        // If we are processing old receipts, the next planned date cannot be today or before,
        // because we will never process them anymore.
        $today = new \DateTime('now');
        $next['date'] = $next['date'] <= $today ? new \DateTime('tomorrow') : $next['date'];

        if (isset($next['date'])) {
            $e = $this->cloneReceipt($enc, $amount, Encaissement::PLANNED, $next['date']);
            $this->em->persist($e);
            $this->em->flush();

            return true;
        }

        return false;
    }

    public function postponeReceipt(Encaissement $enc)
    {
        $encDate = clone $enc->getDate();
        $gateway = $this->paymentFactory->get(self::UNPAID_GATEWAY);
        $next = $this->getNextReceiptDate($gateway, $enc->getConsultation()->getDateConsultation(), $encDate);

        if (isset($next['date'])) {
            $enc->setDate($next['date']);

            $this->em->persist($enc);
            $this->em->flush();

            return true;
        }

        return false;
    }

    /*protected function getAmountToProcess($receipt)
    {
        $previousReceipt = $this->em->getRepository('KGCRdvBundle:Encaissement')->getPreviousReceiptProcessedFromBatch($receipt);

        if (!$previousReceipt || $previousReceipt->getEtat()) {
            return $receipt->getMontant();
        } else {
            return min();
        }
    }*/

    protected function getPreviousReceiptProcessedFromBatch(Encaissement $receipt)
    {
        return $this->em->getRepository('KGCRdvBundle:Encaissement')->getPreviousReceiptProcessedFromBatch($receipt);
    }

    /**
     * Process a receipt.
     * Everything is done here to try smaller amounts.
     * Return the amount processed and the amount left (not processed successfully)
     *
     * @param Encaissement $receipt
     * @param $amount
     * @return array
     */
    public function processReceipt(Encaissement $receipt)
    {
        $previousReceipt = $this->getPreviousReceiptProcessedFromBatch($receipt);

        if ($previousReceipt === null || $previousReceipt->getEtat() === Encaissement::DONE) {
            $amount = $receipt->getMontant();
        } else if ($previousReceipt->getDate() < new \DateTime('today')) {
            $amount = min($receipt->getMontant(), self::SMALLEST_AMOUNT_MINIMUM);
        } else {
            return false;
        }

        $amountLeftTotal = $amountTotal = $receipt->getMontant();
        $amountToTry = $amount;
        $processStatus = $this->processBankReceipt($receipt, $amountToTry);
        $amountLeftTotal = doubleval($processStatus ? $amountLeftTotal - $amountToTry : $amountLeftTotal);

        // For the first process we need to update the current receipt object.
        // For other attempts we will clone the object to create new receipts.
        if ($processStatus) {
            $receipt->setMontant($amountToTry);
            $this->setReceiptProcessStatusFinished($receipt, Encaissement::DONE);
        } else {
            $this->setReceiptProcessStatusFinished($receipt, Encaissement::DENIED);

            // if payment is a CBI
            if ($receipt->getPayment()->hasTag(Payment::TAG_CBI)) {
                // we return directly without trying again
                return [
                    'amount_processed' => 0,
                    'amount_left' => $amountLeftTotal,
                ];
            }
        }

        // if the process is NOT GOOD we try with minimal amount.
        if (!$processStatus && $amountToTry > self::SMALLEST_AMOUNT_MINIMUM) {
            $e = $this->cloneReceipt($receipt,  $amountToTry = self::SMALLEST_AMOUNT_MINIMUM);

            $processStatus = $this->processBankReceipt($e, $amountToTry);
            $e->setBatchRetryFrom($receipt);
            $this->setReceiptProcessStatusFinished($e, $processStatus ? Encaissement::DONE : Encaissement::DENIED);

            $amountLeftTotal = doubleval($processStatus ? $amountLeftTotal - $amountToTry : $amountLeftTotal);
        }

        $amountProcessed = doubleval($amountTotal - $amountLeftTotal);

        // If a receipt is processed successfully for a smallest amount (previous loop),
        // We want to try/process N times this smallest amount to get the total amount.
        if ($amountProcessed > 0 && $amountLeftTotal >= self::SMALLEST_AMOUNT_MINIMUM) {
            $loop = ceil($amountLeftTotal / $amountProcessed);
            while($loop > 0 && $processStatus) {
                $amountToTry = min($amountLeftTotal, self::SMALLEST_AMOUNT_MINIMUM);
                $e = $this->cloneReceipt($receipt,  $amountToTry);

                $processStatus = $this->processBankReceipt($e, $amountToTry);
                $e->setBatchRetryFrom($receipt);
                $this->setReceiptProcessStatusFinished($e, $processStatus ? Encaissement::DONE : Encaissement::DENIED);

                $amountLeftTotal = doubleval($processStatus ? $amountLeftTotal - $amountToTry : $amountLeftTotal);
                $loop--;
            }
        }

        $amountProcessed = doubleval($amountTotal - $amountLeftTotal);
        $amountLeftTotal = doubleval($amountTotal - $amountProcessed);

        if ($amountLeftTotal < self::DECIMAL_DIFF_ALLOWED) {
            $amountLeftTotal = doubleval(0);
            $amountProcessed = doubleval($amountTotal);
        }

        return [
            'amount_processed' => $amountProcessed,
            'amount_left' => $amountLeftTotal,
        ];
    }
}
