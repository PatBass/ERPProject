<?php

namespace KGC\RdvBundle\Tests\Units\Mock\Service;

use KGC\RdvBundle\Entity\CarteBancaire;
use KGC\RdvBundle\Service\CarteBancairePayInterface;
use KGC\Bundle\SharedBundle\Entity\Client;

class CarteBancairePayMock implements CarteBancairePayInterface
{
    /**
     * @inheritdoc
     */
    public function payWithCartebancaire(Client $client, CarteBancaire $carteBancaire, $paymentGateway, $amount)
    {
        throw new \Exception('Unimplemented method');
    }
}