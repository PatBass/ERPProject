<?php

namespace KGC\ClientBundle\Elastic\Finder;

/**
 * Interface FinderInterface.
 */
interface FinderInterface extends \FOS\ElasticaBundle\Finder\FinderInterface
{
    /**
     * Check if the repository is valid.
     *
     * @return bool
     */
    public function hasValidRepository();

    /**
     * Return an array of allowed repositories.
     *
     * @return array
     */
    public function getValidRepositories();
}
