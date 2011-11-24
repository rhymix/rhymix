<?php
/**
 * @class  document
 * @author NHN (developers@xpressengine.com)
 * @brief document the module's high class
 **/

require_once(_XE_PATH_.'modules/trash/model/TrashVO.php');

class trash extends ModuleObject {
	/**
	 * @brief Implement if additional tasks are necessary when installing
	 **/
	function moduleInstall() {
		return new Object();
	}

	/**
	 * @brief a method to check if successfully installed
	 **/
	function checkUpdate() {
		//$oDB = &DB::getInstance();
		//$oModuleModel = &getModel('module');

		return false;
	}

	/**
	 * @brief Execute update
	 **/
	function moduleUpdate() {
		//$oDB = &DB::getInstance();
		//$oModuleModel = &getModel('module');

		return new Object(0,'success_updated');

	}
}
?>
