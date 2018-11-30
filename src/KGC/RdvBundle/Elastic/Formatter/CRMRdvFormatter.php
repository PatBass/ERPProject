<?php

// src/KGC/RdvBundle/Elastic/Formatter/CRMRdvFormatter.php
namespace KGC\RdvBundle\Elastic\Formatter;

use JMS\DiExtraBundle\Annotation as DI;
use KGC\RdvBundle\Entity\RDV;

/**
 * Class CRMRdvFormatter.
 *
 * @DI\Service("kgc.elastic.rdv.crmrdv_formatter")
 */
class CRMRdvFormatter implements FormatterInterface
{
    public static $headers = [
        'ID Astro' => '',
        'Date' => '',
        'Prenom' => '',
        'Sexe' => '',
        'Signe' => '',
        'Telephone' => '',
        'Conjoint' => '',
        'Signe conjoint' => '',
        'Question' => '',
        'Numero_question' => '',
        'Domaine' => '',
        'Site' => '',
        'Source' => '',
        'Url' => '',
        'Voyant' => '',
        'Email' => '',
        'Date de naissance' => '',
        'Pays' => '',
        'Gclid' => '',
        'Reflex ID' => '',
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
        if (!$data instanceof RDV) {
            throw new \InvalidArgumentException(
                sprintf('Parameter must be of type "RDV", "%s" given', gettype($data))
            );
        }

        $formatted = [
            'ID Astro' => $data->getIdAstro(),
            'Date' => date_format($data->getDateConsultation(), 'd/m/Y'),
            'Prenom' => $data->getClient()->getPrenom(),
            'Telephone' => str_replace(['-', ' ', '.', '/'], '', $data->getNumtel1()),
            'Voyant' => $data->getVoyant(),
            'Email' => $data->getClient()->getMail(),
            'Date de naissance' => date_format($data->getClient()->getDateNaissance(), 'd/m/Y'),
            'Site' => $data->getWebsite()->getLibelle(),
            'Source' => $data->getSource() ? $data->getSource()->getLabel() : null,
            'Url' => $data->getFormUrl() ? $data->getFormUrl()->getLabel() : null,
            'Gclid' => $data->getGclid(),
            'Reflex ID' => !is_null($data->getProspect()) ? $data->getProspect()->getReflexAffilateId() : '',
        ];

        switch ($data->getClient()->getGenre()) {
            case 'M' : $formatted['Sexe'] = 'homme'; break;
            case 'F' : $formatted['Sexe'] = 'femme'; break;
        }

        return array_merge(static::$headers, $formatted);
    }
}
