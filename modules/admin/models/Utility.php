<?php

namespace Rhymix\Modules\Admin\Models;

class Utility
{
	/**
	 * Clean up header and footer scripts.
	 *
	 * @param string $content
	 * @return string
	 */
	public static function cleanHeaderAndFooterScripts(string $content)
	{
		$content = utf8_clean($content);
		$content = preg_replace('!</?(html|head|body)[^>]*>!i', '', $content);
		$content = preg_replace_callback('!<script\b([^>]*?)language=[\'"]javascript[\'"]!i', function ($matches) {
			return trim('<script ' . trim($matches[1]));
		}, $content);
		return utf8_trim($content);
	}
}
