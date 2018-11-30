<?php

// src/KGC/RdvBundle/events/RDVEvents.php
namespace KGC\RdvBundle\Events;

/**
 * RDVEvents
 * Liste d'évènements sur consultation.
 *
 * @category Events
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
final class RDVEvents
{
    const onAjoutConsult = 'kgc.rdv.ajout';
    const onCancel = 'kgc.rdv.annulation';
    const onSecurisation = 'kgc.rdv.securisation';
    const onUpdateSecurisation = 'kgc.rdv.update_securisation';
    const onMiserelation = 'kgc.rdv.miserelation';
    const onPriseenCharge = 'kgc.rdv.priseencharge';
    const onValidEncaissement = 'kgc.rdv.validencaissement';
    const onConsultationEffectuee = 'kgc.rdv.consultationeffectuee';
    const onUpdateIdmyastro = 'kgc.rdv.updidmyastro';
    const onUpdateTarification = 'kgc.rdv.updtarification';
    const onReactivation = 'kgc.rdv.reactivation';
    const onValidation = 'kgc.rdv.validation';
    const onUpdateClassement = 'kgc.rdv.updclassement';
    const onPauseConsult = 'kgc.rdv.pause';
    const onReportConsult = 'kgc.rdv.report';
    const onCancelPayment = 'kgc.payment.cancel';
    const onUpdateWebsite = 'kgc.rdv.updwebsite';
    const onUpdateFacturation = 'kgc.rdv.updfacturation';
    const onMailSend = 'kgc.rdv.send_mail';
    const onSmsSend = 'kgc.rdv.send_sms';
}
