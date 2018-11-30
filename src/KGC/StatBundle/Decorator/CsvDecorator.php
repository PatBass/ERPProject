<?php

namespace KGC\StatBundle\Decorator;

use JMS\DiExtraBundle\Annotation as DI;
use KGC\RdvBundle\Entity\Encaissement;
use KGC\RdvBundle\Entity\Etat;
use KGC\RdvBundle\Entity\RDV;

/**
 * Class CsvDecorator.
 *
 * @DI\Service("kgc.stat.decorator.csv")
 */
class CsvDecorator implements DecoratorInterface
{

    const DELIMITER = ";";


    /**
     * @param $rdv
     * @return array
     */
    protected function getRdvDetails($rdv)
    {
        $securisation = $consultation = $consultant = $encaissement = "";
        $etatConsultation = "Etat du suivi";

        if($rdv->getEtat() !== null) {
            switch ($rdv->getSecurisation()) {
                case RDV::SECU_PENDING:
                    if ($rdv->getEtat()->getIdCode() !== Etat::CANCELLED) {
                        $securisation = "En attente";
                    }
                    break;
                case RDV::SECU_DONE:
                    $securisation = "Effectuée";
                    break;
                case RDV::SECU_SKIPPED:
                    $securisation = "Passée";
                    break;
                default:
                    $securisation = "Refusée";
                    break;
            }
            if($rdv->getMiserelation() === null) {
                if ($rdv->getEtat()->getIdCode() === Etat::CANCELLED) {
                    if($rdv->getSecurisation() !== RDV::SECU_DENIED) {
                        $consultation = "Annulée";
                    }
                }
                else {
                    $consultation = "En attente de lien";
                }
            }
            elseif($rdv->getMiserelation()) {
                if($rdv->getConsultation() === null) {
                    $consultation = "En attente de prise en charge";
                    if($rdv->getPriseencharge()) {
                        $consultation = "Pris en charge";
                    }
                    elseif($rdv->getEtat()->getIdCode() == Etat::PAUSED) {
                        $consultation = "Pause";
                    }
                }
                elseif($rdv->getConsultation()) {
                    $consultation = "Effectuée";
                    if($rdv->getCloture() === null) {
                        $encaissement = $rdv->getMontantEncaisse() === 0 ? "Impayé total" : "Partiel";
                    }
                    elseif($rdv->getCloture()) {
                        $encaissement = $rdv->getTarification()->getMontantTotal() === 0 ? "Gratuit" : "Effectué";
                    }
                    else {
                        $encaissement = "Abandonné";
                    }
                }
                else {
                    $consultation = "Annulée";
                }
            }
        }

        if($rdv->getConsultant() !== null) {
            $consultant = $rdv->getConsultant()->getUsername();
        }
        if($rdv->getReminderState() !== null) {
            $etatConsultation = $rdv->getReminderState()->getLabel();
        }

        $line = [
            $rdv->getId(),
            $rdv->getClient()->getNom(),
            $rdv->getClient()->getPrenom(),
            $rdv->getDateConsultation()->format('d/m/Y H:i'),
            $securisation,
            $consultation,
            $encaissement,
            $consultant,
            $etatConsultation
        ];

        return $line;
    }
    /**
     * @param $rdv
     * @return array
     */
    protected function getNewRdvDetails($rdv, $header = [])
    {
        $formatted = [
            'ID' => $rdv->getId(),
            'ID astro' => $rdv->getIdAstro(),
            'Adresse' => $rdv->getAdresse(),
            'Téléphone 1' => $rdv->getNumtel1(),
            'Téléphone 2' => $rdv->getNumtel2(),
            'Site' => $rdv->getWebsite(),
            'Source' => $rdv->getSource(),
            'Url' => $rdv->getFormUrl(),
            'Gclid' => $rdv->getGclid(),
            'TPE' => $rdv->getTpe(),
            'Etat' => $rdv->getEtat(),
            'Classement' => $rdv->getClassement(),
            'Date contact' => date_format($rdv->getDateContact(), 'd/m/Y'),
            'Date consultation' => date_format($rdv->getDateConsultation(), 'd/m/Y'),
            'Support' => $rdv->getSupport(),
            'Code Promo' => $rdv->getCodePromo(),
            'Voyant' => $rdv->getVoyant(),
        ];

        $consultant = $rdv->getConsultant();
        if (null !== $consultant) {
            $formatted = array_merge($formatted, [
                'Consultant' => $consultant->getUsername(),
            ]);
        }

        $client = $rdv->getClient();
        if (null !== $client) {
            $formatted = array_merge($formatted, [
                'Nom' => $rdv->getClient()->getNom(),
                'Prénom' => $rdv->getClient()->getPrenom(),
                'Date de naissance' => date_format($rdv->getClient()->getDateNaissance(), 'd/m/Y'),
                'Mail' => $rdv->getClient()->getMail(),
            ]);
        }

        $etiquettes = $rdv->getEtiquettes();
        if (!empty($etiquettes)) {
            $formatted = array_merge($formatted, [
                'Etiquettes' => implode(' - ', $etiquettes->toArray()),
            ]);
        }

        $tarification = $rdv->getTarification();
        if (null !== $tarification) {
            $formatted = array_merge($formatted, [
                'Tarification' => $rdv->getTarification()->getCode(),
                'Temps' => $rdv->getTarification()->getTemps(),
                'Montant' => $rdv->getTarification()->getMontantTotal(),
                'Montant du' => $rdv->getTarification()->getMontantTotal() - $rdv->getMontantEncaisse()
            ]);
        }
        $formatted = array_merge($formatted, ['Etat du suivi' => $rdv->getReminderState() ? $rdv->getReminderState()->getLabel() : 'Etat du suivi']);
        $formatted = array_merge($formatted, ['Affiliate' => $rdv->getProspect() ? $rdv->getProspect()->getReflexAffilateId() : '']);

        $line = [];
        foreach ($header as $head){
            $line[] = isset($formatted[$head]) ? $formatted[$head] : '';
        }

        return $line;
    }

