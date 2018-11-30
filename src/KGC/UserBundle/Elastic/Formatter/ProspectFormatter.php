<?php
/**
 * Created by PhpStorm.
 * User: niko
 * Date: 17/08/2016
 * Time: 12:27
 */

namespace KGC\UserBundle\Elastic\Formatter;

use JMS\DiExtraBundle\Annotation as DI;
use KGC\Bundle\SharedBundle\Entity\LandingUser;
use KGC\RdvBundle\Elastic\Formatter\FormatterInterface;

/**
 * Class ProspectFormatter.
 *
 * @DI\Service("kgc.elastic.prospect.prospect_formatter")
 */
class ProspectFormatter implements FormatterInterface
{
    public static $headers = [
        'ID' => '',
        'ID astro' => '',
        'Prénom' => '',
        'Genre' => '',
        'Date de naissance' => '',
        'Mail' => '',
        'Téléphone' => '',
        'Site' => '',
        'Source' => '',
        'URL' => '',
        'Code Promo' => '',
        'Voyant' => '',
        'Thème question' => '',
        'Question' => '',
        'Commentaires' => '',
        'Prénom conjoint' => '',
        'Date de naissance conjoint' => '',
        'Signe astrologique conjoint' => '',
    ];

    public function getHeaders()
    {
        return self::$headers;
    }

    /**
     * @param $data
     *
     * @return array
     */
    public function format($data)
    {
        if (!$data instanceof LandingUser) {
            throw new \InvalidArgumentException(
                sprintf('Parameter must be of type "LandingUser", "%s" given', gettype($data))
            );
        }

        $formatted = [
            'ID' => $data->getId(),
            'ID astro' => $data->getMyastroId(),
            'Prénom' => $data->getFirstName(),
            'Genre' => $data->getGender(),
            'Date de naissance' => $data->getBirthday() ? $data->getBirthday()->format('d/m/Y') : "",
            'Mail' => $data->getEmail(),
            'Téléphone' => $data->getPhone(),
            'Site' => $data->getWebsite() ? $data->getWebsite()->getLibelle() : $data->getMyastroWebsite(),
            'Source' => $data->getSourceConsult() ? $data->getSourceConsult()->getLabel() : $data->getMyastroSource(),
            'Code Promo' => $data->getCodePromo() ? $data->getCodePromo()->getCode() : $data->getMyastroPromoCode(),
            'Thème question' => $data->getQuestionSubject(),
            'Question' => $data->getQuestionText(),
            'Commentaires' => $data->getQuestionContent(),
            'Prénom conjoint' => $data->getSpouseName(),
            'Date de naissance conjoint' => $data->getSpouseBirthday() ? $data->getSpouseBirthday()->format('d/m/Y') : "",
            'Signe astrologique conjoint' => $data->getSpouseSign(),
        ];

        return array_merge(static::$headers, $formatted);
    }
}