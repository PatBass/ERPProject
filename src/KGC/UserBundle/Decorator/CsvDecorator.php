<?php

namespace KGC\UserBundle\Decorator;

use JMS\DiExtraBundle\Annotation as DI;
use KGC\Bundle\SharedBundle\Entity\LandingUser;

/**
 * Class CsvDecorator.
 *
 * @DI\Service("kgc.prospect.decorator.csv")
 */
class CsvDecorator implements DecoratorInterface
{

    const DELIMITER = ";";

    /**
     * @param $file
     * @param $data
     */
    protected function buildStandardDetailsCsv(&$file, $data)
    {
        $header = ["Id prospect", "Date", "ID astro", "Site", "Source", "Url", "Support", "Prénom", "E-mail", "Genre", "Date de naissance", "Signe", "Téléphone", "Pays", "Prénom du conjoint", "Signe du conjoint", "Date de naissance du conjoint", "Date de la question", "Thème de la question", "Question", "Commentaires", "Gclid", "Code promo", "Affiliate (Reflex)", "Source (Reflex)"];
        fputcsv($file, $header, self::DELIMITER);

        if (!isset($data['list']) || !count($data['list'])) {
            fputcsv($file, ['Aucun résultat'], self::DELIMITER);
        } else {
            foreach ($data['list'] as $value) {
                if ($value instanceof LandingUser) {
                    $line = [
                        $value->getId(),
                        !is_null($value->getCreatedAt()) ? $value->getCreatedAt()->format("d/m/Y H:i:s") : "",
                        $value->getMyastroId(),
                        $value->getWebsite() ? $value->getWebsite()->getLibelle() : $value->getMyastroWebsite(),
                        $value->getSource() ? $value->getSource()->getLabel() : $value->getMyastroSource(),
                        $value->getFormurl() ? $value->getFormurl()->getLabel() : $value->getMyastroUrl(),
                        $value->getSupport() ? $value->getSupport()->getLibelle() : $value->getMyastroSupport(),
                        $value->getFirstName(),
                        $value->getEmail(),
                        $value->getGender()=='F'?'Femme':'Homme',
                        !is_null($value->getBirthday()) ? $value->getBirthday()->format("d/m/Y") : "",
                        $value->getSign(),
                        $value->getPhone(),
                        $value->getCountry(),
                        $value->getSpouseName(),
                        $value->getSpouseSign(),
                        !is_null($value->getSpouseBirthday()) ? $value->getSpouseBirthday()->format("d/m/Y") : "",
                        !is_null($value->getQuestionDate()) ? $value->getQuestionDate()->format("d/m/Y H:i:s") : "",
                        $value->getQuestionSubject(),
                        $value->getQuestionText(),
                        $value->getQuestionContent(),
                        $value->getMyastroGclid(),
                        $value->getCodePromo() ? $value->getCodePromo()->getCode() : $value->getMyastroPromoCode(),
                        $value->getReflexAffilateId(),
                        $value->getReflexSource(),
                    ];
                }
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
        if (isset($config['standard_details']) && $config['standard_details']) {
            $this->buildStandardDetailsCsv($file, $dataToDecorate);
        }

        rewind($file);
        $content = stream_get_contents($file);

        // Windows Excel encoding fix
        $encoded = mb_convert_encoding($content, 'UTF-16LE', 'UTF-8');

        return $encoded;
    }
}