    /**
     * @param $enc
     * @return array
     */
    protected function getEncaissementDetails($enc)
    {
        $l_csv = [
            $enc['rdv'],
            $enc['nb'] . ' x ',
            $enc['amount'] / 100,
            $enc['user']
        ];

        return $l_csv;
    }

    public static function getRdvHeaders(){
        return [
            'ID',
            'ID astro' ,
            'Nom' ,
            'Prénom' ,
            'Adresse' ,
            'Date de naissance' ,
            'Mail' ,
            'Téléphone 1' ,
            'Téléphone 2' ,
            'Etat' ,
            'Classement' ,
            'Etiquettes' ,
            'Site' ,
            'Source' ,
            'Url' ,
            'Gclid' ,
            'TPE' ,
            'Date contact' ,
            'Date consultation' ,
            'Support' ,
            'Code Promo' ,
            'Consultant' ,
            'Voyant' ,
            'Tarification' ,
            'Temps' ,
            'Montant' ,
            'Montant du',
            'Etat du suivi',
            'Affiliate'
        ];
    }

    /**
     * @param $file
     * @param $data
     */
    protected function buildSpecificRdvDetailsCsv(&$file, $data)
    {
        $header = CsvDecorator::getRdvHeaders();
        fputcsv($file, $header, self::DELIMITER);

        foreach ($data['details'] as $rdv) {
            fputcsv($file, $this->getNewRdvDetails($rdv, $header), self::DELIMITER);
        }
    }

    /**
     * @param $file
     * @param $data
     */
    protected function buildSpecificCADetailsCsv(&$file, $data)
    {
        $header = ["Id rdv", "Nombre", "Encaissement", "Utilisateur"];
        fputcsv($file, $header, self::DELIMITER);
        foreach ($data['details'] as $enc) {
            fputcsv($file, $this->getEncaissementDetails($enc), self::DELIMITER);
        }
    }

