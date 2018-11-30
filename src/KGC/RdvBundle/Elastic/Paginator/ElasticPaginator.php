<?php

namespace KGC\RdvBundle\Elastic\Paginator;

use FOS\ElasticaBundle\Paginator\FantaPaginatorAdapter;

class ElasticPaginator
{
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
    protected $limit;

    /**
     * @var array
     */
    protected $elements;

    /**
     * @var int
     */
    protected $offset;

    /**
     * @var array
     */
    protected $showPages;

    /**
     * @return int
     */
    protected function calculateOffset()
    {
        $offset = ($this->currentPage - 1) * $this->limit;
        $offset = max(0, $offset); // Cannot be negative

        $this->offset = $offset;
    }

    /**
     * Calculate the total number of pages,
     * based on total items count and elements per page.
     */
    protected function calculateTotalPagesCount()
    {
        $limit = max($this->limit, 1);
        $this->totalPagesCount = (int) ceil($this->totalItemsCount / $limit);
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

    protected function calculateShownPages()
    {
        $indexMin = max(1, $this->currentPage - 2);
        $indexMax = min($indexMin + 4, $this->totalPagesCount);

        $this->showPages = range($indexMin, $indexMax, 1);
    }

    /**
     * @param FantaPaginatorAdapter $adapter
     * @param int                   $currentPage
     * @param int                   $limit
     */
    public function __construct(FantaPaginatorAdapter $adapter, $currentPage = 1, $limit = 10)
    {
        $this->totalItemsCount = $adapter->getNbResults();
        $this->limit = $limit ?: $this->totalItemsCount;

        $this->calculateTotalPagesCount();
        $this->calculateCurrentPage($currentPage);
        $this->calculateOffset();
        $this->calculateShownPages();

        $this->elements = $adapter->getSlice($this->offset, $this->limit);
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

    public function hasMoreElements()
    {
        return $this->currentPage < $this->totalPagesCount;
    }

    public function getElements()
    {
        return $this->elements;
    }

    /**
     * @return array
     */
    public function getShowPages()
    {
        return $this->showPages;
    }
}
