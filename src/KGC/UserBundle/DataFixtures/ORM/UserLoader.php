<?php

namespace KGC\UserBundle\DataFixtures\ORM;

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
        return 1;
    }

    /**
     * Returns an array of file paths to fixtures.
     *
     * @return array<string>
     */
    protected function getFixtures()
    {
        return [
            sprintf('%s/%s/voyants.yml', __DIR__, $this->getFixturesFullPath()),
            sprintf('%s/%s/profils.yml', __DIR__, $this->getFixturesFullPath()),
            sprintf('%s/%s/utilisateurs.yml', __DIR__, $this->getFixturesFullPath()),
        ];
    }
}
