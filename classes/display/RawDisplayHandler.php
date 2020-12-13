<?php

class RawDisplayHandler
{
	function toDoc($oModule)
	{
		$tpl_path = $oModule->getTemplatePath();
		$tpl_file = $oModule->getTemplateFile();
		if ($tpl_path && $tpl_file)
		{
			$oTemplate = TemplateHandler::getInstance();
			$output = $oTemplate->compile($tpl_path, $tpl_file);
		}
		else
		{
			$output = '';
		}
		return $output;
	}
}
