<?php

namespace KGC\ClientBundle\Service;

class HistoriquePaginator
{
    /**
     * Number of elements to display per page.
     */
    const ELEMENTS_PER_PAGE = 4;

    /**
     * Grouped items.
     *
     * @var array
     */
    protected $groupedItems;

    /**
     * One dimension array for ids.
     *
     * @var array
     */
    protected $groupedIds;

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
     * Extract and flatten ids from grouped items.
     */
    protected function flattenIds()
    {
        $this->groupedIds = array_map(function ($v) {
            return $v['id'];
        }, $this->groupedItems);
    }

    /**
     * Calculate the total number of pages,
     * based on total items count and elements per page.
     */
    protected function calculateTotalPagesCount()
    {
        $limit = max(self::ELEMENTS_PER_PAGE, 1);
        $this->totalPagesCount = (int) ceil($this->totalItemsCount / $limit);
    }

    /**
     * Calculate indexes.
     */
    protected function calculateIndexes()
    {
        $limit = max(self::ELEMENTS_PER_PAGE, 1);

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
     * Builds everything needed for the paginator.
     *
     * @param array $groupedItems
     * @param int   $currentPage
     */
    public function __construct(array $groupedItems, $currentPage = 1)
    {
        $this->groupedItems = $groupedItems;
        $this->totalItemsCount = count($groupedItems);

        $this->flattenIds();
        $this->calculateTotalPagesCount();
        $this->calculateCurrentPage($currentPage);
        $this->calculateIndexes();
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
    public function getIds()
    {
        if (empty($this->groupedIds)) {
            return ['start' => 0, 'end' => 0];
        }

        return [
            'start' => $this->groupedIds[$this->indexMin],
            'end' => $this->groupedIds[$this->indexMax],
        ];
    }
}
