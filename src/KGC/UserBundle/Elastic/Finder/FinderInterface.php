<?php
/**
 * Created by PhpStorm.
 * User: niko
 * Date: 17/08/2016
 * Time: 11:55
 */

namespace KGC\UserBundle\Elastic\Finder;

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
