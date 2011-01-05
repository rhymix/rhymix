<?php

class JSONDisplayHandler {
	function toDoc(&$oModule)
	{
		$variables = $oModule->getVariables();
		$variables['error'] = $oModule->getError();
		$variables['message'] = $oModule->getMessage();
		$json = str_replace(array("\r\n","\n","\t"),array('\n','\n','\t'),json_encode2($variables));
		return $json;
	}
}
