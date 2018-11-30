<?php
/**
 * Created by PhpStorm.
 * User: niko
 * Date: 17/08/2016
 * Time: 11:55
 */

namespace KGC\UserBundle\Elastic\Repository;

/**
 * Interface RepositoryInterface.
 */
interface RepositoryInterface
{
    /**
     * Returns the repository name.
     *
     * @return mixed
     */
    public function getName();
}
