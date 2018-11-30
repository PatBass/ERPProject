<?php

namespace KGC\RdvBundle\Tests\Units\Service;

use KGC\RdvBundle\Service\Encryption as testedClass;
use atoum\test;

class Encryption extends test
{
    /**
     * @var string
     */
    protected $key = '0123456789abcdef';

    public function testCryptDecrypt()
    {
        $this
            ->given($encryption = new testedClass($this->key))
            ->then
                ->string($encryption->decrypt($encryption->encrypt('bob')))->isIdenticalTo('bob')
        ;
    }

    public function testCryptSameData()
    {
        $this
            ->given($encryption = new testedClass($this->key))
            ->and($crypt1 = $encryption->encrypt('bob'))
            ->and($crypt2 = $encryption->encrypt('bob'))
            ->then
                ->string($crypt1)->isIdenticalTo($crypt2)
        ;
    }
}
