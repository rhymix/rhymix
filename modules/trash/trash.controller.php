<?php
/**
 * @class  documentController
 * @author NHN (developers@xpressengine.com)
 * @brief document the module's controller class
 **/
class trashController extends trash
{
	/**
	 * @brief object insert to trash
	 **/
	function insertTrash($oTrashVO)
	{
		$output = executeQuery('trash.insertTrash', $oTrashVO);
		debugPrint($output);
		return $output;
	}

	/**
	 * @brief empty trash
	 * @param trashSrls : trash_srl in array
	 **/
	function emptyTrash($trashSrls)
	{
		if(!is_array($trashSrls)) return false;
		executeQuery('trash.deleteTrash', $trashSrls);
	}
}

/* End of file trash.controller.php */
/* Location: ./modules/trash/trash.controller.php */