    /**
     * @param $file
     * @param $data
     */
    protected function buildSpecificFullCsv(&$file, $data)
    {
        $headers = [];
        foreach($data['headers'] as $data_header) {
            $headers[] = $data_header['label'];
            if($data_header['colSize'] == 2) {
                $headers[] = $data_header['label'] . ' %';
            }
        }
        fputcsv($file, $headers, self::DELIMITER);

        foreach($data['lines'] as $data_line) {
            $line = [];
            foreach($data_line['headers'] as $line_header) {
                $line[] = $line_header['label'];
            }
            foreach($data_line['values'] as $line_value) {
                $line[] = $line_value['value'];

                if($line_value['ratio'] !== null) {
                    $line[] = round($line_value['ratio'], 2) . '%';
                }
            }
            fputcsv($file, $line, self::DELIMITER);
        }
    }

    /**
     * @param $file
     * @param $data
     */
    protected function buildRoiDetailsCsv(&$file, $data)
    {
        $header = ["Id rdv", "Nombre", "Encaissement", "Utilisateur"];
        fputcsv($file, $header, self::DELIMITER);
        foreach($data['details'] as $enc) {
            foreach ($enc as $line) {
                foreach ($line as $value) {
                    fputcsv($file, $this->getEncaissementDetails($value), self::DELIMITER);
                }
            }
        }
    }

    /**
     * @param $file
     * @param $data
     */
    protected function buildQualiteConsultationCsv(&$file, $data)
    {
        $header = CsvDecorator::getRdvHeaders();
        fputcsv($file, $header, self::DELIMITER);
        foreach ($data as $i_d => $day) {
            foreach ($day as $i_t => $time) {
                foreach ($time as $rdv) {
                    fputcsv($file, $this->getNewRdvDetails($rdv, $header), self::DELIMITER);
                }
            }
        }
    }

    /**
     * @param $file
     * @param $data
     */
    protected function buildStandardDetailsCsv(&$file, $data)
    {
        $header = ["Id rdv", "Date", "Client", "Montant"];
        if(count($data['list']) && ! current($data['list']) instanceof Encaissement && ! current($data['list']) instanceof RDV) {
            array_splice($header, 1, 1);
        }
        fputcsv($file, $header, self::DELIMITER);

        if(!isset($data['list']) || ! count($data['list'])) {
            fputcsv($file, ['Aucun résultat'], self::DELIMITER);
        }
        else {
            foreach ($data['list'] as $value) {
                if($value instanceof Encaissement) {
                    $line = [
                        $value->getConsultation()->getId(),
                        $value->getDate()->format('d/m/Y H:i'),
                        $value->getConsultation()->getClient()->getPrenom() . ' ' . $value->getConsultation()->getClient()->getNom(),
                        $value->getMontant() . " € par " . $value->getMoyenPaiement()->getLibelle()
                    ];
                }
                elseif($value instanceof RDV) {
                    $line = [
                        $value->getId(),
                        $value->getDateConsultation()->format('d/m/Y H:i'),
                        $value->getClient()->getPrenom() . ' ' . $value->getClient()->getNom(),
                        $value->getMontantEncaisse()
                    ];
                }
                else {
                    $line = [
                        $value['consultation']->getId(),
                        $value['consultation']->getClient()->getPrenom() . ' ' .  $value['consultation']->getClient()->getNom(),
                        $value['montantImpaye']
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
        if(isset($config['ca_details']) && $config['ca_details']) {
            $this->buildSpecificCADetailsCsv($file, $dataToDecorate);
        }
        elseif(isset($config['rdv_details']) && $config['rdv_details']) {
           $this->buildSpecificRdvDetailsCsv($file, $dataToDecorate);
        }
        elseif(isset($config['specific_full']) && $config['specific_full']) {
            $this->buildSpecificFullCsv($file, $dataToDecorate);
        }
        elseif(isset($config['roi_details']) && $config['roi_details']) {
            $this->buildRoiDetailsCsv($file, $dataToDecorate);
        }
        elseif(isset($config['qualite_consultation']) && $config['qualite_consultation']) {
            $this->buildQualiteConsultationCsv($file, $dataToDecorate);
        }
        elseif(isset($config['standard_details']) && $config['standard_details']) {
            $this->buildStandardDetailsCsv($file, $dataToDecorate);
        }

        rewind($file);
        $content = stream_get_contents($file);

        // Windows Excel encoding fix
        $encoded = mb_convert_encoding($content, 'UTF-16LE', 'UTF-8');

        return $encoded;
    }
}
