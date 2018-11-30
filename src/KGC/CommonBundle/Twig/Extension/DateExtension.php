<?php

namespace KGC\CommonBundle\Twig\Extension;

use JMS\DiExtraBundle\Annotation as DI;

/**
 * @DI\Service("kgc.date.twig.extension")
 *
 * @DI\Tag("twig.extension")
 */
class DateExtension extends \Twig_Extension
{
    /**
     * @return \DateTime
     */
    protected function getBaseDate()
    {
        return new \DateTime();
    }

    public function __construct()
    {
        if (!extension_loaded('intl')) {
            throw new \Exception("Date extension requires PHP extension 'intl'");
        }
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return array(
            'age' => new \Twig_Filter_Method($this, 'age'),
            'astro' => new \Twig_Filter_Method($this, 'astro'),
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'date';
    }

    /**
     * Get the age (in years) based on a date.
     *
     * @param DateTime|string $date
     *
     * @return string
     */
    public function age($date)
    {
        if (empty($date)) {
            return '';
        }

        if (!$date instanceof \DateTime) {
            $date = new \DateTime($date);
        }
        $base = $this->getBaseDate();

        if ($date > $base) {
            throw new \InvalidArgumentException(
                sprintf('Wrong date parameter %s', date_format($date, 'd/m/Y'))
            );
        }

        return $date->diff($base)->format('%y');
    }

    /**
     * Get the astro sign based on a date.
     *
     * @param DateTime|string $date
     *
     * @return string
     */
    public function astro($date)
    {
        if (empty($date)) {
            return '';
        }

        if (!$date instanceof \DateTime) {
            $date = new \DateTime($date);
        }

        $date = date_format($date, 'Y-m-d');
        list($y, $m, $d) = explode('-', $date);
        $m = (int) $m;
        $d = (int) $d;

        $astros = [
            3 === $m && 20 < $d || 4 === $m && 21 > $d => 'Bélier',
            4 === $m && 20 < $d || 5 === $m && 22 > $d => 'Taureau',
            5 === $m && 21 < $d || 6 === $m && 22 > $d => 'Gémeaux',
            6 === $m && 21 < $d || 7 === $m && 23 > $d => 'Cancer',
            7 === $m && 22 < $d || 8 === $m && 23 > $d => 'Lion',
            8 === $m && 22 < $d || 9 === $m && 23 > $d => 'Vierge',
            9 === $m && 22 < $d || 10 === $m && 23 > $d => 'Balance',
            10 === $m && 22 < $d || 11 === $m && 23 > $d => 'Scorpion',
            11 === $m && 22 < $d || 12 === $m && 22 > $d => 'Sagittaire',
            12 === $m && 21 < $d || 1 === $m && 21 > $d => 'Capricorne',
            1 === $m && 20 < $d || 2 === $m && 20 > $d => 'Verseau',
            2 === $m && 19 < $d || 3 === $m && 21 > $d => 'Poisson',
        ];

        return $astros[true];
    }
}
