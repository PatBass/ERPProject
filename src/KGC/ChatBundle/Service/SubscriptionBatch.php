<?php

namespace KGC\ChatBundle\Service;

use JMS\DiExtraBundle\Annotation as DI;
use KGC\ChatBundle\Entity\ChatPayment;
use Monolog\Logger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use KGC\ChatBundle\Entity\ChatSubscription;
use KGC\CommonBundle\Logger\ConsoleOutputLoggerTrait;
use KGC\CommonBundle\Mailer\TwigSwiftMailer;

/**
 * @DI\Service("kgc.chat.subscription.batch")
 * @DI\Tag("monolog.logger", attributes = {"channel" = "subscription"})
 */
class SubscriptionBatch
{
    use ConsoleOutputLoggerTrait;

    /**
     * @var ChatSubscriptionManager
     */
    protected $subscriptionManager;

    /**
     * @var PaymentManager
     */
    protected $paymentManager;

    /**
     * @var Mailer
     */
    protected $mailer;

    protected $checkedUsers = null;

    protected static $currentDate = 'now';

    /**
     * @DI\InjectParams({
     *     "subscriptionManager" = @DI\Inject("kgc.chat.subscription.manager"),
     *     "paymentManager" = @DI\Inject("kgc.chat.payment.manager"),
     *     "mailer" = @DI\Inject("kgc.common.twig_swift_mailer"),
     *     "em" = @DI\Inject("doctrine.orm.entity_manager")
     * })
     */
    public function __construct(SubscriptionManager $subscriptionManager, PaymentManager $paymentManager, TwigSwiftMailer $mailer, $em, LoggerInterface $logger)
    {
        $this->subscriptionManager = $subscriptionManager;
        $this->paymentManager = $paymentManager;
        $this->mailer = $mailer;
        $this->setLogger($logger);

        $this->em = $em;
    }

    /**
     * @param bool $dryRun Indicate if ready payments are only shown (dryRun = true) or processed
     */
    public function process($dryRun)
    {
        $subscriptions = $this->subscriptionManager->findReadySubscriptions($this->checkedUsers, self::$currentDate);

        if (empty($subscriptions)) {
            $this->info('No new subscription payment to process');
        } elseif ($dryRun) {
            foreach ($subscriptions as $subscription) {
                $this->info('Eligible '.$this->getSubscriptionDescription($subscription));
            }
        } else {
            foreach ($subscriptions as $i => $subscription) {
                $this->info('Processing '.$this->getSubscriptionDescription($subscription));

                try {
                    $chatPayment = $this->paymentManager->processSubscriptionPayment($subscription, true, self::$currentDate);

                    if ($chatPayment->getState() == ChatPayment::STATE_DONE) {
                        $this->info(' -> Payment succeed on '.$chatPayment->getPayment()->getTpe()->getLibelle());
                        $this->mailer->sendSubscriptionPaymentSuccessEmailMessage($chatPayment->getClient(), $chatPayment);
                    } else {
                        $this->info(' -> Payment #'.$chatPayment->getPayment()->getId().' failed on '.$chatPayment->getPayment()->getTpe()->getLibelle());
                        if ($subscription->getNextPaymentDate() === null) {
                            $this->info(' -> Subscription disabled');
                        }
                    }
                } catch (\Exception $e) {
                    $this->error(' -> '.get_class($e).' : '.$e->getMessage());
                }
            }
        }
    }

    public static function setCurrentDate($date)
    {
        self::$currentDate = $date;
    }

    protected function getSubscriptionDescription(ChatSubscription $subscription)
    {
        $client = $subscription->getClient();
        $formulaRate = $subscription->getChatFormulaRate();
        $chatPayment = $formulaRate->getChatPayments()->last();

        return sprintf(
            'subscription #%d : Client #%d (%s, %s), Formula #%d (Price=%.02f€), subscription starting from %s, last payment at %s',
            $subscription->getId(),
            $client->getId(),
            $client->getNom(),
            $client->getPrenom(),
            $formulaRate->getId(),
            $formulaRate->getPrice(),
            $subscription->getSubscriptionDate()->format('Y-m-d H:i:s'),
            $chatPayment ? $chatPayment->getDate()->format('Y-m-d H:i:s') : 'never'
        );
    }

    public function setCheckedUsers(array $checkedUsers)
    {
        $this->checkedUsers = $checkedUsers;
    }
}
