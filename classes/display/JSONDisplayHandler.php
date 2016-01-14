<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

class JSONDisplayHandler
{

	/**
	 * Produce JSON compliant content given a module object.\n
	 * @param ModuleObject $oModule the module object
	 * @return string
	 */
	function toDoc(&$oModule)
	{
		$variables = $oModule->getVariables();
		$variables['error'] = $oModule->getError();
		$variables['message'] = $oModule->getMessage();
		return json_encode($variables);
	}

}
/* End of file JSONDisplayHandler.class.php */
/* Location: ./classes/display/JSONDisplayHandler.class.php */
