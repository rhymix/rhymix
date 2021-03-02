<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Superclass of the edit component.
 * Set up the component variables
 *
 * @class EditorHandler
 * @author NAVER (developers@xpressengine.com)
 */
class EditorHandler extends BaseObject
{

	/**
	 * set the xml and other information of the component
	 * @param object $info editor information
	 * @return void
	 * */
	function setInfo($info)
	{
		Context::set('component_info', $info);

		if(!isset($info->extra_vars) || !$info->extra_vars)
		{
			return;
		}

		foreach($info->extra_vars as $key => $val)
		{
			$this->{$key} = trim($val->value);
		}
	}

}
/* End of file EditorHandler.class.php */
/* Location: ./classes/editor/EditorHandler.class.php */
