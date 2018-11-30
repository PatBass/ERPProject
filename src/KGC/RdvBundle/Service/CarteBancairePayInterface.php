<?php

namespace KGC\RdvBundle\Service;

use KGC\RdvBundle\Entity\CarteBancaire;
use KGC\Bundle\SharedBundle\Entity\Client;

interface CarteBancairePayInterface
{
    /**
     * @param Client
     * @param CarteBancaire
     */
    public function payWithCartebancaire(Client $client, CarteBancaire $carteBancaire, $paymentGateway, $amount);
}