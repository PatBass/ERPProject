<?php

namespace KGC\CommonBundle\Mailer;

use JMS\DiExtraBundle\Annotation as DI;
use KGC\ClientBundle\Entity\MailSent;
use Psr\Log\LoggerInterface;

/**
 * Class Mailer.
 *
 * @DI\Service("kgc.common.mailer")
 */
class Mailer
{
    /**
     * @var \Swift_Mailer
     */
    protected $mailer;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $from;

    /**
     * @var string
     */
    protected $cci;

    /**
     * @param $to
     * @param $subject
     * @param $body
     * @param array $attachments
     *
     * @return int
     */
    protected function sendMessage($to, $subject, $body, $attachments = [])
    {
        for ($i = 2; $i >= 0; --$i) {
            $body = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $body);
        }

        $mail = \Swift_Message::newInstance();
        $mail
            ->setFrom($this->from)
            ->setTo($to)
            ->setSubject($subject)
            ->setBody($body)
            ->setContentType('text/html; charset=UTF-8')
            ->addPart(strip_tags($body), 'text/plain')
        ;

        if (!empty($this->cci)) {
            $mail->setBcc($this->cci);
        }

        if (!empty($attachments)) {
            foreach ($attachments as $a) {
                $part = \Swift_Attachment::newInstance($a['stream'], $a['name'], $a['mime']);
                $mail->attach($part);
            }
        }

        return $this->mailer->send($mail);
    }

    /**
     * @param \Swift_Mailer   $mailer
     * @param LoggerInterface $logger
     * @param $from
     * @param $cci
     *
     * @DI\InjectParams({
     *      "mailer" = @DI\Inject("swiftmailer.mailer.second_mailer"),
     *      "logger" = @DI\Inject("logger"),
     *      "from" = @DI\Inject("%kgc.mail.from%"),
     *      "cci" = @DI\Inject("%kgc.mail.cci%"),
     * })
     */
    public function __construct(\Swift_Mailer $mailer, LoggerInterface $logger, $from, $cci)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->from = $from;
        $this->cci = $cci;
    }

    /**
     * @param MailSent $MailSent
     * @param $email
     *
     * @return bool
     */
    public function sendConsultationMail(MailSent $MailSent, $email)
    {
        $sent = 0;
        try {
            $sent = $this->sendMessage(
                $email,
                $MailSent->getSubject(),
                $MailSent->getHtml(),
                $MailSent->getAttachments()
            );
        } catch (\Exception $e) {
            // Error
            $MailSent->setStatus(MailSent::STATUS_ERROR);
            $MailSent->setErrorMsg($e->getMessage());
            $this->logger->error(sprintf('', $e->getMessage()));

            return false;
        }

        if (0 === $sent) {
            $MailSent->setStatus(MailSent::STATUS_ERROR);
            $MailSent->setErrorMsg('No Exception, but mail not sent');
            $this->logger->error('No Exception, but mail not sent');

            return false;
        }

        $MailSent->setStatus(MailSent::STATUS_SUCCESS);

        return true;
    }
}
