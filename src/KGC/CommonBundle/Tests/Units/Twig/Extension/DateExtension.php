<?php

namespace KGC\CommonBundle\Tests\Units\Twig\Extension;

use KGC\CommonBundle\Twig\Extension\DateExtension as testedClass;
use atoum\test;

class DateExtensionBase extends testedClass
{
    protected function getBaseDate()
    {
        return new \DateTime('2015-05-04');
    }
}

class DateExtension extends test
{
    /**
     * @var string
     */
    protected $key = 'key';

    public function testInstance()
    {
        $this
            ->given($dateExtension = new DateExtensionBase())
            ->then
                ->object($dateExtension)->isInstanceOf('\Twig_Extension')
        ;
    }

    public function testAge()
    {
        $this
            ->given($dateExtension = new DateExtensionBase())

            // Not yet the birthday
            ->and($age = $dateExtension->age(new \Datetime('1987-05-15')))
            ->then
                ->string($age)->isEqualTo('27')

            // Day before birthday
            ->given($age = $dateExtension->age(new \Datetime('1987-05-05')))
            ->then
                ->string($age)->isEqualTo('27')

            // Birthday
            ->given($age = $dateExtension->age(new \Datetime('1987-05-04')))
            ->then
                ->string($age)->isEqualTo('28')

            // Day after birthday
            ->given($age = $dateExtension->age(new \Datetime('1987-05-03')))
            ->then
                ->string($age)->isEqualTo('28')

            // After birthday
            ->given($age = $dateExtension->age(new \Datetime('1987-01-18')))
            ->then
                ->string($age)->isEqualTo('28')

            // Same year
            ->given($age = $dateExtension->age(new \Datetime('2015-05-04')))
            ->then
                ->string($age)->isEqualTo('0')

            // Date after today => not possible
            ->exception(function () use ($dateExtension) {
                $dateExtension->age(new \Datetime('2020-06-15'));
            })->hasMessage('Wrong date parameter 15/06/2020')

        ;
    }

    public function testAstro()
    {
        $this
            ->given($dateExtension = new DateExtensionBase())

            ->given($astro = $dateExtension->astro(new \DateTime('1987-05-15')))
            ->then
                ->string($astro)->isEqualTo('Taureau')

            ->given($astro = $dateExtension->astro(new \DateTime('1987-03-20')))
            ->then
                ->string($astro)->isEqualTo('Poisson')

            ->given($astro = $dateExtension->astro(new \DateTime('1987-03-21')))
            ->then
                ->string($astro)->isEqualTo('Bélier')

            ->given($astro = $dateExtension->astro(new \DateTime('1987-04-20')))
            ->then
                ->string($astro)->isEqualTo('Bélier')

            ->given($astro = $dateExtension->astro(new \DateTime('1987-05-22')))
            ->then
                ->string($astro)->isEqualTo('Gémeaux')

            ->given($astro = $dateExtension->astro(new \DateTime('1987-05-21')))
            ->then
                ->string($astro)->isEqualTo('Taureau')

            ->given($astro = $dateExtension->astro(new \DateTime('1987-06-22')))
            ->then
                ->string($astro)->isEqualTo('Cancer')

            ->given($astro = $dateExtension->astro(new \DateTime('1987-06-21')))
            ->then
                ->string($astro)->isEqualTo('Gémeaux')

            ->given($astro = $dateExtension->astro(new \DateTime('1987-07-23')))
            ->then
                ->string($astro)->isEqualTo('Lion')

            ->given($astro = $dateExtension->astro(new \DateTime('1987-07-22')))
            ->then
                ->string($astro)->isEqualTo('Cancer')

            ->given($astro = $dateExtension->astro(new \DateTime('1987-08-23')))
            ->then
                ->string($astro)->isEqualTo('Vierge')

            ->given($astro = $dateExtension->astro(new \DateTime('1987-08-22')))
            ->then
                ->string($astro)->isEqualTo('Lion')

            ->given($astro = $dateExtension->astro(new \DateTime('1987-09-23')))
            ->then
                ->string($astro)->isEqualTo('Balance')

            ->given($astro = $dateExtension->astro(new \DateTime('1987-09-22')))
            ->then
                ->string($astro)->isEqualTo('Vierge')

            ->given($astro = $dateExtension->astro(new \DateTime('1987-10-23')))
            ->then
                ->string($astro)->isEqualTo('Scorpion')

            ->given($astro = $dateExtension->astro(new \DateTime('1987-10-22')))
            ->then
                ->string($astro)->isEqualTo('Balance')

            ->given($astro = $dateExtension->astro(new \DateTime('1987-11-23')))
            ->then
                ->string($astro)->isEqualTo('Sagittaire')

            ->given($astro = $dateExtension->astro(new \DateTime('1987-11-22')))
            ->then
                ->string($astro)->isEqualTo('Scorpion')

            ->given($astro = $dateExtension->astro(new \DateTime('1987-12-22')))
            ->then
                ->string($astro)->isEqualTo('Capricorne')

            ->given($astro = $dateExtension->astro(new \DateTime('1987-12-21')))
            ->then
                ->string($astro)->isEqualTo('Sagittaire')

            ->given($astro = $dateExtension->astro(new \DateTime('1987-01-21')))
            ->then
                ->string($astro)->isEqualTo('Verseau')

            ->given($astro = $dateExtension->astro(new \DateTime('1987-01-20')))
            ->then
                ->string($astro)->isEqualTo('Capricorne')

            ->given($astro = $dateExtension->astro(new \DateTime('1987-02-20')))
            ->then
                ->string($astro)->isEqualTo('Poisson')

            ->given($astro = $dateExtension->astro(new \DateTime('1987-02-19')))
            ->then
                ->string($astro)->isEqualTo('Verseau')

            ->given($astro = $dateExtension->astro(new \DateTime('1987-03-21')))
            ->then
                ->string($astro)->isEqualTo('Bélier')

        ;
    }
}
