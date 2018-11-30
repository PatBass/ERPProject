<?php

namespace KGC\CommonBundle\Mailer;

use FOS\UserBundle\Mailer\TwigSwiftMailer as BaseMailer;
use FOS\UserBundle\Model\UserInterface;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use KGC\ChatBundle\Entity\ChatPayment;
use KGC\ChatBundle\Entity\ChatSubscription;
use KGC\Bundle\SharedBundle\Service\WebsiteConfiguration;
use KGC\Bundle\SharedBundle\Service\SharedWebsiteManager;
use KGC\RdvBundle\Entity\RDV;

/**
 * @DI\Service("kgc.common.twig_swift_mailer")
 */
class TwigSwiftMailer extends BaseMailer
{
    /**
     * @param \Swift_Mailer   $mailer
     * @param UrlGeneratorInterface $router
     * @param \Twig_Environment $twig
     * @param WebsiteConfiguration $configuration
     *
     * @DI\InjectParams({
     *      "mailer" = @DI\Inject("swiftmailer.mailer.second_mailer"),
     *      "router" = @DI\Inject("router"),
     *      "twig" = @DI\Inject("twig"),
     *      "configuration" = @DI\Inject("kgc.shared.website.configuration"),
     * })
     */
    public function __construct(\Swift_Mailer $mailer, UrlGeneratorInterface $router, \Twig_Environment $twig, WebsiteConfiguration $configuration)
    {
        parent::__construct($mailer, $router, $twig, []);

        $this->configuration = $configuration;
    }

    protected function getCommonParameters($origin = null)
    {
        $suffix = $origin ? SharedWebsiteManager::getSlugFromReference($origin) : null;

        return [
            'siteUrl' => $this->configuration->get([$origin ?: 'default', 'siteUrl']),
            'sitePrefix' => $this->configuration->get([$origin ?: 'default', 'sitePrefix']).'/'.$suffix
        ];
    }

    public function sendPaymentSuccessEmailMessage(UserInterface $user, ChatPayment $chatPayment)
    {
        $template = 'KGCChatBundle:Payment:success.email.twig';
        $origin = $user->getOrigin();

        $context = $this->getCommonParameters($origin) + [
            'user' => $user
        ];

        $fromEmail = $this->configuration->get([$user->getOrigin(), 'from_email']);

        $this->sendMessage($template, $context, [$fromEmail['address'] => $fromEmail['sender_name']], $user->getEmail());
    }

    public function sendSubscriptionPaymentSuccessEmailMessage(UserInterface $user, ChatPayment $chatPayment)
    {
        $template = 'KGCChatBundle:Subscription:paymentSuccess.email.twig';
        $origin = $user->getOrigin();

        $context = $this->getCommonParameters($origin) + [
            'user' => $user,
            'amount' => $chatPayment->getChatFormulaRate()->getPrice()
        ];

        $fromEmail = $this->configuration->get([$origin, 'from_email']);

        $this->sendMessage($template, $context, [$fromEmail['address'] => $fromEmail['sender_name']], $user->getEmail());
    }

    public function sendCancelSubscriptionSuccessEmailMessage(UserInterface $user, ChatSubscription $chatSubscription)
    {
        $template = 'KGCChatBundle:Subscription:cancelSuccess.email.twig';
        $origin = $user->getOrigin();

        $context = $this->getCommonParameters($origin) + [
            'user' => $user,
            'subscription' => $chatSubscription
        ];

        $fromEmail = $this->configuration->get([$origin, 'from_email']);

        $this->sendMessage($template, $context, [$fromEmail['address'] => $fromEmail['sender_name']], $user->getEmail());
    }

    public function sendNewCardHashSuccessEmailMessage(RDV $rdv)
    {
        $template = 'KGCRdvBundle:Consultation:newCardHashSuccess.email.twig';

        $context = $this->getCommonParameters() + [
            'rdv' => $rdv
        ];

        $fromEmail = $this->configuration->get(['default', 'from_email']);

        $this->sendMessage($template, $context, [$fromEmail['address'] => $fromEmail['sender_name']], $rdv->getClient()->getMail());
    }
}