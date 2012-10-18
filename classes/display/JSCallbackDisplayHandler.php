<?php

class JSCallbackDisplayHandler{
	/**
	 * Produce JSCallback compliant content given a module object.\n
	 * @param ModuleObject $oModule the module object
	 * @return string
	 **/
	function toDoc(&$oModule)
	{
		$variables = $oModule->getVariables();
		$variables['error'] = $oModule->getError();
		$variables['message'] = $oModule->getMessage();
		$json = str_replace(array("\r\n","\n","\t"),array('\n','\n','\t'),json_encode2($variables));
		$output = sprintf('<script>%s(%s);</script>', Context::getJSCallbackFunc(), $json);
		return $output;
	}
}
