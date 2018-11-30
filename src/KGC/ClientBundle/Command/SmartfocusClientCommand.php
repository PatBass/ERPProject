<?php

namespace KGC\ClientBundle\Command;

use FOS\ElasticaBundle\Doctrine\Listener;
use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\ClientBundle\Entity\SmartFocus;
use KGC\CommonBundle\Service\UnpaidCalculator;
use KGC\PaymentBundle\Entity\Payment;
use KGC\RdvBundle\Entity\Encaissement;
use KGC\RdvBundle\Exception\Payment\CardException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SmartfocusClientCommand extends ContainerAwareCommand
{
    const NAME = 'kgestion:smartfocus:client';

    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Process to update smartfocus client')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $clientService = $this->getContainer()->get('kgc.client.manager');
        $clients = $clientService->getAllChatCLient();

        $header = ['EMAIL', 'CLIENT', 'DATEOFBIRTH', 'FIRSTNAME', 'LASTNAME', 'EMVCELLPHONE', 'NUMEROTELEPHONE', 'TITLE'];
        $header = array_merge($header, $clientService->getEntityManager()->getRepository('KGCSharedBundle:Client')->getSmartFields(24));
        $header = array_merge($header, $clientService->getEntityManager()->getRepository('KGCSharedBundle:Client')->getSmartFields(19));
        $header = array_merge($header, $clientService->getEntityManager()->getRepository('KGCSharedBundle:Client')->getSmartFields(null));
        $idx = 0;
        $nb = 1;
        $arrayNames = array();
        $fp = null;
        foreach ($clients as $aClient) {
            if($idx == 0){
                if($nb > 1 && !is_null($fp)) {
                    fclose($fp);
                }
                $shortName = 'client'.date('d-m-y').'_'.$nb.'.csv';
                $name = dirname(__FILE__).'/'.$shortName;
                $arrayNames[$shortName] = $name;
                $fp = fopen($name, 'w');

                fputcsv($fp, $header, ';');
                $idx = 50000;
                $nb++;
            }
            $toSend = array(
                str_replace(' ', '', $aClient['mail']),
                1,
                $aClient['dateNaissance'] && $aClient['dateNaissance']->format('Y') != "-0001" ? $aClient['dateNaissance']->format('d/m/Y') : '00/00/0000',
                $aClient['prenom'],
                $aClient['nom'],
                intval($aClient['numtel1']),
                $aClient['numtel1'],
                $aClient['genre'],
            );
            $toSend = array_merge($toSend, array_values($clientService->getEntityManager()->getRepository('KGCSharedBundle:Client')->getSmartfocusFields($aClient['mail'], 24))); //VPT
            $toSend = array_merge($toSend, array_values($clientService->getEntityManager()->getRepository('KGCSharedBundle:Client')->getSmartfocusFields($aClient['mail'], 19))); //TD
            $toSend = array_merge($toSend, array_values($clientService->getEntityManager()->getRepository('KGCSharedBundle:Client')->getSmartfocusFields($aClient['mail'], null))); //DRI
            fputcsv($fp, $toSend, ';');
            $idx--;
        }
        $smartfocus = new SmartFocus($clientService->getEntityManager());
        $smartfocus->updateMassClientsMembers($header, $arrayNames);
        foreach ($arrayNames as $arrayName) {
            unlink($arrayName);
        }
    }

}
