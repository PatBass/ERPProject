<?php

namespace KGC\CommonBundle\Repository;

use Gedmo\Loggable\Entity\Repository\LogEntryRepository;
use KGC\ChatBundle\Entity\ChatFormulaRate;

class LogRepository extends LogEntryRepository
{
    /**
     * @param ChatFormulaRate $formula
     *
     * @return array
     */
    public function getShortenedFormulaLogEntries(ChatFormulaRate $formula)
    {
        $entries = []; $lastData = null;

        foreach ($this->getLogEntries($formula) as $i => $entry) {
            $data = $entry->getData();

            if ($lastData === null || count($data) != 1 || (isset($data['price']) && $data['price'] != $lastData['price'])) {
                $entries[] = $entry;
                $lastData = $data;
            }
        }

        return $entries;
    }
}