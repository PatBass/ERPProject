<?php

namespace KGC\CommonBundle\Traits;

trait Constantable
{
    /**
     * @return array
     */
    protected static function getConstants()
    {
        $oClass = new \ReflectionClass(__CLASS__);

        return $oClass->getConstants();
    }

    /**
     * @param $haystack
     * @param $needle
     *
     * @return bool
     */
    public static function startsWith($haystack, $needle)
    {
        return $needle === '' || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }

    /**
     * @param $prefix
     * @param bool $not
     *
     * @return array
     */
    public static function buildByPrefixes($prefix, $not = false)
    {
        $constants = static::getConstants();
        $results = [];

        foreach ($constants as $const => $value) {
            if (!$not && static::startsWith($const, $prefix) || $not && !static::startsWith($const, $prefix)) {
                $results[] = $value;
            }
        }

        return $results;
    }
}
