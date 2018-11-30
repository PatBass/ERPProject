<?php

namespace KGC\RdvBundle\DataFixtures\ORM;

use KGC\CommonBundle\DataFixtures\CommonLoader;

class RdvLoader extends CommonLoader
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
            sprintf('%s/%s/codes_promo.yml', __DIR__, $this->getFixturesFullPath()),
            sprintf('%s/%s/tiroir.yml', __DIR__, $this->getFixturesFullPath()),
            sprintf('%s/%s/tpe.yml', __DIR__, $this->getFixturesFullPath()),
            sprintf('%s/%s/groupeaction.yml', __DIR__, $this->getFixturesFullPath()),
            sprintf('%s/%s/etat.yml', __DIR__, $this->getFixturesFullPath()),
            sprintf('%s/%s/etiquettes.yml', __DIR__, $this->getFixturesFullPath()),
            sprintf('%s/%s/support.yml', __DIR__, $this->getFixturesFullPath()),
            sprintf('%s/%s/actionsuivi.yml', __DIR__, $this->getFixturesFullPath()),
            sprintf('%s/%s/dossier.yml', __DIR__, $this->getFixturesFullPath()),
            sprintf('%s/%s/moyen_paiement.yml', __DIR__, $this->getFixturesFullPath()),
            sprintf('%s/%s/code_tarification.yml', __DIR__, $this->getFixturesFullPath()),
        ];
    }
}
