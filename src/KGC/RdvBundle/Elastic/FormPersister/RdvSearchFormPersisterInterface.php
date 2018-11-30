<?php

namespace KGC\RdvBundle\Elastic\FormPersister;

/**
 * Interface RepositoryInterface.
 */
interface RdvSearchFormPersisterInterface
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
