<?php
namespace KGC\CommonBundle\Traits;

interface DayPostponementInterface
{
    /**
     * Get list of trys' days after fail for first month
     *
     * @return array
     */
    public function getFirstMonthNextReceiptDays();

    /**
     * Get list of trys' days after fail for other months
     *
     * @return array
     */
    public function getOtherMonthsNextReceiptDays();

    /**
     * @return int
     */
    public function getAllowedConsecutiveFails();
}