<?php

// src/KGC/RdvBundle/listener/StatsMyastroListener.php


namespace KGC\RdvBundle\Listener;

use KGC\RdvBundle\Entity\RDV;
use KGC\RdvBundle\Entity\Encaissement;
use KGC\RdvBundle\Events\RDVActionEvent;
use KGC\RdvBundle\Events\RDVEvents;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * StatsMyastro Listener
 * Ecouteur d'évènement pour gérer la communication des stats à myastro.
 *
 * @category Listener
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 *
 * @DI\Service("kgc.statsmyastro.listener")
 */
class StatsMyastroListener
{
    protected $logger;

    /**
     * Constructeur.
     *
     * @param type $logger
     *
     * @DI\InjectParams({
     *     "logger" = @DI\Inject("kgc.myastrobridge.logger")
     * })
     */
    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    /**
     * Envoi consultation : ajoute tracking_rdv.
     *
     * @param \KGC\RdvBundle\Entity\RDV $rdv
     * @param string                    $eventname nom de l'évènement pour le log
     */
    private function ajouterConsultation(RDV $rdv, $eventname)
    {
        $id_myastro = $rdv->getIdAstro();
        $id_tracker = $rdv->getSupport()->getIdtracker();
        if (isset($id_myastro) or isset($id_tracker)) {
            $url = 'http://api.myastro.fr/addAppointement/amount/'.$rdv->getTarification()->getMontantTotal().'/date/'.$rdv->getDateConsultation()->format('Y-m-d');
            $code_promo = $rdv->getCodePromo();
            if (isset($id_myastro)) {
                $url .= '/iduser/'.$id_myastro->getValeur();
            }
            if (!empty($code_promo)) {
                $url .= '/codepromo/'.$code_promo->getCode();
            }
            if (is_int($id_tracker)) {
                $url .= '/support/'.$id_tracker
                        .'/nom/'.urlencode($rdv->getClient()->getNom())
                        .'/prenom/'.urlencode($rdv->getClient()->getPrenom())
                        .'/daten/'.$rdv->getClient()->getDateNaissance()->format('d-m-Y')
                ;
                $mail = $rdv->getClient()->getMail();
                if (!empty($mail)) {
                    $url .= '/email/'.urlencode($rdv->getClient()->getMail());
                }
            }

            $query = json_decode(file_get_contents($url), true);
            if (!isset($query['error'])) {
                if (!isset($id_myastro)) {
                    $idastro = new \KGC\ClientBundle\Entity\IdAstro();
                    $idastro->create($rdv->getClient(), $rdv->getWebsite(), $query['IdAstro']);
                    $rdv->setIdAstro($idastro);
                }
                $rdv->setIdtransactionmyastro($query['IdTransaction']);
            }
            $this->logger->notice($eventname.' : rdv_id = '.$rdv->getId().', url = '.$url.' =====> '.$query['result']);
        }
    }

    /**
     * Envoi encaissement : ajoute tracking_payment.
     *
     * @param \KGC\RdvBundle\Entity\RDV          $rdv
     * @param \KGC\RdvBundle\Entity\Encaissement $enc
     * @param string                             $eventname nom de l'évènement pour le log
     */
    private function ajouterEncaissement(RDV $rdv, Encaissement $enc, $eventname)
    {
        if ($enc->getEtat() == Encaissement::DONE) {
            $id_transaction = $rdv->getIdtransactionmyastro();
            $url = 'http://api.myastro.fr/addPayment/idrdv/'.$id_transaction.'/price/'.$enc->getMontant().'/date/'.$enc->getDate()->format('Y-m-d');
            $query = json_decode(file_get_contents($url), true);
            $this->logger->notice($eventname.' : rdv_id = '.$rdv->getId().', enc_id = '.$enc->getId().', url = '.$url.' =====> '.$query['result']);
        }
    }

    /**
     * @param RDVActionEvent $event
     *
     * @DI\Observe(RDVEvents::onValidEncaissement)
     */
    public function onValidationEncaissement(RDVActionEvent $event)
    {
        $rdv = $event->getRDV();
        $enc = $event->getEncaissement();
        $id_transaction = $rdv->getIdtransactionmyastro();
        if (!empty($id_transaction) and $enc->getEtat() === Encaissement::DONE) {
            $this->ajouterEncaissement($rdv, $enc, 'OnValidationEncaissement');
        }
    }

    /**
     * @param RDVActionEvent $event
     *
     * @DI\Observe(RDVEvents::onConsultationEffectuee)
     */
    public function onConsultationEffectuee(RDVActionEvent $event)
    {
        $rdv = $event->getRDV();
        $this->ajouterConsultation($rdv, 'OnConsultationEffectuee');
    }

    /**
     * @param RDVActionEvent $event
     *
     * @DI\Observe(RDVEvents::onUpdateIdmyastro)
     */
    public function onUpdateIdmyastro(RDVActionEvent $event)
    {
        $idastro = $event->getRDV()->getIdAstro();
        foreach ($idastro->getConsultations() as $rdv) {
            $idt = $rdv->getIdtransactionmyastro();
            if (isset($idt)) {
                $url = 'http://api.myastro.fr/updateAppointement/idrdv/'.$rdv->getIdtransactionmyastro().'/idastro/'.$rdv->getIdAstro()->getValeur();
                $query = json_decode(file_get_contents($url), true);
                $this->logger->notice('OnUpdateIdmyastro : rdv_id = '.$rdv->getId().', idTransaction = '.$rdv->getIdtransactionmyastro().', url = '.$url.' =====> '.$query['result']);
            } else { // la consult n'avait jamais été envoyée
                if ($rdv->getConsultation() == 1) { // la consultation à bien été complétée
                    $this->ajouterConsultation($rdv, 'OnUpdateIdmyastro');
                    foreach ($rdv->getEncaissements() as $enc) {
                        $this->ajouterEncaissement($rdv, $enc, 'OnUpdateIdmyastro');
                    }
                }
            }
        }
    }

    /**
     * onUpdateWebsite.
     *
     * @param \KGC\RdvBundle\Events\RDVActionEvent $event
     *
     * @DI\Observe(RDVEvents::onUpdateWebsite)
     */
    public function onUpdateWebsite(RDVActionEvent $event)
    {
        $rdv = $event->getRDV();
        $idt = $rdv->getIdtransactionmyastro();
        $ida = $rdv->getIdAstro();
        if (isset($idt) && isset($ida)){
            $url = 'http://api.myastro.fr/updateAppointement/idrdv/'.$rdv->getIdtransactionmyastro().'/idastro/'.$rdv->getIdAstro()->getValeur();
            $query = json_decode(file_get_contents($url), true);
            $this->logger->notice('OnUpdateWebsite : rdv_id = '.$rdv->getId().', idTransaction = '.$rdv->getIdtransactionmyastro().', url = '.$url.' =====> '.$query['result']);
        }
    }

    /**
     * @param $event
     *
     * @DI\Observe(RDVEvents::onUpdateTarification)
     */
    public function onUpdateTarification($event)
    {
        $rdv = $event->getRDV();
        $idt = $rdv->getIdtransactionmyastro();
        if (isset($idt)) {
            $url = 'http://api.myastro.fr/updateAppointement/idrdv/'.$rdv->getIdtransactionmyastro().'/amount/'.$rdv->getTarification()->getMontantTotal();
            $query = json_decode(file_get_contents($url), true);
            $this->logger->notice('OnUpdateTarification : rdv_id = '.$rdv->getId().', idTransaction = '.$rdv->getIdtransactionmyastro().', url = '.$url.' =====> '.$query['result']);
        }
    }
}
