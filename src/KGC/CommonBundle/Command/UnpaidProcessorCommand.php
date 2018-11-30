<?php

namespace KGC\CommonBundle\Command;

use FOS\ElasticaBundle\Doctrine\Listener;
use KGC\CommonBundle\Service\UnpaidCalculator;
use KGC\PaymentBundle\Entity\Payment;
use KGC\PaymentBundle\Exception\Payment\PaymentFailedException;
use KGC\PaymentBundle\Service\Payment\Gateway\Exception\InvalidParameterException;
use KGC\RdvBundle\Entity\Encaissement;
use KGC\RdvBundle\Exception\Payment\CardException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UnpaidProcessorCommand extends ContainerAwareCommand
{
    const NAME = 'kgestion:unpaid:process';

    /**
     * Fields updated on RDV for this command must not trigger an elasticsearch reindexation.
     * So, we need to disable the listeners !
     */
    protected function removeElasticSearchListener()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $listeners = $em->getEventManager()->getListeners();
        $evm = $em->getEventManager();
        foreach ($listeners as $event => $listeners) {
            foreach ($listeners as $hash => $listener) {
                if ($listener instanceof Listener) {
                    $evm->removeEventListener(
                        ['postLoad', 'postUpdate', 'postPersist', 'preRemove', 'preFlush', 'postFlush'],
                        $listener
                    );
                }
            }
        }
    }

    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Process unpaid, planned or refused bank receipts')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Display what the command will do without doing it')
            ->addOption('date', null, InputOption::VALUE_OPTIONAL, 'Date of receipts to process (format: YYY-dd-mm)')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'The number of receipts to process');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->removeElasticSearchListener();

        $limit = $input->getOption('limit', 10);
        $date = $input->getOption('date', null);

        $dryRun = $input->getOption('dry-run', null);
        $dryRun = !!$dryRun;

        $date = null !== $date ? new \DateTime($date) : new \DateTime('today');

        if ($date > new \DateTime('now')) {
            throw new \Exception('Processing receipts planned in the future is not allowed');
        }

        $unpaidService = $this->getContainer()->get('kgc.unpaid.calculator');
        $unpaidService->setOutput($output);

        $receipts = $unpaidService->getAllReceiptsToProcess($date, $limit);

        $unpaidService->debug('Starting receipts processing', [
            'date' => $date->format('d/m/Y'),
            'count' => count($receipts),
        ]);

        // TODO : OU affiche t-on les process en erreur (Etat PROCESSING mais sans date de fin)

        foreach ($receipts as $receipt) {
            $rdv = $receipt->getConsultation();

            $unpaidService->notice('Processing new receipt', [
                'receipt' =>$receipt->getId(),
                'amount' => $receipt->getMontant(),
                'rdv' => $rdv->getId(),
                'client_id' => $rdv->getClient()->getId(),
                'client' => $rdv->getClient()->getFullName(),
            ]);

            if (!$dryRun) {
                $unpaidService->setRdvProcessStatusProcessing($receipt->getConsultation());

                try {
                    $date = $rdv->getDateConsultation();

                    $receiptResult = $unpaidService->processReceipt($receipt);

                    if ($receiptResult === false) {
                        // if receipt is skipped, postponed it to another date
                        $unpaidService->postponeReceipt($receipt);
                    } else {
                        if ($receipt->getPayment()->hasTag(Payment::TAG_CBI)) {
                            $unpaidService->setRdvAsCBI($rdv);
                        }

                        if ($receiptResult['amount_left'] > UnpaidCalculator::DECIMAL_DIFF_ALLOWED) {
                            if ($receipt->getEtat() == Encaissement::DENIED && $date <= new \DateTime('-3 month')) {
                                //$unpaidService->setRdvAsAbandoned($rdv);
                            } else {
                                $newPlanned = $unpaidService->planAnotherReceipt(
                                    $receipt,
                                    $date,
                                    $receiptResult['amount_left']
                                );
                            }
                        }
                    }

                    $unpaidService->setRdvProcessStatusFinished($rdv);
                } catch (\Exception $ex) {
                    $unpaidService->error('UNPAID PROCESSOR ERROR', [
                        'previousMsg' => get_class($ex).' => '.$ex->getMessage(),
                        'msg' => sprintf('Failed to process receipt %d for RDV %s for client %s', $receipt->getId(), $rdv->getId(), $rdv->getClient()->getFullName())
                    ]);

                    if ($ex instanceof PaymentRefusedException) {
                        $unpaidService->setRdvProcessStatusFinished($rdv);
                    } else if (
                        !$ex instanceof InvalidParameterException
                        && !$ex instanceof CardException
                        && !$ex instanceof PaymentFailedException
                    ) {
                        throw $ex;
                    }
                }
            }
        }

        $unpaidService->debug('Finished receipts processing', []);

    }

}
