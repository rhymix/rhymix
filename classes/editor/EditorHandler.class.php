<?php

/**
 * Superclass of the editor component.
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
