<?php
/**
 * Created by PhpStorm.
 * User: niko
 * Date: 17/08/2016
 * Time: 12:27
 */

namespace KGC\ClientBundle\Elastic\Formatter;

use JMS\DiExtraBundle\Annotation as DI;
use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\Bundle\SharedBundle\Entity\LandingUser;
use KGC\RdvBundle\Elastic\Formatter\FormatterInterface;

/**
 * Class ClientFormatter.
 *
 * @DI\Service("kgc.elastic.client.client_formatter")
 */
class ClientFormatter implements FormatterInterface
{
    public static $headers = [
        'ID' => '',
        'Prénom' => '',
        'Nom' => '',
        'Genre' => '',
        'Date de naissance' => '',
        'Mail' => '',
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
        if (!$data instanceof Client) {
            throw new \InvalidArgumentException(
                sprintf('Parameter must be of type "Client", "%s" given', gettype($data))
            );
        }

        $formatted = [
            'ID' => $data->getId(),
            'Prénom' => $data->getPrenom(),
            'Nom' => $data->getNom(),
            'Genre' => $data->getGenre(),
            'Date de naissance' => $data->getDateNaissance() ? $data->getDateNaissance()->format('d/m/Y') : "",
            'Mail' => $data->getEmail(),
        ];

        return array_merge(static::$headers, $formatted);
    }
}