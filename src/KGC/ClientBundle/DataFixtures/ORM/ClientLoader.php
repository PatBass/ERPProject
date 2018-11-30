<?php

namespace KGC\ClientBundle\DataFixtures\ORM;

use KGC\CommonBundle\DataFixtures\CommonLoader;

class ClientLoader extends CommonLoader
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
            sprintf('%s/%s/website.yml', __DIR__, $this->getFixturesFullPath()),
        ];
    }
}
