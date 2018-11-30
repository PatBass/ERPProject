<?php

namespace KGC\ChatBundle\Service;

use JMS\DiExtraBundle\Annotation as DI;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\FormFactory;
use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\Bundle\SharedBundle\Entity\Website;
use KGC\Bundle\SharedBundle\Form\CreditCardType;
use KGC\Bundle\SharedBundle\Service\SharedWebsiteManager;
use KGC\ChatBundle\Entity\ChatType;
use KGC\ChatBundle\Entity\ChatFormulaRate;
use KGC\ChatBundle\Entity\ChatPayment;
use KGC\ChatBundle\Entity\ChatPromotion;
use KGC\ChatBundle\Entity\ChatSubscription;
use KGC\CommonBundle\Mailer\TwigSwiftMailer;
use KGC\PaymentBundle\Entity\Payment;
use KGC\PaymentBundle\Entity\PaymentAlias;
use KGC\PaymentBundle\Exception\Payment\InvalidCardDataException;
use KGC\PaymentBundle\Service\Payment\Factory as PaymentFactory;
use KGC\PaymentBundle\Service\Payment\Gateway\Exception\InvalidParameterException;
use KGC\PaymentBundle\Service\Payment\PaymentStatus;
use KGC\RdvBundle\Service\PaymentManager as RdvPaymentManager;
use KGC\RdvBundle\Entity\CarteBancaire;
use KGC\RdvBundle\Entity\MoyenPaiement;

/**
 * @DI\Service("kgc.chat.payment.manager")
 */
