<?php

namespace KGC\CommonBundle\Upgrade;

class UpgradeDate
{
    const FORFAIT_AUTO_TARIFICATION = 'forfait_auto_tarification';
    const PRODUCT_AUTO_TARIFICATION = 'product_auto_tarification';
    const URL_SOURCE = 'url_source';

    /**
     * @param $type
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public static function getDate($type)
    {
        $updates = [
            self::FORFAIT_AUTO_TARIFICATION => new \DateTime('2015-08-21'), // MEP S10
            self::PRODUCT_AUTO_TARIFICATION => new \DateTime('2015-09-10'),
            self::URL_SOURCE => new \DateTime('2016-01-07'),
        ];

        if (!in_array($type, array_keys($updates))) {
            throw new \Exception(sprintf('Functionality "%s" not available in the list', $type));
        }

        return $updates[$type];
    }
}
