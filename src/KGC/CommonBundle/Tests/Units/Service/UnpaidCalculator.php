<?php

namespace KGC\CommonBundle\Tests\Units\Service;

use Doctrine\ORM\EntityManagerInterface;
use KGC\CommonBundle\Service\UnpaidCalculator as testedClass;
use atoum\test;
use KGC\PaymentBundle\Entity\Payment;
use KGC\RdvBundle\Entity\Encaissement;
use KGC\RdvBundle\Tests\Units\Mock\Service\CarteBancairePayMock;
use KGC\RdvBundle\Tests\Units\Mock\Service\RdvManagerMock;
use Symfony\Component\HttpKernel\Log\NullLogger;

/**
 * Class UnpaidCalculatorBase
 * @package KGC\CommonBundle\Tests\Units\Service
 */
class UnpaidCalculatorBase extends testedClass
{
    public $processBankReceiptValue = false;
    public $processBankReceiptReInit = false;

    public $cloneReceiptValue = false;
    public $cloneReceiptReInit = false;

    protected $cloneReceiptVar = 0;

    protected $previousReceiptFromBatch = null;

    /**
     * Make this method public
     */
    public function getMonthsDiff(\Datetime $baseDate, \Datetime $date)
    {
        return parent::getMonthsDiff($baseDate, $date);
    }

    public function smallestAmount($initialAmount)
    {
        return parent::smallestAmount($initialAmount);
    }

    public function getNextReceiptDate(\DateTime $dateC, \DateTime $dateE, \DateTime $previous = null)
    {
        return parent::getNextReceiptDate($dateC, $dateE, $previous);
    }

    protected function getPreviousReceiptProcessedFromBatch(Encaissement $receipt)
    {
        return $this->previousReceiptFromBatch;
    }

    public function setPreviousReceiptProcessedFromBatch(Encaissement $receipt = null)
    {
        $this->previousReceiptFromBatch = $receipt;
    }

    public function processBankReceipt(Encaissement $receipt, $amount)
    {
        $receipt->setPayment(new Payment);

        if (is_bool($this->processBankReceiptValue)) {
            return $this->processBankReceiptValue;
        }

        if (is_array($this->processBankReceiptValue)) {
            static $var = 0;
            if ($this->processBankReceiptReInit) {
                $this->processBankReceiptReInit = false;
                $var = 0;
            }
            $var++;

            return in_array($var, $this->processBankReceiptValue);
        }

        return false;
    }

    protected function cloneReceipt(Encaissement $enc, $amount, $status = null, \DateTime $date = null)
    {
        if (is_array($this->cloneReceiptValue)) {
            if ($this->cloneReceiptReInit) {
                $this->cloneReceiptReInit = false;
                $this->cloneReceiptVar = 0;
            }

            list($expectedAmount, $expectedStatus) = $this->cloneReceiptValue[$this->cloneReceiptVar];

            if ($amount != $expectedAmount) {
                throw new \Exception(
                    sprintf('Cloned receipt amount %f€, expected %f€ for item %d', $amount, $expectedAmount, $this->cloneReceiptVar)
                );
            }

            if ($status != $expectedStatus) {
                throw new \Exception(
                    sprintf('Cloned receipt status %s, expected %s for item %d', $status, $expectedStatus, $this->cloneReceiptVar)
                );
            }

            ++$this->cloneReceiptVar;
        }

        return parent::cloneReceipt($enc, $amount, $status, $date);
    }

    public function checkCloneReceiptCountValid()
    {
        return !is_array($this->cloneReceiptValue) || (count($this->cloneReceiptValue) == $this->cloneReceiptVar);
    }
}

class UnpaidCalculator extends test
{
    protected function getUnpaidCalculator()
    {
        return new UnpaidCalculatorBase(
            new \mock\Doctrine\ORM\EntityManagerInterface,
            new RdvManagerMock,
            new CarteBancairePayMock,
            new NullLogger
        );
    }

    public function testGetMonthsDiff()
    {
        $this
            ->given($calculator = $this->getUnpaidCalculator())

            ->and($startDate = new \DateTime('2015-03-05'))
            ->and($endDate = new \DateTime('2015-03-15'))
            ->and($diff = $calculator->getMonthsDiff($startDate, $endDate))
            ->then
                ->integer($diff)->isIdenticalTo(0)

            ->and($startDate = new \DateTime('2015-03-05'))
            ->and($endDate = new \DateTime('2015-05-15'))
            ->and($diff = $calculator->getMonthsDiff($startDate, $endDate))
            ->then
                ->integer($diff)->isIdenticalTo(2)

            ->and($startDate = new \DateTime('2015-05-15'))
            ->and($endDate = new \DateTime('2015-03-05'))
            ->and($diff = $calculator->getMonthsDiff($startDate, $endDate))
            ->then
                ->integer($diff)->isIdenticalTo(2)

            ->and($startDate = new \DateTime('2015-10-05'))
            ->and($endDate = new \DateTime('2016-02-01'))
            ->and($diff = $calculator->getMonthsDiff($startDate, $endDate))
            ->then
                ->integer($diff)->isIdenticalTo(3)

            ->and($startDate = new \DateTime('2015-01-01'))
            ->and($endDate = new \DateTime('2018-01-01'))
            ->and($diff = $calculator->getMonthsDiff($startDate, $endDate))
            ->then
                ->integer($diff)->isIdenticalTo(36)
        ;
    }

