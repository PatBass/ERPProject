<?php

namespace KGC\CommonBundle\Traits;

trait NextReceiptDate
{
    /**
     * Return the number of months between two dates.
     * It's an approximation (30/31 days...)
     *
     * @param \Datetime $baseDate
     * @param \Datetime $date
     * @return int
     */
    protected function getMonthsDiff(\Datetime $baseDate, \Datetime $date)
    {
        return $baseDate->diff($date)->m + ($baseDate->diff($date)->y * 12);
    }

    /**
     * Return the next date to process a totally/partially refused bank receipt.
     *
     * @param \DateTime $startDate
     * @param \DateTime $paymentDate
     * @return array
     */
    public function getNextReceiptDate(DayPostponementInterface $postpone, \DateTime $startDate, \DateTime $paymentDate)
    {
        $step = null;
        $monthDiffCount = $this->getMonthsDiff($startDate, $paymentDate);

        $firstMonthDays = $postpone->getFirstMonthNextReceiptDays();
        $nextMonthDays = $postpone->getOtherMonthsNextReceiptDays();

        $days = $monthDiffCount === 0 ? $firstMonthDays : $nextMonthDays;

        $endOfMonthDay = clone($paymentDate);
        $endOfMonthDay->modify('last day of this month, midnight');
        if ($endOfMonthDay->format('d') > 30) {
            $endOfMonthDay->modify('-1 day');
        }

        $nextDay = null;

        foreach ($days as $day) {
            if (is_int($day) && $paymentDate->format('d') < $day) {
                $nextDay = new \DateTime($paymentDate->format('Y-m-'.$day));

                $monthDiff = $this->getMonthsDiff($startDate, $nextDay);

                // if we change from first to second month with the new date, we don't break if the chosen date is not in the $nextMonthDays choices
                if ($monthDiff == 0 || in_array($day, $nextMonthDays)) {
                    break;
                }
            } else if ($day == 'end' && $paymentDate < $endOfMonthDay) {
                $nextDay = $endOfMonthDay;
                break;
            }
        }


        if ($nextDay === null) {
            $nextDay = (new \DateTime($paymentDate->format('Y-m-05')))->modify('+1 month');
        }

        $startDate2 = clone $startDate;

        return ['finished' => $nextDay > $startDate2->modify('+3 month') , 'date' => $nextDay];
    }
}