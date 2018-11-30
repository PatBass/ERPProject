<?php
/**
 * Created by PhpStorm.
 * User: niko
 * Date: 17/08/2016
 * Time: 11:57
 */

namespace KGC\ClientBundle\Elastic\FormPersister;

/**
 * Interface RepositoryInterface.
 */
interface ClientSearchFormPersisterInterface
{
    /**
     * @param $key
     * @param $data
     *
     * @return mixed
     */
    public function persist($key, $data);

    /**
     * @param $key
     *
     * @return mixed
     */
    public function get($key);
}