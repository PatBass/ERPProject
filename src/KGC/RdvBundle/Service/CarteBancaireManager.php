<?php

namespace KGC\RdvBundle\Service;

use JMS\DiExtraBundle\Annotation as DI;
use KGC\RdvBundle\Entity\CarteBancaire;

/**
 * @DI\Service("kgc.rdv.carte_bancaire.manager")
 */
class CarteBancaireManager
{
    /**
     * @var Encryption
     */
    protected $encryptionService;

    /**
     * @param Encryption $encryptionService
     *
     * @DI\InjectParams({
     *     "encryptionService" = @DI\Inject("kgc.rdv.encryption.service"),
     * })
     */
    public function __construct(Encryption $encryptionService)
    {
        $this->encryptionService = $encryptionService;
    }

    /**
     * Return CB with critical information encrypted.
     *
     * @param CarteBancaire $cb
     *
     * @return CarteBancaire
     */
    public function encrypt(CarteBancaire $cb)
    {
        if (strlen($cb->getExpiration()) > 10) {
            return $cb;
        }

        $crypto = $cb->getCryptogramme();
        $date = $cb->getExpiration();
        $number = $cb->getNumero();

        if (null === $crypto || null === $date || null === $number) {
            throw new \LogicException(
                sprintf(
                    'Crypto, date and number must not be empty, values are (%s, %s, %s)',
                    $crypto, $date, $number)
            );
        }

        $cb->setCryptogramme($this->encryptionService->encrypt($crypto));
        $cb->setExpiration($this->encryptionService->encrypt($date));
        $cb->setNumero($this->encryptionService->encrypt($number));
        if ($firstName = $cb->getFirstName()) {
            $cb->setFirstName($this->encryptionService->encrypt($firstName));
        }
        if ($lastName = $cb->getLastName()) {
            $cb->setLastName($this->encryptionService->encrypt($lastName));
        }

        return $cb;
    }

    /**
     * Return CB with critical information decrypted.
     *
     * @param CarteBancaire $cb
     *
     * @return CarteBancaire
     */
    public function decrypt(CarteBancaire $cb)
    {
        if (strlen($cb->getExpiration()) < 10) {
            return $cb;
        }

        $cb->setCryptogramme(
            $this->encryptionService->decrypt(
                $cb->getCryptogramme()
            )
        );
        $cb->setExpiration(
            $this->encryptionService->decrypt(
                $cb->getExpiration()
            )
        );
        $cb->setNumero(
            $this->encryptionService->decrypt(
                $cb->getNumero()
            )
        );

        if ($firstName = $cb->getFirstName()) {
            $cb->setFirstName($this->encryptionService->decrypt($firstName));
        }
        if ($lastName = $cb->getLastName()) {
            $cb->setLastName($this->encryptionService->decrypt($lastName));
        }

        return $cb;
    }
}
