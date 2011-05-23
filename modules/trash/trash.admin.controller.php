<?php
/**
 * @class  documentController
 * @author NHN (developers@xpressengine.com)
 * @brief document the module's controller class
 **/
class trashAdminController extends trash
{
	/**
	 * @brief object insert to trash
	 **/
	function insertTrash($obj)
	{
		if(Context::get('is_logged'))
		{
			$logged_info = Context::get('logged_info');

			$oTrashVO = new TrashVO();
			$oTrashVO->setTrashSrl(getNextSequence());
			$oTrashVO->setTitle($obj->title);
			$oTrashVO->setOriginModule($obj->trashType);
			$oTrashVO->setSerializedObject(serialize($obj->originObject));
			$oTrashVO->setDescription($obj->description);
			$oTrashVO->setIpaddress($_SERVER['REMOTE_ADDR']);
			$oTrashVO->setRemoverSrl($logged_info->member_srl);
			$oTrashVO->setRegdate(date('YmdHis'));

			$output = executeQuery('trash.insertTrash', $oTrashVO);
			return $output;
		}
		return new Object(-1, 'msg_not_permitted');
	}

	/**
	 * @brief empty trash
	 * @param trashSrls : trash_srl in array
	 **/
	function procTrashAdminEmptyTrash()
	{
		global $lang;
		$isAll = Context::get('is_all');
		$trashSrls = explode('|@|', Context::get('trash_srls'));

		$oTrashModel = &getModel('trash');
		if($isAll == 'true')
		{
			$trashSrls = array();

			//module relation data delete...
			$output = $oTrashModel->getTrashList($args);
			if(!$output->toBool()) return new Object(-1, $output->message);

			if(is_array($output->data))
			{
				foreach($output->data AS $key=>$oTrashVO)
				{
					//class file check
					$classPath = ModuleHandler::getModulePath($oTrashVO->getOriginModule());
					if(!is_dir(FileHandler::getRealPath($classPath))) return new Object(-1, 'not exist restore module directory');

					$classFile = sprintf('%s%s.admin.controller.php', $classPath, $oTrashVO->getOriginModule());
					$classFile = FileHandler::getRealPath($classFile);
					if(!file_exists($classFile)) return new Object(-1, 'not exist restore module class file');

					$oAdminController = &getAdminController($oTrashVO->getOriginModule());
					if(!method_exists($oAdminController, 'emptyTrash')) return new Object(-1, 'not exist restore method in module class file');

					$output = $oAdminController->emptyTrash($oTrashVO->getSerializedObject());
				}
			}
		}

		if(!$this->_emptyTrash($trashSrls))
			return new Object(-1, $lang->fail_empty);

		return new Object(0, $lang->success_empty);
	}

	function procTrashAdminRestore()
	{
		global $lang;
		$trashSrl = Context::get('trash_srl');

		$oTrashModel = &getModel('trash');
		$output = $oTrashModel->getTrash($trashSrl);
		if(!$output->toBool()) return new Object(-1, $output->message);

		//class file check
		$classPath = ModuleHandler::getModulePath($output->data->getOriginModule());
		if(!is_dir(FileHandler::getRealPath($classPath))) return new Object(-1, 'not exist restore module directory');

		$classFile = sprintf('%s%s.admin.controller.php', $classPath, $output->data->getOriginModule());
		$classFile = FileHandler::getRealPath($classFile);
		if(!file_exists($classFile)) return new Object(-1, 'not exist restore module class file');

		$oAdminController = &getAdminController($output->data->getOriginModule());
		if(!method_exists($oAdminController, 'restoreTrash')) return new Object(-1, 'not exist restore method in module class file');

		// begin transaction
		$oDB = &DB::getInstance();
		$oDB->begin();

		$originObject = unserialize($output->data->getSerializedObject());
		$output = $oAdminController->restoreTrash($originObject);

		if(!$output->toBool()) {
			$oDB->rollback();
			return new Object(-1, $output->message);
		}
		else
		{
			Context::set('is_all', 'false');
			Context::set('trash_srls', $trashSrl);
			$output = $this->procTrashAdminEmptyTrash();
			if(!$output->toBool()) {
				$oDB->rollback();
				return new Object(-1, $output->message);
			}
		}
		$oDB->commit();
		return new Object(0, $lang->success_restore);
	}

	/**
	 * @brief empty trash
	 * @param trashSrls : trash_srl in array
	 **/
	function _emptyTrash($trashSrls)
	{
		if(!is_array($trashSrls)) return false;
		$args->trashSrls = $trashSrls;
		$output = executeQuery('trash.deleteTrash', $args);
		if(!$output->toBool()) return false;

		return true;
	}
}

/* End of file trash.controller.php */
/* Location: ./modules/trash/trash.controller.php */
