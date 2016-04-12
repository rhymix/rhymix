<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

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
/* End of file : Purifier.class.php */
/* Location: ./classes/security/Purifier.class.php */
