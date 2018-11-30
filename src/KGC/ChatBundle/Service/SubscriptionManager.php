<?php

namespace KGC\ChatBundle\Service;

use JMS\DiExtraBundle\Annotation as DI;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\TranslatorInterface;
use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\Bundle\SharedBundle\Entity\Website;
use KGC\Bundle\SharedBundle\Service\SharedWebsiteManager;
use KGC\ChatBundle\Entity\ChatSubscription;
use KGC\CommonBundle\Mailer\TwigSwiftMailer;
use KGC\CommonBundle\Traits\NextReceiptDate;
use KGC\PaymentBundle\Service\Payment\Factory as PaymentFactory;
use KGC\UserBundle\Entity\Utilisateur;

/**
 * @DI\Service("kgc.chat.subscription.manager")
 */
class SubscriptionManager
{
    use NextReceiptDate;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var TranslatorInterface
     */
    protected $t;

    /**
     * @var SharedWebsiteManager
     */
    protected $sharedWebsiteManager;

    /**
     * @var TwigSwiftMailer
     */
    protected $mailer;

    /**
     * @var PaymentFactory
     */
    protected $paymentFactory;

    /**
     * @param EntityManager $em
     *
     * @DI\InjectParams({
     *     "em" = @DI\Inject("doctrine.orm.entity_manager"),
     *     "t" = @DI\Inject("translator"),
     *     "sharedWebsiteManager" = @DI\Inject("kgc.shared.website.manager"),
     *     "mailer" = @DI\Inject("kgc.common.twig_swift_mailer"),
     *     "paymentFactory"  = @DI\Inject("kgc.payment.factory")
     * })
     */
    public function __construct(EntityManager $em, TranslatorInterface $t, SharedWebsiteManager $sharedWebsiteManager, TwigSwiftMailer $mailer, PaymentFactory $paymentFactory)
    {
        $this->em = $em;
        $this->t = $t;
        $this->sharedWebsiteManager = $sharedWebsiteManager;
        $this->mailer = $mailer;
        $this->paymentFactory = $paymentFactory;
    }

    /**
     * Convert a chat subscription to json array.
     *
     * @param ChatSubscription $chatSubscription
     *
     * @return JSON like array
     */
    public function convertChatSubscriptionToJsonArray(ChatSubscription $chatSubscription)
    {
        $json_chat_subscription = $chatSubscription->toJsonArray();
        $json_complement = array(
            'formula_rate' => null,
            'chat_type' => null,
        );

        if ($chatFormulaRate = $chatSubscription->getChatFormulaRate()) {
            $json_complement['formula_rate'] = $chatFormulaRate->toJsonArray();

            if ($chatFormula = $chatFormulaRate->getChatFormula()) {
                if ($chatType = $chatFormula->getChatType()) {
                    $json_complement['chat_type'] = $chatType->toJsonArray();
                }
            }
        }

        return $json_chat_subscription +  $json_complement;
    }

    /**
     * Convenience method for convertChatSubscriptionToJsonArray().
     *
     * @param array $chatSubscriptions
     *
     * @return JSON like array
     */
    public function convertChatSubscriptionsToJsonArray($chatSubscriptions = array())
    {
        $json = array();
        foreach ($chatSubscriptions as $chatSubscription) {
            $json[] = $this->convertChatSubscriptionToJsonArray($chatSubscription);
        }

        return $json;
    }

    /**
     * Get the current subscription (if it exists) for a specific user on a specific website.
     *
     * @param string $website_slug
     * @param Client $client
     *
     * @return JSON like array
     */
    public function getSubscriptions($website_slug, Client $client)
    {
        $json = array(
            'status' => 'KO',
            'message' => 'Nothing happend',
            'subscriptions' => array(),
        );

        $website = $this->sharedWebsiteManager->getWebsiteBySlug($website_slug);
        if (!($website instanceof Website)) {
            $json['message'] = 'Unknown website';

            return $json;
        }

        $subscriptions = $this->em->getRepository('KGCChatBundle:ChatSubscription')->findWithChatType($client, $website);

        $json = array(
            'status' => 'OK',
            'message' => 'Subscriptions retrieved',
            'subscriptions' => $subscriptions,
        );

        return $json;
    }

