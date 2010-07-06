<?php

class moduleMobile extends moduleObject {
	function dispModuleChangeLang() { 
		$this->setTemplatePath(sprintf("%stpl/",$this->module_path));
		$this->setTemplateFile('lang.html');
	}
}

?>
