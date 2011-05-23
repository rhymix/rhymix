<?php
/**
 * @class  trashView
 * @author NHN (developers@xpressengine.com)
 * @brief View class of the module trash
 **/

class trashView extends trash {

	/**
	 * @brief Initialization
	 **/
	function init() {
	}

	/**
	 * @brief 
	 **/
	function dispTrash() {
		$trashSrl = Context::get('trashSrl');
		debugPrint($trashSrl);

		$oWastebasketModel = getModel('trash');
		$output = $oWastebasketModel->getTrash($trashSrl);
	}

	/**
	 * @brief 
	 **/
	function dispTrashList() {
		$oWastebasketModel = getModel('trash');
		$output = $oWastebasketModel->getTrashList();
	}
}
?>