    public function testGetNextReceiptDate()
    {
        $this
            ->given($calculator = $this->getUnpaidCalculator())

            ->and($dateC = new \DateTime('2016-01-01'))
            ->and($dateE = new \DateTime('2016-01-04'))
            ->and($next = $calculator->getNextReceiptDate($dateC, $dateE))
            ->then
                ->dateTime($next['date'])->isEqualTo(new \DateTime('2016-01-05'))
                ->boolean($next['finished'])->isEqualTo(false)

            //->and($dateC = new \DateTime('2016-01-01'))
            ->and($dateE = new \DateTime('2016-01-05'))
            ->and($next = $calculator->getNextReceiptDate($dateC, $dateE))
            ->then
                ->dateTime($next['date'])->isEqualTo(new \DateTime('2016-01-06'))
                ->boolean($next['finished'])->isEqualTo(false)

            //->and($dateC = new \DateTime('2016-01-01'))
            ->and($dateE = new \DateTime('2016-01-06'))
            ->and($next = $calculator->getNextReceiptDate($dateC, $dateE))
            ->then
                ->dateTime($next['date'])->isEqualTo(new \DateTime('2016-01-10'))
                ->boolean($next['finished'])->isEqualTo(false)

            //->and($dateC = new \DateTime('2016-01-01'))
            ->and($dateE = new \DateTime('2016-01-10'))
            ->and($next = $calculator->getNextReceiptDate($dateC, $dateE))
            ->then
                ->dateTime($next['date'])->isEqualTo(new \DateTime('2016-01-12'))
                ->boolean($next['finished'])->isEqualTo(false)

            //->and($dateC = new \DateTime('2016-01-01'))
            ->and($dateE = new \DateTime('2016-01-25'))
            ->and($next = $calculator->getNextReceiptDate($dateC, $dateE))
            ->then
                ->dateTime($next['date'])->isEqualTo(new \DateTime('2016-01-30'))
                ->boolean($next['finished'])->isEqualTo(false)

            //->and($dateC = new \DateTime('2016-01-01'))
            ->and($dateE = new \DateTime('2016-01-31'))
            ->and($next = $calculator->getNextReceiptDate($dateC, $dateE))
            ->then
                ->dateTime($next['date'])->isEqualTo(new \DateTime('2016-02-05'))
                ->boolean($next['finished'])->isEqualTo(false)

            //->and($dateC = new \DateTime('2016-01-01'))
            ->and($dateE = new \DateTime('2016-02-04'))
            ->and($next = $calculator->getNextReceiptDate($dateC, $dateE))
            ->then
                ->dateTime($next['date'])->isEqualTo(new \DateTime('2016-02-05'))
                ->boolean($next['finished'])->isEqualTo(false)

            //->and($dateC = new \DateTime('2016-01-01'))
            ->and($dateE = new \DateTime('2016-02-05'))
            ->and($next = $calculator->getNextReceiptDate($dateC, $dateE))
            ->then
                ->dateTime($next['date'])->isEqualTo(new \DateTime('2016-02-10'))
                ->boolean($next['finished'])->isEqualTo(false)

            //->and($dateC = new \DateTime('2016-01-01'))
            ->and($dateE = new \DateTime('2016-02-10'))
            ->and($next = $calculator->getNextReceiptDate($dateC, $dateE))
            ->then
                ->dateTime($next['date'])->isEqualTo(new \DateTime('2016-02-29'))
                ->boolean($next['finished'])->isEqualTo(false)

            //->and($dateC = new \DateTime('2016-01-01'))
            ->and($dateE = new \DateTime('2016-02-29'))
            ->and($next = $calculator->getNextReceiptDate($dateC, $dateE))
            ->then
                ->dateTime($next['date'])->isEqualTo(new \DateTime('2016-03-05'))
                ->boolean($next['finished'])->isEqualTo(false)

            //->and($dateC = new \DateTime('2016-01-01'))
            ->and($dateE = new \DateTime('2016-03-05'))
            ->and($next = $calculator->getNextReceiptDate($dateC, $dateE))
            ->then
                ->dateTime($next['date'])->isEqualTo(new \DateTime('2016-03-10'))
                ->boolean($next['finished'])->isEqualTo(false)

            //->and($dateC = new \DateTime('2016-01-01'))
            ->and($dateE = new \DateTime('2016-03-10'))
            ->and($next = $calculator->getNextReceiptDate($dateC, $dateE))
            ->then
                ->dateTime($next['date'])->isEqualTo(new \DateTime('2016-03-30'))
                ->boolean($next['finished'])->isEqualTo(false)

            //->and($dateC = new \DateTime('2016-01-01'))
            ->and($dateE = new \DateTime('2016-03-31'))
            ->and($next = $calculator->getNextReceiptDate($dateC, $dateE))
            ->then
                ->dateTime($next['date'])->isEqualTo(new \DateTime('2016-04-05'))
                ->boolean($next['finished'])->isEqualTo(true)
        ;
    }

