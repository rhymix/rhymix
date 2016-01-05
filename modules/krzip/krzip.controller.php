<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  krzipController
 * @author NAVER (developers@xpressengine.com)
 * @brief  Krzip module controller class.
 */

class krzipController extends krzip
{
	function updateConfig($args)
	{
		if(!$args || !is_object($args))
		{
			$args = new stdClass();
		}

		$oModuleController = getController('module');
		$output = $oModuleController->updateModuleConfig('krzip', $args);
		if($output->toBool())
		{
			unset($this->module_config);
		}

		return $output;
	}
}

/* End of file krzip.controller.php */
/* Location: ./modules/krzip/krzip.controller.php */
