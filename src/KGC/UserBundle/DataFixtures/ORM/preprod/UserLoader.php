<?php

namespace KGC\UserBundle\DataFixtures\ORM\preprod;

use KGC\CommonBundle\DataFixtures\CommonLoader;

class UserLoader extends CommonLoader
{
    /**
     * Get the order of this fixture.
     *
     * @return int
     */
    public function getOrder()
    {
        return 2;
    }

    /**
     * Returns an array of file paths to fixtures.
     *
     * @return array<string>
     */
    protected function getFixtures()
    {
        return [
            sprintf('%s/%s/preprod/utilisateurs.yml', __DIR__.'/..', $this->getFixturesFullPath()),
        ];
    }
}

// make console "doctrine:fixtures:load --fixtures=src/KGC/UserBundle/DataFixtures/ORM/preprod --append"

