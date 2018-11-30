<?php

namespace KGC\RdvBundle\Service;

use JMS\DiExtraBundle\Annotation as DI;

/**
 * @DI\Service("kgc.rdv.encryption.service")
 */
final class Encryption
{
    const CIPHER = MCRYPT_RIJNDAEL_128;
    const MODE = MCRYPT_MODE_CBC;

    /* Cryptographic key of length 16, 24 or 32. NOT a password! */
    private $key;

    /**
     * @return int
     */
    protected function getIvSize()
    {
        return mcrypt_get_iv_size(self::CIPHER, self::MODE);
    }

    /**
     * @param $key
     *
     * @DI\InjectParams({
     *     "key" = @DI\Inject("%crypt.key%"),
     * })
     */
    public function __construct($key)
    {
        $this->key = $key;
    }

    /**
     * Return an encrypted string from plain text.
     *
     * @param $plainText
     *
     * @return string
     */
    public function encrypt($plainText)
    {
        // WE CAN'T USE RANDOM IV TO BE ABLE TO SEARCH BY CARD NUMBER
        //$iv = mcrypt_create_iv($this->getIvSize(), MCRYPT_DEV_URANDOM);
        $iv = substr(md5($this->key), 0, $this->getIvSize());
        $cipherText = mcrypt_encrypt(self::CIPHER, $this->key, $plainText, self::MODE, $iv);

        return base64_encode($iv.$cipherText);
    }

    /**
     * Return a plain text string from encrypted string.
     *
     * @param $cipherText
     *
     * @return string
     *
     * @throws \Exception
     */
    public function decrypt($cipherText)
    {
        $cipherText = base64_decode($cipherText);
        $ivSize = $this->getIvSize();

        if (strlen($cipherText) < $ivSize) {
            throw new \Exception('Missing initialization vector');
        }

        $iv = substr($cipherText, 0, $ivSize);
        $cipherText = substr($cipherText, $ivSize);
        $plainText = mcrypt_decrypt(self::CIPHER, $this->key, $cipherText, self::MODE, $iv);

        return rtrim($plainText, "\0");
    }
}
