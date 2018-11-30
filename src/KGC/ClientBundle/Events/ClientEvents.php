<?php

// src/KGC/ClientBundle/events/ClientEvents.php
namespace KGC\ClientBundle\Events;

/**
 * ClientEvents
 * Liste d'évènements sur client.
 *
 * @category Events
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
final class ClientEvents
{
    const onMailSend = 'client.rdv.send_mail';
    const onSmsSend = 'client.rdv.send_sms';
}
