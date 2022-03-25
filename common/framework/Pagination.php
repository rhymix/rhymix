<?php

namespace Rhymix\Framework;

/**
 * The pagination class.
 */
class Pagination
{
	/**
	 * Count style constants.
	 */
	const COUNT_STYLE_NORMAL = 1;
	const COUNT_STYLE_CONTINUOUS = 2;
	
	/**
	 * Calculate the number of pages.
	 * 
	 * @param int $total_items
	 * @param int $items_per_page
	 * @param int $minimum (optional)
	 * @return int
	 */
	public static function countPages($total_items, $items_per_page, $minimum = 1)
	{
		if (!$items_per_page)
		{
			return (int)$minimum;
		}
		else
		{
			return (int)max($minimum, ceil($total_items / $items_per_page));
		}
	}
	
	/**
	 * Create HTML for pagination.
	 * 
	 * @param string $base_url ($PAGE will be replaced with the page number)
	 * @param int $current_page
	 * @param int $total_pages
	 * @param int $count (optional)
	 */
	public static function createLinks($base_url, $total_pages, $current_page, $count = 10, $count_style = self::COUNT_STYLE_NORMAL)
	{
		// Only integers are allowed here.
		$current_page = (int)$current_page;
		$total_pages = (int)$total_pages;
		$count = (int)$count;
		
		// Determine the range of pages to show.
		if ($count_style === self::COUNT_STYLE_NORMAL)
		{
			$last_shown = ceil($current_page / $count) * $count;
			$first_shown = max(1, $last_shown - $count + 1);
			if ($last_shown > $total_pages)
			{
				$last_shown = $total_pages;
			}
		}
		else
		{
			$first_shown = $current_page - floor(($count - 1) / 2);
			if ($first_shown < 1)
			{
				$first_shown = 1;
			}
			$last_shown = $first_shown + $count - 1;
			if ($last_shown > $total_pages)
			{
				$last_shown = $total_pages;
				$first_shown = max(1, $last_shown - $count + 1);
			}
		}
		
		// Open the <div> tag.
		$return = array('<div class="pagination">');
		
		// Compose the link to the first page.
		if ($first_shown > 1)
		{
			if (strpos($base_url, '$PAGE') !== false)
			{
				$target_url = str_replace('$PAGE', 1, $base_url);
			}
			else
			{
				$target_url = $base_url . 1;
			}
			
			$return[] = self::_composeLink($target_url, '<span class="arrow">&laquo;</span> <span class="page_number first_page">1</span>');
			$return[] = '<span class="ellipsis">...</span>';
		}
		
		// Compose links for each page.
		for ($page = $first_shown; $page <= $last_shown; $page++)
		{
			if ($page == $current_page)
			{
				$opening_span = '<span class="page_number current_page">';
			}
			else
			{
				$opening_span = '<span class="page_number">';
			}
			
			if (strpos($base_url, '$PAGE') !== false)
			{
				$target_url = str_replace('$PAGE', $page, $base_url);
			}
			else
			{
				$target_url = $base_url . $page;
			}
			
			$return[] = self::_composeLink($target_url, $opening_span . $page . '</span>');
		}
		
		// Compose the link to the last page.
		if ($last_shown < $total_pages)
		{
			if (strpos($base_url, '$PAGE') !== false)
			{
				$target_url = str_replace('$PAGE', $total_pages, $base_url);
			}
			else
			{
				$target_url = $base_url . $total_pages;
			}
			
			$return[] = '<span class="ellipsis">...</span>';
			$return[] = self::_composeLink($target_url, '<span class="page_number last_page">' . $total_pages . '</span> <span class="arrow">&raquo;</span>');
		}
		
		// Close the <div> tag.
		$return[] = '</div>';
		
		// Return the completed HTML.
		return implode(' ', $return);
	}
	
	/**
	 * Link creation subroutine.
	 * 
	 * @param string $target_url
	 * @param string $content
	 * @return string
	 */
	protected static function _composeLink($target_url, $content)
	{
		return '<a href="' . escape($target_url) . '">' . $content . '</a>';
	}
}
