<?php

namespace KGC\ClientBundle\Tests\Units\Twig\Extension;

use KGC\ClientBundle\Twig\Extension\HistoriqueExtension as testedClass;
use atoum\test;

class HistoriqueExtension extends test
{
    protected function createObject()
    {
        $objectManager = new \mock\Doctrine\Common\Persistence\ObjectManager();
        $historiqueManager = new \mock\KGC\ClientBundle\Service\HistoriqueManager($objectManager);

        return new testedClass($historiqueManager);
    }

    public function testInstance()
    {
        $this
            ->given($historiqueExtension = $this->createObject())
            ->then
                ->object($historiqueExtension)->isInstanceOf('\Twig_Extension')
        ;
    }

    public function testSimple()
    {
        $this
            ->given($historiqueExtension = $this->createObject())
            ->and($filters = array_keys($historiqueExtension->getFilters()))
            ->then
                ->array($filters)->size->isEqualTo(2)
                ->array($filters)->isIdenticalTo(['historique_type_label', 'historique_value'])
                ->string($historiqueExtension->getName())->IsEqualTo('historique')
        ;
    }
}
