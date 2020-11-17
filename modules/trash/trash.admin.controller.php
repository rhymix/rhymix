<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * trashAdminController class
 * trash admin the module's controller class
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/trash
 * @version 0.1
 */
class trashAdminController extends trash
{
	/**
	 * object insert to trash
	 * @param TrashVO $obj
	 * @return Object
	 */
	function insertTrash($obj)
	{
		$logged_info = Context::get('logged_info');

		$oTrashVO = new TrashVO();
		$oTrashVO = &$obj;

		if(!$oTrashVO->getTrashSrl()) $oTrashVO->setTrashSrl(getNextSequence());
		if(!is_string($oTrashVO->getSerializedObject())) $oTrashVO->setSerializedObject(serialize($oTrashVO->getSerializedObject()));
		$oTrashVO->setIpaddress(\RX_CLIENT_IP);
		$oTrashVO->setRemoverSrl($logged_info->member_srl);
		$oTrashVO->setRegdate(date('YmdHis'));

		return executeQuery('trash.insertTrash', $oTrashVO);
	}

	/**
	 * Empty trash
	 * @param array trashSrls
	 * @return Object
	 */
	function procTrashAdminEmptyTrash()
	{
		global $lang;
		$isAll = Context::get('is_all');
		$originModule = Context::get('origin_module');
		$tmpTrashSrls = Context::get('cart');
		$is_type = Context::get('is_type');

		$trashSrls = array();
		if($isAll != 'true')
		{
			if(is_array($tmpTrashSrls))
			{
				$trashSrls = $tmpTrashSrls;
			}
			else
			{
				$trashSrls = explode('|@|', $tmpTrashSrls);
			}
		}

		//module relation data delete...
		$output = $this->_relationDataDelete($isAll, $is_type, $trashSrls);
		if(!$output->toBool()) throw new Rhymix\Framework\Exception($output->message);

		if(!$this->_emptyTrash($trashSrls)) throw new Rhymix\Framework\Exception($lang->fail_empty);

		$this->setMessage('success_deleted', 'info');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispTrashAdminList', 'origin_module', $originModule);
		$this->setRedirectUrl($returnUrl, $output);
	}

	/**
	 * Empty trash - private method
	 * @param string $isAll
	 * @param string $is_type
	 * @param array trashSrls
	 * @return Object
	 */
	function _relationDataDelete($isAll, $is_type, &$trashSrls)
	{
		$oTrashModel = getModel('trash');
		if($isAll == 'true')
		{
			$args = new stdClass();
			$args->originModule = $is_type;
			$output = $oTrashModel->getTrashAllList($args);
			if(!$output->toBool())
			{
				return $output;
			}

			if(is_array($output->data))
			{
				foreach($output->data as $value)
				{
					$trashSrls[] = $value->getTrashSrl();
				}
			}
		}
		else
		{
			$args = new stdClass();
			$args->trashSrl = $trashSrls;
			$output = $oTrashModel->getTrashList($args);
			if(!$output->toBool())
			{
				return $output;
			}
		}

		if(is_array($output->data))
		{
			foreach($output->data as $oTrashVO)
			{
				//class file check
				$classPath = ModuleHandler::getModulePath($oTrashVO->getOriginModule());
				if(!is_dir(FileHandler::getRealPath($classPath))) throw new Rhymix\Framework\Exception('not exist restore module directory');

				$classFile = sprintf('%s%s.admin.controller.php', $classPath, $oTrashVO->getOriginModule());
				$classFile = FileHandler::getRealPath($classFile);
				if(!file_exists($classFile)) throw new Rhymix\Framework\Exception('not exist restore module class file');

				$oAdminController = getAdminController($oTrashVO->getOriginModule());
				if(!method_exists($oAdminController, 'emptyTrash')) throw new Rhymix\Framework\Exception('not exist restore method in module class file');

				$output2 = $oAdminController->emptyTrash($oTrashVO->getSerializedObject());
				if(!$output2->toBool()) return $output;
			}
		}
		
		return new BaseObject(0, 'success_deleted');
	}

	/**
	 * Restore content object
	 * @return void|Object
	 */
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
			foreach($trashSrlList as $value)
			{
				$oTrashModel = getModel('trash');
				$output = $oTrashModel->getTrash($value);
				if(!$output->toBool()) return $output;

				//class file check
				$classPath = ModuleHandler::getModulePath($output->data->getOriginModule());
				if(!is_dir(FileHandler::getRealPath($classPath))) throw new Rhymix\Framework\Exception('not exist restore module directory');

				$classFile = sprintf('%s%s.admin.controller.php', $classPath, $output->data->getOriginModule());
				$classFile = FileHandler::getRealPath($classFile);
				if(!file_exists($classFile)) throw new Rhymix\Framework\Exception('not exist restore module class file');

				$oAdminController = getAdminController($output->data->getOriginModule());
				if(!method_exists($oAdminController, 'restoreTrash')) throw new Rhymix\Framework\Exception('not exist restore method in module class file');

				$originObject = unserialize($output->data->getSerializedObject());
				$output = $oAdminController->restoreTrash($originObject);

				if(!$output->toBool())
				{
					$oDB->rollback();
					return $output;
				}
			}

			// restore object delete in trash box
			if(!$this->_emptyTrash($trashSrlList)) {
				$oDB->rollback();
				throw new Rhymix\Framework\Exception($lang->fail_empty);
			}
			$oDB->commit();
		}

		$this->setMessage('success_restore', 'info');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispTrashAdminList');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * Set trash list to Context
	 * @return void|Object
	 */
	function procTrashAdminGetList()
	{
		if(!Context::get('is_logged')) throw new Rhymix\Framework\Exceptions\NotPermitted;
		$trashSrls = Context::get('trash_srls');
		if($trashSrls) $trashSrlList = explode(',', $trashSrls);

		if(count($trashSrlList) > 0)
		{
			$oTrashModel = getModel('trash');
			$args = new stdClass();
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

		$oSecurity = new Security($trashList);
		$oSecurity->encodeHTML('..');
		$this->add('trash_list', $trashList);
	}

	/**
	 * empty trash
	 * @param array trashSrls
	 * @return bool
	 */
	function _emptyTrash($trashSrls)
	{
		if(!is_array($trashSrls)) return false;
		$args = new stdClass();
		$args->trashSrls = $trashSrls;
		$output = executeQuery('trash.deleteTrash', $args);
		if(!$output->toBool()) return false;

		return true;
	}
}
/* End of file trash.admin.controller.php */
/* Location: ./modules/trash/trash.admin.controller.php */
