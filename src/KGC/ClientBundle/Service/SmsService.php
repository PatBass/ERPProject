<?php

namespace KGC\ClientBundle\Service;

use JMS\DiExtraBundle\Annotation as DI;
use KGC\ClientBundle\Entity\CampagneSms;
use KGC\ClientBundle\Entity\SmsSent;
use KGC\UserBundle\Entity\Poste;
use Psr\Log\LoggerInterface;

/**
 * @DI\Service("kgc.client.sms.service")
 */
final class SmsService
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    private $api;
    private $app;


    /**
     * @param LoggerInterface $logger
     *
     * @DI\InjectParams({
     *      "logger" = @DI\Inject("logger"),
     *     "login" = @DI\Inject("%callr.login%"),
     *     "password" = @DI\Inject("%callr.password%"),
     * })
     */
    public function __construct(LoggerInterface $logger, $login, $password)
    {
        $this->logger = $logger;
        $this->api = new \CALLR\API\Client;
        $this->api->setAuthCredentials($login, $password);
    }

    public function createApp(){
        try {
//            $app = $this->api->call('apps.create', ['CLICKTOCALL10', 'KGESTION', NULL]);
            $this->app = "AL1OCWNJ";
        } catch (\Exception $e) {
            $this->logger->error(sprintf('', $e->getMessage()));
        }
        return false;
    }

    public function filterPhone($phone) {
        if(substr($phone, 0, 2) == '00') {
            $phone = '+'.substr($phone, 2);
        }
        if(substr($phone, 0, 2) == '33') {
            $phone = '+'.$phone;
        }
        if($phone != 'BLOCKED' && substr($phone, 0, 1) != '+' && substr($phone, 0, 3) != '003' && strlen($phone) <= 10) {
            $phone = '+33'.substr($phone, 1);
        }else if(substr($phone, 0, 1) != '+') {
            $phone = '+'.$phone;
        }
        $phone = str_replace('-','',$phone);
        $phone = str_replace(' ','',$phone);
        $phone = str_replace('.','',$phone);
        $phone = str_replace('_','',$phone);
        $phone = str_replace('tel:','',$phone);
        return $phone;
    }

    public function makeCall(Poste $poste, $phoneClient) {
        $phoneAgent = $this->filterPhone($poste->getPhone());
        $phoneClient = $this->filterPhone($phoneClient);
        if($phoneAgent != "" && $phoneClient != "") {
            $this->createApp();
            if($poste->getCli() && $poste->getCli() != "" && $poste->getCli() != 'BLOCKED') {
                $options = (object) [
                    'cli' => $this->filterPhone($poste->getCli())
                ];
            } else {
                $options = null;
            }

            $agent_phone = [
                'number' => $phoneAgent,
                'timeout' => 30
            ];

            $client_phone = [
                'number' => $phoneClient,
                'timeout' => 60
            ];
            try {
                $call_id = $this->api->call('clicktocall/calls.start_2', [$this->app, [$agent_phone],[$client_phone], $options]);
                return true;
            } catch (\Exception $e) {
                echo "<pre>";
                print_r($e->getMessage());
                echo "</pre>";
                $this->logger->error(sprintf('', $e->getMessage()));
            }
        }
        return false;
    }

    public function sendSms(SmsSent $SmsSent, $website_id = null)
    {
        $phoneClient = $this->filterPhone($SmsSent->getPhone());
        $sender = 'ASTRO';
        switch ($website_id) {
            case 8:
                $sender = 'Tchat';
                break;
            case 1:
                $sender = 'My Astro';
                break;
            case 9:
                $sender = 'Voyance';
                break;
            case 19:
                $sender = 'TarotDirect';
                break;
        }
        $sent = 0;
        try {
            $sent = $this->api->call('sms.send', [ $sender, $phoneClient, $SmsSent->getText(), null ]);
        } catch (\Exception $e) {
            $SmsSent->setStatus(SmsSent::STATUS_ERROR);
            $SmsSent->setPhone($phoneClient);
            $SmsSent->setErrorMsg($e->getMessage());
            $this->logger->error(sprintf('', $e->getMessage()));
            return false;
        }
        if (0 === $sent) {
            $SmsSent->setStatus(SmsSent::STATUS_ERROR);
            $SmsSent->setErrorMsg('No Exception, but sms not sent');
            $this->logger->error('No Exception, but sms not sent');
            return false;
        }

        $SmsSent->setStatus(SmsSent::STATUS_SUCCESS);

        return true;
    }

    public function sendCampagne(CampagneSms $campagne)
    {
        $sent = $error = 0;
        $aError = array();
        if ($campagne && $campagne->getList()) {
            foreach ($campagne->getList()->getContacts() as $contact) {
                $campagneTxt = $campagne->getText();
                $sender = $campagne->getSender();
                $phoneClient = $this->filterPhone($contact->getPhone());
                $firstname = $contact->getFirstname();
                $lastname = $contact->getLastName();
                $campagneTxt = str_replace('[PRENOM]', $firstname, $campagneTxt);
                $campagneTxt = str_replace('[NOM]', $lastname, $campagneTxt);
                try {
                    $this->api->call('sms.send', [$sender, $phoneClient, $campagneTxt, null]);
                    $sent++;
                } catch (\Exception $e) {
                    $error++;
                    $aError[] = array('phone' => $phoneClient, 'fullname' => $contact->fullname());
                }
            }
        }
        return array('nbSent' => $sent, 'nbError' => $error, 'errors' => $aError);
    }

}
