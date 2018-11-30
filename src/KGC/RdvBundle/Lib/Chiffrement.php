<?php

// src/KGC/rdvBundke/Lib/Chiffrement.php


namespace KGC\RdvBundle\Lib;

/**
 * Chriffrement
 * http://www.finalclap.com/tuto/php-cryptage-aes-chiffrement-85/.
 *
 * @category Lib
 *
 * @author Laurene Dourdin <2aurene@gmail.com>
 */
class Chiffrement
{
    private static $cipher = MCRYPT_RIJNDAEL_128;          // Algorithme utilisé pour le cryptage des blocs
    private static $key = 'KGCOM';                      // Clé de cryptage
    private static $mode = 'cbc';                        // Mode opératoire (traitement des blocs)

    public static function crypt($data)
    {
        $keyHash = md5(self::$key);
        $key = substr($keyHash, 0,   mcrypt_get_key_size(self::$cipher, self::$mode));
        $iv = substr($keyHash, 0, mcrypt_get_block_size(self::$cipher, self::$mode));
        if (!empty($data)) {
            $data = mcrypt_encrypt(self::$cipher, $key, $data, self::$mode, $iv);
            $data = base64_encode($data);
        }

        return $data;
    }

    public static function decrypt($data)
    {
        $keyHash = md5(self::$key);
        $key = substr($keyHash, 0,   mcrypt_get_key_size(self::$cipher, self::$mode));
        $iv = substr($keyHash, 0, mcrypt_get_block_size(self::$cipher, self::$mode));
        if (!empty($data)) {
            $data = base64_decode($data);
            $data = mcrypt_decrypt(self::$cipher, $key, $data, self::$mode, $iv);
            $data = rtrim($data);
        }

        return $data;
    }
}
