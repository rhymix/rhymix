<?php

namespace Rhymix\Framework\Helpers;

/**
 * DB result helper class.
 * 
 * Instances of this class will be returned from DB queries.
 */
class DBResultHelper extends \BaseObject
{
	// Additional attributes for DB query results.
	public $page_navigation;
	public $data;
}
