<?php

// src/KGC/CommonBundle/Service/Paginator.php


namespace KGC\CommonBundle\Service;

class Paginator
{
    /**
     * Number of elements to display per page.
     */
    protected $elementsPerPage;

    /**
     * Total number of items to paginate (grouped).
     *
     * @var int
     */
    protected $totalItemsCount;

    /**
     * Total number of pages based on total items count and elements per page.
     *
     * @var int
     */
    protected $totalPagesCount;

    /**
     * @var int
     */
    protected $currentPage = 1;

    /**
     * @var int
     */
    protected $indexMin;

    /**
     * @var int
     */
    protected $indexMax;

    /**
     * Builds everything needed for the paginator.
     *
     * @param array $groupedItems
     * @param int   $currentPage
     */
    public function __construct($itemsCount, $currentPage = 1, $elementsPerPage = 10)
    {
        $this->elementsPerPage = $elementsPerPage;
        $this->totalItemsCount = max($itemsCount, 1);

        $this->calculateTotalPagesCount();
        $this->calculateCurrentPage($currentPage);
        $this->calculateIndexes();
    }

    /**
     * Calculate the total number of pages,
     * based on total items count and elements per page.
     */
    protected function calculateTotalPagesCount()
    {
        $limit = $this->elementsPerPage;
        $this->totalPagesCount = (int) ceil($this->totalItemsCount / $limit);
    }

    /**
     * Calculate indexes.
     */
    protected function calculateIndexes()
    {
        $limit = $this->elementsPerPage;

        $indexMin = ($this->currentPage - 1) * $limit;
        $indexMin = max(0, $indexMin); // Cannot be negative

        $indexMax = $indexMin + $limit - 1;
        $indexMax = min($indexMax, $this->totalItemsCount - 1);
        $indexMax = max($indexMax, 0); // Cannot be negative

        $this->indexMin = $indexMin;
        $this->indexMax = $indexMax;
    }

    /**
     * @param $currentPage
     */
    protected function calculateCurrentPage($currentPage)
    {
        // Page min is 1, Page max is totalPagesCount
        $currentPage = max($currentPage, 1);
        $currentPage = min($currentPage, $this->totalPagesCount);
        $this->currentPage = $currentPage;
    }

    /**
     * @return int
     */
    public function getTotalItemsCount()
    {
        return $this->totalItemsCount;
    }

    /**
     * @return int
     */
    public function getTotalPagesCount()
    {
        return $this->totalPagesCount;
    }

    /**
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    /**
     * @return array
     */
    public function getStartElement()
    {
        return $this->indexMin + 1;
    }

    public function getEndElement()
    {
        return $this->indexMax + 1;
    }

    public function hasMoreElements()
    {
        return $this->currentPage < $this->totalPagesCount;
    }

    /**
     * @return array
     */
    public function getLimits()
    {
        return [
            'start' => $this->indexMin,
            'end' => $this->indexMax,
            'nb' => $this->elementsPerPage,
        ];
    }
}
