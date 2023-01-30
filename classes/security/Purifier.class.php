<?php

class Purifier
{
	public static function getInstance()
	{
		return new self();
	}

	public function purify(&$content)
	{
		$content = Rhymix\Framework\Filters\HTMLFilter::clean($content);
	}

}
