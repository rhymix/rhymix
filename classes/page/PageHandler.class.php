<?php

/**
 * PageHandler
 *
 * @author NAVER (developers@xpressengine.com)
 */
class PageHandler extends Handler implements Iterator
{
	// Number of total items
	public $total_count = 0;

	// Number of total pages
	public $total_page = 0;

	// Current page number
	public $cur_page = 0;

	// Number of page links displayed at one time.
	public $page_count = 10;

	// First page number
	public $first_page = 1;

	// Last page number
	public $last_page = 1;

	// Stepper
	public $point = 0;

	/**
	 * Constructor
	 *
	 * @param int $total_count number of total items
	 * @param int $total_page number of total pages
	 * @param int $cur_page current page number
	 * @param int $page_count number of page links displayed at one time
	 */
	public function __construct(int $total_count, int $total_page, int $cur_page, int $page_count = 10)
	{
		$this->total_count = $total_count;
		$this->total_page = $total_page;
		$this->cur_page = $cur_page;
		$this->page_count = $page_count;
		$this->point = 0;

		$first_page = $cur_page - (int) ($page_count / 2);
		if($first_page < 1)
		{
			$first_page = 1;
		}

		if($total_page > $page_count && $first_page + $page_count - 1 > $total_page)
		{
			$first_page -= $first_page + $page_count - 1 - $total_page;
		}

		$this->first_page = $first_page;
		$this->last_page = $total_page;

		if($total_page < $this->page_count)
		{
			$this->page_count = $total_page;
		}
	}

	/**
	 * Request next page
	 *
	 * @return int
	 */
	public function getNextPage(): int
	{
		$page = $this->first_page + $this->point++;
		if($this->point > $this->page_count || $page > $this->last_page)
		{
			$page = 0;
		}
		return $page;
	}

	/**
	 * Return number of page that added offset.
	 *
	 * @param int $offset
	 * @return int
	 */
	public function getPage(int $offset): int
	{
		return max(min($this->cur_page + $offset, $this->total_page), '');
	}

	/**
	 * Rewind iterator stepper.
	 *
	 * @return void
	 */
	public function rewind(): void
	{
		$this->point = 0;
	}

	/**
	 * Determine if a current iterated item is valid.
	 *
	 * @return bool
	 */
	public function valid(): bool
	{
		$page = $this->first_page + $this->point;
		return $this->point < $this->page_count && $page <= $this->last_page;
	}

	/**
	 * Get a current iterated page number.
	 *
	 * @return int
	 */
	public function current(): int
	{
		return $this->first_page + $this->point;
	}

	/**
	 * Get a current iterator stepper.
	 *
	 * @return int
	 */
	public function key(): int
	{
		return $this->point;
	}

	/**
	 * Step up the iterator.
	 *
	 * @return void
	 */
	public function next(): void
	{
		$this->point++;
	}
}
