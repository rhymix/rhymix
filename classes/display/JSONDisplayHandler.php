<?php

class JSONDisplayHandler {
	function toDoc(&$oModule)
	{
		$variables = $oModule->getVariables();
		$variables['error'] = $oModule->getError();
		$variables['message'] = $oModule->getMessage();
		$json = preg_replace("(\r\n|\n)",'\n',json_encode2($variables));
		$json = preg_replace("\t",'\t',$json);
		return $json;
	}
}
