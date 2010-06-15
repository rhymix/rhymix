<?php

class Mobile {
	function isFromMobilePhone() {
		if(Context::get('full_browse') || $_COOKIE["FullBrowse"])
		{
			return false;
		}

		return Context::get('mobile') || preg_match('/(iPod|iPhone|Android|SCH\-M[0-9]+)/',$_SERVER['HTTP_USER_AGENT']);
	}
}

?>
