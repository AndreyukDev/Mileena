<?php

declare(strict_types=1);

namespace Mileena\DBMQ;

/**
 * A utility class for calculating pagination details for database queries.
 *
 * It helps determine the total number of pages, the SQL query offset,
 * and provides methods to navigate between pages.
 */
class PagerPaginate
{
    /**
     * Total number of pages.
     */
    private int $allPages;

    /**
     * Number of records per page.
     */
    private int $rowsPerPage;

    /**
     * The current page number.
     */
    private int $currentPage;

    /**
     * Total number of records across all pages.
     */
    private int $total;

    /**
     * Calculates pagination details.
     *
     * @param int $total The total number of records.
     * @param int $rowsPerPage The number of records to display per page.
     * @param int $currentPage The current page number (1-based).
     */
    public function __construct(int $total, int $rowsPerPage, int $currentPage = 1)
    {
        $this->total = $total;
        $this->rowsPerPage = $rowsPerPage > 0 ? $rowsPerPage : 1; // Avoid division by zero
        $this->currentPage = $currentPage > 0 ? $currentPage : 1;

        if ($this->total === 0) {
            $this->allPages = 1;
        } else {
            $this->allPages = (int) ceil($this->total / $this->rowsPerPage);
        }
    }

    /**
     * Returns the starting offset for a SQL LIMIT clause based on a page number.
     *
     * @param int $pageNumber The page number to get the offset for.
     * @return int The calculated offset.
     */
    public function getPage(int $pageNumber = 0): int
    {
        $page = ($pageNumber > 1) ? $pageNumber : $this->currentPage;

        if ($page > 1) {
            return $this->rowsPerPage * ($page - 1);
        }

        return 0;
    }

    /**
     * Gets the previous page number.
     *
     * @return int The previous page number, or the current page if it's the first one.
     */
    public function getPreviousPage(): int
    {
        return max(1, $this->currentPage - 1);
    }

    /**
     * Gets the next page number.
     *
     * @return int The next page number, or the current page if it's the last one.
     */
    public function getNextPage(): int
    {
        return min($this->allPages, $this->currentPage + 1);
    }

    /**
     * Gets the last page number.
     *
     * @return int The total number of pages.
     */
    public function getLastPage(): int
    {
        return $this->allPages;
    }

    /**
     * Gets the current page number.
     *
     * @return int The current page number.
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * Gets the number of rows per page.
     *
     * @return int The number of rows per page.
     */
    public function getRowsPerPage(): int
    {
        return $this->rowsPerPage;
    }

    /**
     * Gets the total number of records.
     *
     * @return int The total number of records.
     */
    public function getTotal(): int
    {
        return $this->total;
    }
}
