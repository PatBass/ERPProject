<?php

namespace KGC\StatBundle\Calculator;

/**
 * Interface CalculatorInterface.
 */
interface CalculatorInterface
{
    /**
     * @param array $config
     *
     * @return array
     */
    public function calculate(array $config = []);
}
