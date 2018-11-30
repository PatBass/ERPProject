<?php

namespace KGC\ClientBundle\Service;

use Doctrine\Common\Persistence\ObjectManager;
use JMS\DiExtraBundle\Annotation as DI;
use KGC\ClientBundle\Events\ClientActionEvent;
use Symfony\Component\Form\FormInterface;
use KGC\ClientBundle\Events\ClientEvents;
use KGC\CommonBundle\Mailer\Mailer;

/**
 * @DI\Service("kgc.client.manager")
 */
class ClientManager
{
    /**
     * @var ObjectManager
     */
    protected $entityManager;

    /**
     * @param ObjectManager $entityManager
     * @param Mailer          $mailer
     * @param SmsService      $smsApi
     *
     * @DI\InjectParams({
     *     "entityManager"  = @DI\Inject("doctrine.orm.entity_manager"),
     *     "mailer"          = @DI\Inject("kgc.common.mailer"),
     *     "smsApi"  = @DI\Inject("kgc.client.sms.service"),
     * })
     */
    public function __construct(
        ObjectManager $entityManager,
        Mailer $mailer,
        SmsService $smsApi
    )
    {
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->smsApi = $smsApi;
    }

    public function getCLientId($id)
    {
        return $this->entityManager->getRepository('KGCSharedBundle:Client')->find($id);
    }

    public function getAllChatCLient()
    {
        return $this->entityManager->getRepository('KGCSharedBundle:Client')->getAllChatClient();
    }

    /**
     * @return ObjectManager $entityManager
     */
    public function getEntityManager() {
        return $this->entityManager;
    }

    /**
     * @param \KGC\ClientBundle\Events\ClientActionEvent $event
     *
     * @DI\Observe(ClientEvents::onMailSend)
     */
    public function mail_send(ClientActionEvent $event)
    {
        $sentMailForm = $event->getForm()->get('mail_sent');
        $client = $event->getClient();
        $website = $event->getWebsite();
        $user = $event->getUser();
        if ($sentMailForm && $client && $user) {
            $sentMail = $sentMailForm->getData();
            $mailObject = $sentMailForm->get('mail')->getData();

            $file = $sentMailForm->get('file')->getData();
            if ($file) {
                $sentMail->addAttachment([
                    'stream' => file_get_contents($file),
                    'name' => $file->getClientOriginalName(),
                    'mime' => $file->getClientMimeType(),
                ]);
            }

            $this->mailer->sendConsultationMail($sentMail, $client->getMail());

            $client->addMailSents($sentMail);
            $this->entityManager->persist($client);
            $this->entityManager->persist($sentMail);
        }
    }

    /**
     * @param \KGC\ClientBundle\Events\ClientActionEvent $event
     *
     * @DI\Observe(ClientEvents::onSmsSend)
     */
    public function sms_send(ClientActionEvent $event)
    {
        $sentSmsForm = $event->getForm()->get('sms_sent');
        $client = $event->getClient();
        $website = $event->getWebsite();
        $user = $event->getUser();
        if ($sentSmsForm && $client && $user) {
            $sentSms = $sentSmsForm->getData();
            $smsObject = $sentSmsForm->get('sms')->getData();

            $result = $this->smsApi->sendSms($sentSms, $website ? $website->getId() : null);

            $client->addSmsSents($sentSms);
            $this->entityManager->persist($client);
            $this->entityManager->persist($smsObject);
        }
    }
}
