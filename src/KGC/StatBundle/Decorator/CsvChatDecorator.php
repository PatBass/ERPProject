<?php

namespace KGC\StatBundle\Decorator;

use JMS\DiExtraBundle\Annotation as DI;
use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\Bundle\SharedBundle\Entity\LandingUser;
use KGC\ChatBundle\Entity\ChatPayment;
use KGC\ChatBundle\Entity\ChatSubscription;
use KGC\RdvBundle\Entity\Encaissement;
use KGC\RdvBundle\Entity\Etat;
use KGC\RdvBundle\Entity\RDV;
use Symfony\Component\Intl\Data\Provider\LanguageDataProvider;

/**
 * Class CsvChatDecorator.
 *
 * @DI\Service("kgc.stat.chat.decorator.csv")
 */
class CsvChatDecorator implements DecoratorInterface
{

    const DELIMITER = ";";


    /**
     * @param $rdv
     * @return array
     */
    protected function getAboDetails($entity)
    {
        $line = null;
        $client = $entity->getClient();
        if($entity instanceof ChatSubscription) {
            $website = $entity->getWebsite();
            $prospect = $client->getLandingByWebsite($website);
        }
        else if($entity instanceof ChatPayment) {
            $website = $entity->getChatFormulaRate()->getChatFormula()->getWebsite();
            $prospect = $client->getLandingByWebsite($website);
        }
        $line = [
            $client->getId(),
            $client->getPrenom(),
            $client->getUsername(),
            $client->getGenre(),
            $client->getEmail(),
            !is_null($client->getNumtel1()) && $client->getNumtel1() != '' ? $client->getNumtel1() : $client->getNumtel2(),
            $client->getDateNaissance() ? $client->getDateNaissance()->format('d-m-Y') : '',
            $website->getLibelle(),
            $prospect ? $prospect->getMyastroSource() : '',
            $prospect ? $prospect->getMyastroUrl() : '',
            $client->getChatInfoSubject(),
            $client->getChatInfoPartner(),
            $client->getChatInfoAdvice(),
        ];

        return $line;
    }

    public static function getAboHeaders(){
        return [
            'ID client' ,
            'Prénom' ,
            'Pseudo',
            'Genre',
            'Email' ,
            'Téléphone',
            'Date de naissance' ,
            'Site' ,
            'Source' ,
            'URL' ,
            'Sujet principal' ,
            'Conjoint' ,
            'Conseil principal' ,
        ];
    }

    /**
     * @param $file
     * @param $data
     */
    protected function buildAboDetailsCsv(&$file, $data)
    {
        $header = CsvChatDecorator::getAboHeaders();
        if(count($data['list']) && ! current($data['list']) instanceof ChatSubscription && ! current($data['list']) instanceof ChatPayment) {
            array_splice($header, 1, 1);
        }
        fputcsv($file, $header, self::DELIMITER);

        if(!isset($data['list']) || ! count($data['list'])) {
            fputcsv($file, ['Aucun résultat'], self::DELIMITER);
        }
        else {
            foreach ($data['list'] as $value) {
                $line = $this->getAboDetails($value);

                fputcsv($file, $line, self::DELIMITER);
            }
        }
    }

    /**
     * @param array $dataToDecorate
     * @param array $config
     *
     * @return array
     */
    public function decorate(array $dataToDecorate, array $config)
    {
        $file = fopen('php://memory', 'r+');
        if(isset($config['abo_details']) && $config['abo_details']) {
            $this->buildAboDetailsCsv($file, $dataToDecorate);
        }

        rewind($file);
        $content = stream_get_contents($file);

        // Windows Excel encoding fix
        $encoded = mb_convert_encoding($content, 'UTF-16LE', 'UTF-8');

        return $encoded;
    }
}