class PaymentManager
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var SharedWebsiteManager
     */
    protected $sharedWebsiteManager;

    /**
     * @var TwigSwiftMailer
     */
    protected $mailer;

    /**
     * @param SecurityContextInterface $em
     *
     * @DI\InjectParams({
     *     "em" = @DI\Inject("doctrine.orm.entity_manager"),
     *     "rdvPaymentManager" = @DI\Inject("kgc.rdv.payment_manager"),
     *     "sharedWebsiteManager" = @DI\Inject("kgc.shared.website.manager"),
     *     "websiteManager" = @DI\Inject("kgc.chat.website.manager"),
     *     "formFactory" = @DI\Inject("form.factory"),
     *     "paymentFactory" = @DI\Inject("kgc.payment.factory"),
     *     "mailer" = @DI\Inject("kgc.common.twig_swift_mailer")
     * })
     */
    public function __construct(EntityManager $em, RdvPaymentManager $rdvPaymentManager, SharedWebsiteManager $sharedWebsiteManager, WebsiteManager $websiteManager, FormFactory $formFactory, PaymentFactory $paymentFactory, TwigSwiftMailer $mailer)
    {
        $this->em = $em;
        $this->rdvPaymentManager = $rdvPaymentManager;
        $this->sharedWebsiteManager = $sharedWebsiteManager;
        $this->websiteManager = $websiteManager;
        $this->formFactory = $formFactory;
        $this->paymentFactory = $paymentFactory;
        $this->mailer = $mailer;
    }

    /**
     * Get units available (amount of units availables for a chat type) about a client
     * For minute chat type, it's an amount of seconds
     * For a question chat type, it's an amount of remaining questions.
     *
     * @param Client   $client
     * @param ChatType $chatType The chat type wanted (question, minute ..)
     * @param Website  $website  The website concerned
     *
     * @return int
     */
    public function getRemainingCredit(Client $client, ChatType $chatType, Website $website)
    {
        // Add all units from chatFormulaRates linked to chatPayments which are not empty
        $units = 0;
        $chatPayments = $this->em->getRepository('KGCChatBundle:ChatPayment')->getNotEmptyPayments($client, $chatType, $website);

        // For each of this chatPayments, compare the consumed amount and the remaining units
        foreach ($chatPayments as $chatPayment) {
            $units += $chatPayment->getRemainingUnits();
        }

        return $units;
    }

    /**
     * Get chat remaining credit for a specific client on website.
     *
     * @param string $website_slug
     * @param Client $client
     *
     * @return JSON like array
     */
    public function getChatRemainingCredit($website_slug, Client $client)
    {
        $json = array(
            'status' => 'KO',
            'message' => 'Nothing happend',
            'credits_by_chat_type' => array(),
        );

        $website = $this->sharedWebsiteManager->getWebsiteBySlug($website_slug);
        if (!($website instanceof Website)) {
            $json['message'] = 'Unknown website';

            return $json;
        }

        foreach ($website->getChatFormulas() as $chatFormula) {
            $remaining_credit = $this->getRemainingCredit($client, $chatFormula->getChatType(), $website);
            $json['credits_by_chat_type'][] = array(
                'chat_type' => $chatFormula->getChatType(),
                'remaining_credit' => $remaining_credit,
            );
        }

        $json['status'] = 'OK';
        $json['message'] = 'Conversations retrieved';

        return $json;
    }

    public function getDefaultPaymentMethod()
    {
        static $paymentMethod = null;

        if ($paymentMethod === null) {
            $paymentMethod = $this->em->getRepository('KGCRdvBundle:MoyenPaiement')->findOneByIdcode(MoyenPaiement::DEBIT_CARD);
        }

        return $paymentMethod;
    }

    /**
     * @param Client $client
     * @param ChatFormulaRate $formulaRate
     * @param ChatPromotion $promotion
     *
     * @return bool
     */
    protected function isPromotionAllowed(Client $client, ChatFormulaRate $formulaRate, ChatPromotion $promotion, &$errorMessage)
    {
        $errorMessage = 'Invalid promotion code';

        $website = $formulaRate->getChatFormula()->getWebsite();

        // if wrong client website or promotion website
        if ($website->getReference() != $client->getOrigin() || $website->getId() != $promotion->getWebsite()->getId()) {
            //$errorMessage = 'Invalid promotion website';

            return false;
        }

        if (!$promotion->getEnabled()) {
            return false;
        }

        $isValidForType = false;

        switch ($formulaRate->getType()) {
            case ChatFormulaRate::TYPE_FREE_OFFER:
                $isValidForType = $formulaRate->getFlexible() && $promotion->hasFormulaFilter(ChatPromotion::FORMULA_FILTER_NONE);
                break;
            case ChatFormulaRate::TYPE_DISCOVERY:
                $isValidForType = $promotion->hasFormulaFilter(ChatPromotion::FORMULA_FILTER_DISCOVERY);
                break;
            case ChatFormulaRate::TYPE_STANDARD:
                $isValidForType = $promotion->hasFormulaFilter(ChatPromotion::FORMULA_FILTER_STANDARD);
                break;
            case ChatFormulaRate::TYPE_PREMIUM:
                $isValidForType = $promotion->hasFormulaFilter(ChatPromotion::FORMULA_FILTER_PREMIUM);
                break;
        }

        if (!$isValidForType) {
            //$errorMessage = 'Invalid promotion formula type type';
            return;
        }

        if ($promotion->getType() != ChatPromotion::TYPE_CODE_PROMO) {
            //$errorMessage = 'Invalid promotion type';

            return false;
        }

        $currentDate = new \DateTime;
        if ($promotion->getEndDate()) {
            $endDate = clone $promotion->getEndDate();
            $endDate->modify('+1 day');
        } else {
            $endDate = null;
        }

        if (
            ($promotion->getStartDate() !== null && $currentDate < $promotion->getStartDate())
            || ($endDate !== null && $currentDate >= $endDate)
        ) {
            //$errorMessage = 'Invalid promotion period ('.$promotion->getStartDate()->format('Y-m-d H:i:s').' => '.$endDate->format('Y-m-d H:i:s').')';

            return false;
        }

        if ($this->em->getRepository('KGCChatBundle:ChatPayment')->hasChatPaymentWithChatPromotion($client, $promotion)) {
            $errorMessage = 'Promotion code already used';

            return false;
        }

        return true;
    }

    /**
     * Buy a formula rate for a client.
     *
     * @param Client $client
     * @param string $website_slug
     * @param int    $id           formla rate's id
     *
     * @return JSON like array
     */
    public function buyFormulaRate(Client $client, $website_slug, $id, $parameters = [])
    {
        $json = array(
            'status' => 'KO',
            'message' => 'Nothing happend',
            'formula_rate' => null,
        );

        $website = $this->sharedWebsiteManager->getWebsiteBySlug($website_slug);
        if (!($website instanceof Website)) {
            $json['message'] = 'Unknown website';

            return $json;
        }

        if ($errorMessage = $this->websiteManager->checkClientIsNotAllowed($client, $website)) {
            $json['message'] = $errorMessage;

            return $json;
        }

        $formulaRate = $this->em->getRepository('KGCChatBundle:ChatFormulaRate')->findByWebsiteAndId($website->getId(), $id);
        if (!($formulaRate instanceof ChatFormulaRate)) {
            $json['message'] = 'Unknown formula rate';

            return $json;
        }

        if (!$this->isFormulaRateAllowed($client, $website, $formulaRate, $errorMessage)) {
            $json['message'] = $errorMessage;

            return $json;
        }

        if (!empty($parameters['promotionCode'])) {
            $promotion = $this->em->getRepository('KGCChatBundle:ChatPromotion')->findOneByWebsiteAndPromotionCode($website, $parameters['promotionCode']);
            $errorMessage = null;

            if ($promotion === null || !$this->isPromotionAllowed($client, $formulaRate, $promotion, $errorMessage)) {
                $json['message'] = isset($errorMessage) ? $errorMessage : 'Invalid promotion code';

                return $json;
            }
        } else {
            $promotion = null;
        }

        // we cannot "buy" a flexible offer through api without using a compatible promotion
        if ($formulaRate->getFlexible() && $promotion === null) {
            $json['message'] = 'Missing promotion code for flexible offer';

            return $json;
        }

        $effectivePrice = $formulaRate->getPrice();
        $effectiveUnits = $formulaRate->getUnits();

        if ($promotion) {
            switch ($promotion->getUnitType()) {
                case ChatPromotion::UNIT_TYPE_BONUS:
                    $effectiveUnits += $promotion->getUnit();
                    $promotionForJson = [
                        'code' => $promotion->getPromotionCode(),
                        'type' => $formulaRate->getChatFormula()->getChatType()->getType() == ChatType::TYPE_QUESTION ? 'bonus_question' : 'bonus_duration',
                        'unit' => $promotion->getUnit()
                    ];
                    break;
                case ChatPromotion::UNIT_TYPE_PERCENTAGE:
                    $effectivePrice = intval($effectivePrice * (100 - $promotion->getUnitType())) / 100;
                    $promotionForJson = [
                        'code' => $promotion->getPromotionCode(),
                        'type' => 'reduction_percentage',
                        'unit' => $promotion->getUnit(),
                        'effectivePrice' => $effectivePrice
                    ];
                    break;
                case ChatPromotion::UNIT_TYPE_PRICE:
                    $effectivePrice = max(0, $effectivePrice - $promotion->getUnit());
                    $promotionForJson = [
                        'code' => $promotion->getPromotionCode(),
                        'type' => 'reduction_price',
                        'unit' => $promotion->getUnit(),
                        'effectivePrice' => $effectivePrice
                    ];
                    break;
            }
        }

        if ($effectivePrice > 0) {
            $paymentMethod = $this->validateBuyParameters($client, $parameters, $errorMessage);
            $isSubscription = $formulaRate->getType() == ChatFormulaRate::TYPE_SUBSCRIPTION;

            if (!$paymentMethod) {
                $json['message'] = $errorMessage;

                return $json;
            }

            try {
                if ($paymentMethod instanceof CarteBancaire) {
                    if (!$paymentMethod->belongsTo($client)) {
                        $json['message'] = 'Invalid card';

                        return $json;
                    }

                    $status = $this->rdvPaymentManager->payWithCartebancaire($client, $paymentMethod, $website->getPaymentGateway(), $effectivePrice, $isSubscription, false);

                    // we store cb only if payment is successful
                    if ($status->isCaptured()) {
                        $client->addCartebancaires($paymentMethod);

                        $this->em->persist($client);
                        $this->em->flush($client);
                    }
                } else {
                    $gateway = $this->paymentFactory->get($website->getPaymentGateway());
                    $status = $gateway->payment($client, $formulaRate->getPrice(), $paymentMethod, $isSubscription);
                }


                if (!$status->isCaptured()) {
                    $json['message'] = 'Payment refused';

                    return $json;
                }

                $payment = $status->getFirstModel();
            } catch (\Exception $e) {
                if ($e instanceof InvalidParameterException) {
                    $json['message'] = 'Invalid alias';
                } else if ($e instanceof InvalidCardDataException) {
                    $json['message'] = 'Invalid card data';
                } else if ($e instanceof PaymentFailedException) {
                    $json['message'] = 'Payment failed';
                } else {
                    $json['message'] = 'Error during payment';
                }

                return $json;
            }
        } else {
            $payment = null;
        }

        $nextPaymentDate = new \DateTime('+1 month, -1 day, midnight');

        // If it's a subscription website and client buy a discovery offer
        // Search for subscription offer and insert a new subscription for this client (only if he doesn't have one yet)
        if ($website->isTypeSubscription() && $formulaRate->isDiscovery()) {
            $subscriptionFormulaRate = $this->em
                ->getRepository('KGCChatBundle:ChatFormulaRate')
                ->findOneByWebsiteAndType($website, ChatFormulaRate::TYPE_SUBSCRIPTION);

            if ($subscriptionFormulaRate instanceof ChatFormulaRate) {
                $subscription = new ChatSubscription();
                $subscription->setClient($client)
                             ->setWebsite($website)
                             ->setChatFormulaRate($subscriptionFormulaRate)
                             ->setSubscriptionDate(new \DateTime)
                             ->setNextPaymentDate($nextPaymentDate);

                $this->em->persist($subscription);
            }
        }

        // If client buy a subscription offer, register him in subscription table
        if ($formulaRate->isSubscription()) {
            $subscription = new ChatSubscription();
            $subscription->setClient($client)
                         ->setWebsite($website)
                         ->setChatFormulaRate($formulaRate)
                         ->setSubscriptionDate(new \DateTime)
                         ->setNextPaymentDate($nextPaymentDate);

            $this->em->persist($subscription);
        }

        $chatPayment = new ChatPayment();
        $chatPayment->setChatFormulaRate($formulaRate)
                    ->setAmount($amount = $effectivePrice * 100)
                    ->setUnit($effectiveUnits)
                    ->setClient($client)
                    ->setPayment($payment)
                    ->setPaymentMethod($amount > 0 ? $this->getDefaultPaymentMethod() : null)
                    ->setState(ChatPayment::STATE_DONE)
                    ->setPromotion($promotion);

        $this->em->persist($chatPayment);
        $this->em->flush();

        if ($formulaRate->getPrice() > 0) {
            try {
                $this->mailer->sendPaymentSuccessEmailMessage($client, $chatPayment);
            } catch (\Exception $e) {}
        }

        if (isset($promotionForJson)) {
            $json['promotion'] = $promotionForJson;
        }
        $json['formula_rate'] = $formulaRate;
        $json['status'] = 'OK';
        $json['message'] = 'Formula rate chose';

        return $json;
    }

    /**
     * @param Client $client
     * @param string $promotionCode
     *
     * @return array
     */
    public function usePromotionCode(Client $client, $promotionCode)
    {
        $reference = $client->getOrigin();
        $website_slug = SharedWebsiteManager::getSlugFromReference($reference);
        $formulaRate = $this->em->getRepository('KGCChatBundle:ChatFormulaRate')->findOneByWebsiteRefAndType(
            $reference,
            ChatFormulaRate::TYPE_FREE_OFFER,
            true
        );

        if ($formulaRate) {
            return $this->buyFormulaRate($client, $website_slug, $formulaRate->getId(), ['promotionCode' => $promotionCode]);
        } else {
            return ['status' => 'KO', 'message' => 'Unknown formula rate'];
        }
    }

    public function deleteCreditCard(Client $client, $id)
    {
        $json = array(
            'status' => 'KO',
            'message' => 'Nothing happend',
        );

        $cbRepository = $this->em->getRepository('KGCRdvBundle:CarteBancaire');
        $card = $cbRepository->find($id);

        if (!$card instanceof CarteBancaire || !$card->belongsTo($client) || $card->getFirstName() === null) {
            return ['message' => 'Invalid card'] + $json;
        }

        // If client has only one card and a subscription on going, he has to cancel his subscription before
        $cardsNb = $cbRepository->countChatCbsByClient($client);
        if ($cardsNb == 1) {
            // Check if this client has subscription on going
            $subscriptions = $this->em->getRepository('KGCChatBundle:ChatSubscription')->findByClient($client);
            if (count($subscriptions) > 0) {
                return ['message' => 'Can\'t remove credit cards without unsubscribing first'] + $json;
            }
        }

        foreach ($card->getPaymentAliases() as $alias) {
            $this->em->remove($alias);
        }
        $this->em->remove($card);
        $this->em->flush();

        return ['status' => 'OK', 'message' => 'Card deleted'];
    }

    protected function validateBuyParameters(Client $client, $parameters, &$errorMessage)
    {
        foreach ($parameters as $key => $value) {
            if (!in_array($key, ['creditCard', 'card', 'promotionCode'])) {
                $errorMessage = 'Invalid parameter "'.$key.'"';

                return false;
            }
        }

        if (isset($parameters['creditCard'])) {
            $form = $this->formFactory->create(new CreditCardType(), null, ['csrf_protection' => false]);

            $creditCardParams = $parameters['creditCard'];
            if (isset($creditCardParams['expireAt'])) {
                $creditCardParams['expireAt'] = ['day' => '1'] + (array) $creditCardParams['expireAt'];
            }

            $form->submit($creditCardParams);
            if ($form->isValid()) {
                return CarteBancaire::createFromCreditCard($form->getData())->addClients($client);
            }

            foreach ($form as $fieldName => $formField) {
                foreach ($formField->getErrors() as $error) {
                    $errorMessage = sprintf('Invalid creditCard field "%s" : %s', $fieldName, $error->getMessage());

                    return false;
                }
            }

            $errorMessage = 'Invalid creditCard';

            return false;
        } elseif (isset($parameters['card'])) {
            if (
                !ctype_digit($parameters['card']) ||
                !($cb = $this->em->getRepository('KGCRdvBundle:CarteBancaire')->find($parameters['card']))
            ) {
                $errorMessage = 'Invalid card';

                return false;
            }

            return $cb;
        }

        $errorMessage = 'Missing creditCard or card parameter';

        return false;
    }

    protected function isFormulaRateAllowed(Client $client, Website $website, ChatFormulaRate $formulaRate, &$errorMessage)
    {
        $rateExpiration = $formulaRate->getDesactivationDate();
        if ($rateExpiration instanceof \DateTime && $rateExpiration->format('U') <= time()) {
            $errorMessage = 'Expired formula rate';

            return false;
        }

        $formulaExpiration = $formulaRate->getChatFormula()->getDesactivationDate();
        if ($formulaExpiration instanceof \DateTime && $formulaExpiration->format('U') <= time()) {
            $errorMessage = 'Expired formula';

            return false;
        }

        $statistics = $this->em->getRepository('KGCChatBundle:ChatPayment')->getClientPaymentsStatistics($client, $website);

        // if new formula is premium and there is no standard formulas already bought
        if (
            $formulaRate->getType() == ChatFormulaRate::TYPE_PREMIUM &&
            (
                empty($statistics['standard_formulas']) ||
                $statistics['standard_formulas'] == 0
            )
        ) {
            $errorMessage = 'Not eligible for premium formula rate';

            return false;
        }

        // if new formula is discovery and client has already bought another formula
        if (
            $formulaRate->getType() == ChatFormulaRate::TYPE_DISCOVERY &&
            isset($statistics['total_formulas']) && $statistics['total_formulas'] > $statistics['free_offer_formulas']
        ) {
            $errorMessage = 'Not eligible for discovery formula rate';

            return false;
        }

        return true;
    }

    protected function setNextSubscriptionPaymentDate(ChatSubscription $subscription)
    {
        $currentDate = new \DateTime;

        if ($subscription->getDesactivationDate() !== null && $subscription->getDesactivationDate() < $currentDate) {
            $nextPaymentDate = null;
        } else {
            $originalPaymentDate = clone $subscription->getSubscriptionDate();
            $originalPaymentDate->modify('+1 month, -1 day');
            $nextPaymentDate = new \DateTime($currentDate->format('Y-m-').$originalPaymentDate->format('d'));

            while ($nextPaymentDate < $currentDate || $nextPaymentDate <= $subscription->getNextPaymentDate()) {
                $nextPaymentDate->modify('+1 month');
            }
        }

        $subscription->setNextPaymentDate($nextPaymentDate);
    }

    public function processSubscriptionPayment(ChatSubscription $subscription, $useDelay = true, $currentDate = 'now')
    {
        $client = $subscription->getClient();

        $website = $this->em->getRepository('KGCSharedBundle:Website')->findOneByReference($client->getOrigin());

        $carteBancaire = null;
        foreach ($client->getCartebancairesForTchat() as $cb) {
            $this->rdvPaymentManager->decryptCb($cb);
            if ($cb->isValid()) {
                $carteBancaire = $cb;
            }
        }
        $chatFormulaRate = $subscription->getChatFormulaRate();

        if ($carteBancaire) {
            $status = $this->rdvPaymentManager->payWithCartebancaire($client, $carteBancaire, $website->getPaymentGateway(), $chatFormulaRate->getPrice(), true);
        } else {
            $subscription
                ->setNextPaymentDate(null)
                ->setDisableDate(new \DateTime)
                ->setDisableSource(ChatSubscription::SOURCE_CRON);
            $this->em->persist($subscription);

            throw new \Exception('No valid alias or card available');
        }

        $payment = $status->getFirstModel();

        $lastPayment = $this->em->getRepository('KGCChatBundle:ChatPayment')->findLastBySubscription($client, $website);

        $state = $status->isCaptured() ? ChatPayment::STATE_DONE : ChatPayment::STATE_ERROR;
        $chatPayment = new ChatPayment();
        $chatPayment->setChatFormulaRate($chatFormulaRate)
                    ->setAmount($amount = $chatFormulaRate->getPrice() * 100)
                    ->setUnit($chatFormulaRate->getUnits())
                    ->setClient($client)
                    ->setPreviousPayment($lastPayment)
                    ->setPayment($payment)
                    ->setPaymentMethod($amount > 0 ? $this->getDefaultPaymentMethod() : null)
                    ->setState($state);

        if ($status->isCaptured()) {
            $this->setNextSubscriptionPaymentDate($subscription, $currentDate);

            $this->em->persist($subscription);
        } else {
            $consecFails = $this->em->getRepository('KGCChatBundle:ChatSubscription')->countConsecutiveFails($subscription);
            $gateway = $this->paymentFactory->get($website->getPaymentGateway());

            if ($consecFails >= $gateway->getAllowedConsecutiveFails()) {
                $subscription
                    ->setNextPaymentDate(null)
                    ->setDisableDate(new \DateTime)
                    ->setDisableSource(ChatSubscription::SOURCE_CRON);

                $this->em->persist($subscription);
            }
        }

        $this->em->persist($chatPayment);
        $this->em->flush();

        if ($useDelay && $payment && ($delay = $payment->getTpe()->getDelay())) {
            sleep($delay);
        }

        return $chatPayment;
    }

    /**
     * @param ChatPayment $chatPayment
     * @param ChatSubscription $chatSubscription
     */
    public function processManualSubscription(ChatPayment $chatPayment, ChatSubscription $chatSubscription)
    {
        $chatFormulaRate = $chatSubscription->getChatFormulaRate();

        $chatPayment
            ->setChatFormulaRate($chatFormulaRate)
            ->setAmount($chatFormulaRate->getPrice() * 100)
            ->setUnit($chatFormulaRate->getUnits())
            ->setState(ChatPayment::STATE_DONE)
            ->setClient($chatSubscription->getClient());

        $this->setNextSubscriptionPaymentDate($chatSubscription);

        $this->em->persist($chatPayment);
        $this->em->persist($chatSubscription);
        $this->em->flush();
    }

    /**
     * @param PaymentStatus
     *
     * @return bool true if a chat payment is updated
     */
    public function updateChatPaymentFromStatus(PaymentStatus $status)
    {
        $payment = $status->getFirstModel();
        if ($payment instanceof Payment) {
            $chatPayment = $this->em->getRepository('KGCChatBundle:ChatPayment')
                ->findOneByPayment($payment->getOriginalPayment() ?: $payment);

            if ($chatPayment) {
                $state = $chatPayment->getState();

                if ($state != ChatPayment::STATE_OPPOSED && $status->isOpposed()) {
                    $chatPayment->setState(ChatPayment::STATE_OPPOSED)->setOpposedDate(new \DateTime);
                } else if ($state != ChatPayment::STATE_REFUNDED && $status->isRefunded()) {
                    $chatPayment->setState(ChatPayment::STATE_REFUNDED)->setOpposedDate(new \DateTime);
                } else if ($state === ChatPayment::STATE_DONE && !$status->isCaptured()) {
                    $chatPayment->setState(ChatPayment::STATE_ERROR);
                } elseif ($state === ChatPayment::STATE_ERROR && $status->isCaptured()) {
                    $chatPayment->setState(ChatPayment::STATE_DONE);
                }

                $this->em->persist($chatPayment);
                $this->em->flush();

                return true;
            }
        }

        return false;
    }

    /**
     * @param ChatPayment $chatPayment
     * @param ChatFormulaRate $formula
     * @param Client $client
     */
    public function processOfferPayment(ChatPayment $chatPayment, ChatFormulaRate $formula, Client $client)
    {
        $chatPayment
            ->setAmount(0)
            ->setChatFormulaRate($formula)
            ->setState(ChatPayment::STATE_DONE)
            ->setClient($client)
            ->setDate(new \DateTime);

        if ($formula->getChatFormula()->getChatType()->getType() == ChatType::TYPE_MINUTE) {
            $chatPayment->setUnit($chatPayment->getUnit() * 60);
        }

        $this->em->persist($chatPayment);
        $this->em->flush();
    }
}
