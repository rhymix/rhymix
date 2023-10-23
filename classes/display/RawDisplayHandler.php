<?php

class RawDisplayHandler
{
	function toDoc($oModule)
	{
		$tpl_path = $oModule->getTemplatePath();
		$tpl_file = $oModule->getTemplateFile();
		if ($tpl_path && $tpl_file)
		{
			$oTemplate = new Rhymix\Framework\Template($tpl_path, $tpl_file);
			$output = $oTemplate->compile();
		}
		else
		{
			$output = '';
		}
		return $output;
	}
}
