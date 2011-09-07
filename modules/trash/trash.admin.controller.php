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
	 * @param $obj : TrashVO type object
	 **/
	function insertTrash($obj)
	{
		if(Context::get('is_logged'))
		{
			$logged_info = Context::get('logged_info');

			$oTrashVO = new TrashVO();
			$oTrashVO = &$obj;

			if(!$oTrashVO->getTrashSrl()) $oTrashVO->setTrashSrl(getNextSequence());
			if(!is_string($oTrashVO->getSerializedObject())) $oTrashVO->setSerializedObject(serialize($oTrashVO->getSerializedObject()));
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
		$originModule = Context::get('origin_module');
		$tmpTrashSrls = Context::get('cart');
		if(is_array($tmpTrashSrls)) $trashSrls = $tmpTrashSrls;
		else $trashSrls = explode('|@|', $tmpTrashSrls);

		//module relation data delete...
		$output = $this->_relationDataDelete($isAll, $trashSrls);
		if(!$output->toBool()) return new Object(-1, $output->message);

		if(!$this->_emptyTrash($trashSrls)) return new Object(-1, $lang->fail_empty);

		$this->setMessage('success_deleted', 'info');
		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispTrashAdminList', 'origin_module', $originModule);
			$this->setRedirectUrl($returnUrl);
			return;
		}
		return new Object(0, $lang->success_empty);
	}

	/**
	 * @brief empty trash - private method
	 **/
	function _relationDataDelete($isAll, &$trashSrls)
	{
		if($isAll == 'true') $trashSrls = array();
		$oTrashModel = &getModel('trash');
		if(count($trashSrls) > 0) $args->trashSrl = $trashSrls;
		$output = $oTrashModel->getTrashList($args);
		if(!$output->toBool()) return new Object(-1, $output->message);

		if(is_array($output->data))
		{
			foreach($output->data AS $key=>$oTrashVO)
			{
				if($isAll == 'true') array_push($trashSrls, $oTrashVO->getTrashSrl());

				//class file check
				$classPath = ModuleHandler::getModulePath($oTrashVO->getOriginModule());
				if(!is_dir(FileHandler::getRealPath($classPath))) return new Object(-1, 'not exist restore module directory');

				$classFile = sprintf('%s%s.admin.controller.php', $classPath, $oTrashVO->getOriginModule());
				$classFile = FileHandler::getRealPath($classFile);
				if(!file_exists($classFile)) return new Object(-1, 'not exist restore module class file');

				$oAdminController = &getAdminController($oTrashVO->getOriginModule());
				if(!method_exists($oAdminController, 'emptyTrash')) return new Object(-1, 'not exist restore method in module class file');

				$output = $oAdminController->emptyTrash($oTrashVO->getSerializedObject());
				if(!$output->toBool()) return new Object(-1, $output->message);
			}
		}
		return new Object(0, $lang->success_deleted);
	}

	/**
	 * @brief restore content object
	 **/
	function procTrashAdminRestore()
	{
		global $lang;
		$trashSrlList = Context::get('cart');

		if(is_array($trashSrlList))
		{
			// begin transaction
			$oDB = &DB::getInstance();
			$oDB->begin();
			// eache restore method call in each classfile
			foreach($trashSrlList AS $key=>$value)
			{
				$oTrashModel = &getModel('trash');
				$output = $oTrashModel->getTrash($value);
				if(!$output->toBool()) return new Object(-1, $output->message);

				//class file check
				$classPath = ModuleHandler::getModulePath($output->data->getOriginModule());
				if(!is_dir(FileHandler::getRealPath($classPath))) return new Object(-1, 'not exist restore module directory');

				$classFile = sprintf('%s%s.admin.controller.php', $classPath, $output->data->getOriginModule());
				$classFile = FileHandler::getRealPath($classFile);
				if(!file_exists($classFile)) return new Object(-1, 'not exist restore module class file');

				$oAdminController = &getAdminController($output->data->getOriginModule());
				if(!method_exists($oAdminController, 'restoreTrash')) return new Object(-1, 'not exist restore method in module class file');

				$originObject = unserialize($output->data->getSerializedObject());
				$output = $oAdminController->restoreTrash($originObject);

				if(!$output->toBool()) {
					$oDB->rollback();
					return new Object(-1, $output->message);
				}
			}

			// restore object delete in trash box
			if(!$this->_emptyTrash($trashSrlList)) {
				$oDB->rollback();
				return new Object(-1, $lang->fail_empty);
			}
			$oDB->commit();
		}

		$this->setMessage('success_restore', 'info');
		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispTrashAdminList');
			$this->setRedirectUrl($returnUrl);
			return;
		}
	}

	function procTrashAdminGetList()
	{
		if(!Context::get('is_logged')) return new Object(-1,'msg_not_permitted');
		$trashSrls = Context::get('trash_srls');
		if($trashSrls) $trashSrlList = explode(',', $trashSrls);

		if(count($trashSrlList) > 0) {
			$oTrashModel = &getModel('trash');
			$args->trashSrl = $trashSrlList;
			$output = $oTrashModel->getTrashList($args);
			$trashList = $output->data;
		}
		else
		{
			global $lang;
			$trashList = array();
			$this->setMessage($lang->no_documents);
		}

		$this->add('trash_list', $trashList);
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
