<?php

namespace KGC\UserBundle\Decorator;

/**
 * Interface DecoratorInterface.
 */
interface DecoratorInterface
{
    /**
     * @param array $dataToDecorate
     * @param array $config
     *
     * @return mixed
     */
    public function decorate(array $dataToDecorate, array $config);
}
