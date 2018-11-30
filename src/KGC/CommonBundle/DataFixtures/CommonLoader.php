<?php

namespace KGC\CommonBundle\DataFixtures;

use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Hautelook\AliceBundle\Alice\DataFixtureLoader;

/**
 * Class CommonLoader.
 */
abstract class CommonLoader extends DataFixtureLoader implements OrderedFixtureInterface
{
    /**
     * Return the path to access fixtures path from loader path.
     *
     * @return string
     */
    protected function getFixturesFullPath()
    {
        return sprintf('../../Resources/fixtures');
    }
}
