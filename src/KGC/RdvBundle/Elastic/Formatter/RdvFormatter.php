<?php

namespace KGC\RdvBundle\Elastic\Formatter;

use JMS\DiExtraBundle\Annotation as DI;
use KGC\RdvBundle\Entity\RDV;

/**
 * Class RdvFormatter.
 *
 * @DI\Service("kgc.elastic.rdv.rdv_formatter")
 */
class RdvFormatter implements FormatterInterface
{
    public static $headers = [
        'ID' => '',
        'ID astro' => '',
        'Nom' => '',
        'Prénom' => '',
        'Adresse' => '',
        'Date de naissance' => '',
        'Mail' => '',
        'Téléphone 1' => '',
        'Téléphone 2' => '',
        'Etat' => '',
        'Classement' => '',
        'Etiquettes' => '',
        'Site' => '',
        'Source' => '',
        'Url' => '',
        'Gclid' => '',
        'TPE' => '',
        'Date contact' => '',
        'Date consultation' => '',
        'Support' => '',
        'Code Promo' => '',
        'Consultant' => '',
        'Voyant' => '',
        'Tarification' => '',
        'Temps' => '',
        'Montant' => '',
        'Montant du' => '',
        'Etat du suivi' => '',
        'Affiliate' => '',
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
            'ID' => $data->getId(),
            'ID astro' => $data->getIdAstro(),
            'Adresse' => $data->getAdresse(),
            'Téléphone 1' => $data->getNumtel1(),
            'Téléphone 2' => $data->getNumtel2(),
            'Site' => $data->getWebsite(),
            'Source' => $data->getSource(),
            'Url' => $data->getFormUrl(),
            'Gclid' => $data->getGclid(),
            'TPE' => $data->getTpe(),
            'Etat' => $data->getEtat(),
            'Classement' => $data->getClassement(),
            'Date contact' => date_format($data->getDateContact(), 'd/m/Y'),
            'Date consultation' => date_format($data->getDateConsultation(), 'd/m/Y'),
            'Support' => $data->getSupport(),
            'Code Promo' => $data->getCodePromo(),
            'Voyant' => $data->getVoyant(),
        ];

        $consultant = $data->getConsultant();
        if (null !== $consultant) {
            $formatted = array_merge($formatted, [
                'Consultant' => $consultant->getUsername(),
            ]);
        }

        $client = $data->getClient();
        if (null !== $client) {
            $formatted = array_merge($formatted, [
                'Nom' => $data->getClient()->getNom(),
                'Prénom' => $data->getClient()->getPrenom(),
                'Date de naissance' => date_format($data->getClient()->getDateNaissance(), 'd/m/Y'),
                'Mail' => $data->getClient()->getMail(),
            ]);
        }

        $etiquettes = $data->getEtiquettes();
        if (!empty($etiquettes)) {
            $formatted = array_merge($formatted, [
                'Etiquettes' => implode(' - ', $etiquettes->toArray()),
            ]);
        }

        $tarification = $data->getTarification();
        if (null !== $tarification) {
            $formatted = array_merge($formatted, [
                'Tarification' => $data->getTarification()->getCode(),
                'Temps' => $data->getTarification()->getTemps(),
                'Montant' => $data->getTarification()->getMontantTotal(),
                'Montant du' => $data->getTarification()->getMontantTotal() - $data->getMontantEncaisse()
            ]);
        }
        $formatted = array_merge($formatted, ['Etat du suivi' => $data->getReminderState() ? $data->getReminderState()->getLabel() : 'Etat du suivi']);
        $formatted = array_merge($formatted, ['Affiliate' => $data->getProspect() ? $data->getProspect()->getReflexAffilateId() : '']);

        return array_merge(static::$headers, $formatted);
    }
}
