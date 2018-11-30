<?php

namespace KGC\RdvBundle\Tests\Units\Service;

use KGC\RdvBundle\Entity\CarteBancaire;
use KGC\RdvBundle\Service\CarteBancaireManager as testedClass;
use KGC\RdvBundle\Service\Encryption;
use KGC\RdvBundle\Tests\Units\Mock\Service\CarteBancairePayMock;
use atoum\test;

class CarteBancaireManager extends test
{
    /**
     * @return Encryption
     */
    protected function getEncryptionService()
    {
        return new Encryption('0123456789abcdef');
    }

    protected function getCarteBancairePayManager()
    {
        return new CarteBancairePayMock();
    }

    /**
     * @return CarteBancaire
     */
    protected function getCB($crypto, $date, $number)
    {
        $cb = new CarteBancaire();
        $cb->setCryptogramme($crypto);
        $cb->setExpiration($date);
        $cb->setNumero($number);

        return $cb;
    }

    public function testCryptDecrypt()
    {
        $this
            // Classical usage
            ->given($CBManager = new testedClass($this->getEncryptionService(), $this->getCarteBancairePayManager()))
            ->then
                ->object($CBManager)->isInstanceOf('KGC\RdvBundle\Service\CarteBancaireManager')
            ->given($cb = $this->getCB('987', '12/12', '321654987654321'))
            ->and($cbEncrypted = $CBManager->encrypt($cb))
            ->then
                ->object($cbEncrypted)->isInstanceOf('KGC\RdvBundle\Entity\CarteBancaire')
            ->given($cbDecrypted = $CBManager->decrypt($cbEncrypted))
            ->then
                ->object($cbDecrypted)->isInstanceOf('KGC\RdvBundle\Entity\CarteBancaire')
                ->string($cbDecrypted->getCryptogramme())->isIdenticalTo('987')
                ->string($cbDecrypted->getExpiration())->isIdenticalTo('12/12')
                ->string($cbDecrypted->getNumero())->isIdenticalTo('321654987654321')

            // Limit usage with decrypt with string already decrypted
            ->given($cb = $this->getCB('987', '12/12', '321654987654321'))
            ->and($cbDecrypted = $CBManager->decrypt($cb))
            ->then
                ->object($cbDecrypted)->isInstanceOf('KGC\RdvBundle\Entity\CarteBancaire')
                ->string($cbDecrypted->getExpiration())->isIdenticalTo('12/12')

            // Limit usage with encrypt with string already encrypted
            ->given($cb = $this->getCB('987', '321654987654321', '321654987654321'))
                ->and($cbEncrypted = $CBManager->encrypt($cb))
            ->then
                ->object($cbEncrypted)->isInstanceOf('KGC\RdvBundle\Entity\CarteBancaire')
                ->string($cbEncrypted->getExpiration())->isIdenticalTo('321654987654321')

        ;
    }

    public function testException()
    {
        $this
            ->given($CBManager = new testedClass($this->getEncryptionService(), $this->getCarteBancairePayManager()))
            ->then
                ->object($CBManager)->isInstanceOf('KGC\RdvBundle\Service\CarteBancaireManager')
            ->given($cb = $this->getCB(null, '12/12', '321654987654321'))
            ->then
                ->exception(function () use ($cb, $CBManager) {$CBManager->encrypt($cb);})
                ->hasMessage('Crypto, date and number must not be empty, values are (, 12/12, 321654987654321)')
            ->given($cb = $this->getCB('987', null, '321654987654321'))
            ->then
                ->exception(function () use ($cb, $CBManager) {$CBManager->encrypt($cb);})
                ->hasMessage('Crypto, date and number must not be empty, values are (987, , 321654987654321)')
            ->given($cb = $this->getCB('987', '12/12', null))
            ->then
                ->exception(function () use ($cb, $CBManager) {$CBManager->encrypt($cb);})
                ->hasMessage('Crypto, date and number must not be empty, values are (987, 12/12, )')

            ->given($cb = $this->getCB('987', '12/12', '321654987654321'))
            ->and($cbEncrypted = $CBManager->encrypt($cb))
            ->and($cbEncrypted->setExpiration('321654987654'))
            ->then
                ->exception(function () use ($cb, $CBManager) {$CBManager->decrypt($cb);})
                ->hasMessage('Missing initialization vector')

        ;
    }
}