    /**
     * Unsubscribe (if subscription exists).
     *
     * @param string      $websiteSlug
     * @param Client      $client
     * @param int         $chatSubscriptionId
     * @param int         $source
     * @param Utilisateur $updatedBy
     *
     * @return JSON like array
     */
    public function unsubscribe($websiteSlug, Client $client, $chatSubscriptionId, $source = null, Utilisateur $updatedBy = null)
    {
        $json = array(
            'status' => 'KO',
            'message' => 'Nothing happend',
        );

        $website = $this->sharedWebsiteManager->getWebsiteBySlug($websiteSlug);
        if (!($website instanceof Website)) {
            $json['message'] = 'Unknown website';

            return $json;
        }

        $subscription = $this->em->getRepository('KGCChatBundle:ChatSubscription')->findOneBy(array(
            'id' => $chatSubscriptionId,
            'website' => $website,
            'client' => $client,
        ));
        if (!($subscription instanceof ChatSubscription)) {
            $json['message'] = 'Unknown subscription';

            return $json;
        }

        $subscription
            ->setDesactivationDate(new \DateTime())
            ->setDesactivationSource($source)
            ->setDesactivatedBy($updatedBy);
        $this->em->flush();

        try {
            $this->mailer->sendCancelSubscriptionSuccessEmailMessage($client, $subscription);
        } catch (\Exception $e) {
            return ['message' => $e->getMessage()] + $json;
        }

        $json = array(
            'status' => 'OK',
            'message' => 'Subscription canceled',
        );

        return $json;
    }

    /**
     * Disable the subscription (if it exists).
     *
     * @param string $websiteSlug
     * @param Client $client
     * @param int    $chatSubscriptionId
     * @param int         $source
     * @param Utilisateur $updatedBy
     *
     * @return JSON like array
     */
    public function disableSubscription($websiteSlug, Client $client, $chatSubscriptionId, $source = null, Utilisateur $updatedBy = null)
    {
        $json = array(
            'status' => 'KO',
            'message' => 'Nothing happend',
        );

        $website = $this->sharedWebsiteManager->getWebsiteBySlug($websiteSlug);
        if (!($website instanceof Website)) {
            $json['message'] = 'Unknown website';

            return $json;
        }

        $subscription = $this->em->getRepository('KGCChatBundle:ChatSubscription')->findOneBy(array(
            'id' => $chatSubscriptionId,
            'website' => $website,
            'client' => $client,
        ));
        if (!($subscription instanceof ChatSubscription)) {
            $json['message'] = 'Unknown subscription';

            return $json;
        }

        $disableDate = new \DateTime;

        $subscription
            ->setNextPaymentDate(null)
            ->setDisableDate($disableDate)
            ->setDisableSource($source)
            ->setDisabledBy($updatedBy);

        $this->em->flush();

        $json = array(
            'status' => 'OK',
            'message' => 'Subscription canceled',
        );

        return $json;
    }

    protected function guessNextPaymentDate(array $row)
    {
        $startDate = $row['nextPaymentDate'];

        if (isset($row['lastFailedPaymentDate'])) {
            $nextPaymentDate = $this->getNextReceiptDate(
                $this->paymentFactory->getByWebsiteReference($row['origin']),
                $startDate,
                new \DateTime($row['lastFailedPaymentDate'])
            )['date'];
        } else {
            $nextPaymentDate = $startDate;
        }

        return $nextPaymentDate;
    }

    public function findPlannedPaymentsByClient(Client $client)
    {
        $chatSubscriptionRepo = $this->em->getRepository('KGCChatBundle:ChatSubscription');

        $readySubsBeforeFailedCheck = $chatSubscriptionRepo->findReadySubscriptionsBeforeFailCheck([$client->getId()]);

        $selectedSubs = [];
        foreach ($readySubsBeforeFailedCheck as $row) {
            $nextPaymentDate = $this->guessNextPaymentDate($row);
            if ($nextPaymentDate !== null) {
                $selectedSubs[$row['id']] = $nextPaymentDate;
            }
        }

        if (empty($selectedSubs)) {
            return [];
        } else {
            $tpe = $this->em->getRepository('KGCRdvBundle:TPE')->findOneByWebsiteReference($client->getOrigin());

            $result = $chatSubscriptionRepo->findShortenSubscriptionListByIds(array_keys($selectedSubs));

            $payments = [];
            foreach ($result as $row) {
                $payments[] = [
                    'chatFormulaRate' => [
                        'type' => $row['type'],
                    ],
                    'amount' => $row['price'] * 100,
                    'state' => null,
                    'date' => $selectedSubs[$row['id']],
                    'isEditable' => false,
                    'payment' => [
                        'tpe' => [
                            'libelle' => $tpe->getLibelle(),
                        ],
                    ],
                ];
            }

            return $payments;
        }
    }

    public function findReadySubscriptions($checkedUsers = null, $currentDate = 'now')
    {
        $chatSubscriptionRepo = $this->em->getRepository('KGCChatBundle:ChatSubscription');

        $readySubsBeforeFailedCheck = $chatSubscriptionRepo->findReadySubscriptionsBeforeFailCheck($checkedUsers, $currentDt = new \DateTime($currentDate));

        $ids = [];
        foreach ($readySubsBeforeFailedCheck as $row) {
            $nextPaymentDate = $this->guessNextPaymentDate($row);
            if ($nextPaymentDate !== null && $nextPaymentDate < $currentDt) {
                $ids[] = $row['id'];
            }
        }

        return empty($ids) ?
            [] :
            $chatSubscriptionRepo->findSubscriptionsWithFormulaRateByIds($ids);
    }
}