    public function testProcessReceipt()
    {
        $this
            ->given($calculator = $this->getUnpaidCalculator())
            ->and($amount = 500)
            ->and($data = (new Encaissement())->setMontant($amount))
            ->and($result = $calculator->processReceipt($data, $amount))
            ->then
                ->array($result)->isIdenticalTo([
                'amount_processed' => doubleval(0),
                'amount_left' => doubleval(500),
            ])
        ;

        $calculator->processBankReceiptValue = true;
        $this
            ->and($result = $calculator->processReceipt($data))
            ->then
                ->array($result)->isIdenticalTo([
                    'amount_processed' => doubleval(500),
                    'amount_left' => doubleval(0),
                ])
        ;

        $calculator->processBankReceiptValue = [2];
        $calculator->processBankReceiptReInit = true;
        $this
            ->and($result = $calculator->processReceipt($data))
            ->then
                ->array($result)->isIdenticalTo([
                    'amount_processed' => doubleval(50),
                    'amount_left' => doubleval(450),
                ])
        ;

        $calculator->processBankReceiptValue = [2, 3, 4];
        $calculator->processBankReceiptReInit = true;
        $this
            ->and($result = $calculator->processReceipt($data))
            ->then
                ->float($result['amount_processed'])->isNearlyEqualTo(doubleval(150))
                ->float($result['amount_left'])->isNearlyEqualTo(doubleval(350))
                ->float($result['amount_left']+$result['amount_processed'])->isEqualTo($amount)
        ;

        $calculator->processBankReceiptValue = true;
        $this
            ->given($calculator->setPreviousReceiptProcessedFromBatch((new Encaissement)->setEtat(Encaissement::DONE)))
            ->and($result = $calculator->processReceipt($data))
            ->then
                ->float($result['amount_processed'])->isNearlyEqualTo(doubleval(500))
                ->float($result['amount_left'])->isNearlyEqualTo(doubleval(0))
                ->float($result['amount_left']+$result['amount_processed'])->isEqualTo($amount)
        ;

        $calculator->processBankReceiptValue = [1, 2, 3, 4];
        $calculator->processBankReceiptReInit = true;
        $this
            ->given($calculator->setPreviousReceiptProcessedFromBatch((new Encaissement)->setEtat(Encaissement::DENIED)->setDate(new \DateTime('yesterday'))))
            ->and($result = $calculator->processReceipt($data))
            ->then
                ->integer($data->getMontant())->isEqualTo(50)
                ->float($result['amount_processed'])->isNearlyEqualTo(doubleval(200))
                ->float($result['amount_left'])->isNearlyEqualTo(doubleval(300))
                ->float($result['amount_left']+$result['amount_processed'])->isEqualTo($amount)
        ;

        $this
            ->given($calculator->setPreviousReceiptProcessedFromBatch((new Encaissement)->setEtat(Encaissement::DENIED)->setDate(new \DateTime('today'))))
            ->and($data->setMontant(500))
            ->and($result = $calculator->processReceipt($data))
            ->then
                ->boolean($result)->isEqualTo(false)
                ->integer($data->getMontant())->isEqualTo(500)
        ;

        $calculator->processBankReceiptValue = [2];
        $calculator->processBankReceiptReInit = true;

        $calculator->cloneReceiptValue = [[50, null]];
        $calculator->cloneReceiptReInit = true;

        $data->setMontant($amount = 52);

        $this
            ->given($calculator->setPreviousReceiptProcessedFromBatch(null))
            ->and($result = $calculator->processReceipt($data))
            ->then
                ->float($result['amount_processed'])->isNearlyEqualTo(doubleval(50))
                ->float($result['amount_left'])->isNearlyEqualTo(doubleval(2))
                ->float($result['amount_left']+$result['amount_processed'])->isEqualTo($amount)
                ->boolean($calculator->checkCloneReceiptCountValid())->isEqualTo(true)
        ;
    }

}
