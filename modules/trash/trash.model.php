<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * trashModel class
 * trash the module's model class
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/trash
 * @version 0.1
 */
class trashModel extends trash
{
	private static $config = NULL;

	/**
	 * get Tresh Module Config
	 *
	 * @return trash config
	 */
	function getConfig()
	{
		if(self::$config === NULL)
		{
			$oModuleModel = getModel('module');
			$config = $oModuleModel->getModuleConfig('trash');
			if(!$config)
			{
				$config = new stdClass();
			}

			self::$config = $config;
		}

		return self::$config;
	}

	/**
	 * Get one trash object
	 * @param int $trashSrl
	 * @pram array $columnList
	 * @return TrashVO
	 */
	function getTrash($trashSrl, $columnList = array())
	{
		$oTrashVO = new TrashVO();
		if(!$trashSrl) return $oTrashVO;

		$args = new stdClass();
		$args->trashSrl = $trashSrl;
		$output = executeQuery('trash.getTrash', $args, $columnList);

		$this->_setTrashObject($oTrashVO, $output->data);
		$output->data = $oTrashVO;

		return $output;
	}

	/**
	 * Get TrashVO list
	 * @param object $args
	 * @param array $columnList
	 * @return object
	 */
	function getTrashList($args, $columnList = array())
	{
		$output = executeQueryArray('trash.getTrashList', $args, $columnList);

		if(is_array($output->data))
		{
			foreach($output->data AS $key=>$value)
			{
				$oTrashVO = new TrashVO();
				$this->_setTrashObject($oTrashVO, $value);
				$output->data[$key] = $oTrashVO;
			}
		}
		return $output;
	}

	/**
	 * Get TrashVO all list
	 * @param object $args
	 * @param array $columnList
	 * @return object
	 */
	function getTrashAllList($args, $columnList = array())
	{
		$output = executeQueryArray('trash.getTrashAllList', $args, $columnList);

		if(is_array($output->data))
		{
			foreach($output->data AS $key=>$value)
			{
				$oTrashVO = new TrashVO();
				$this->_setTrashObject($oTrashVO, $value);
				$output->data[$key] = $oTrashVO;
			}
		}
		return $output;
	}

	/**
	 * Set trash object from std object
	 * @param TrashVO $oTrashVO
	 * @param object $stdObject
	 * @return void
	 */
	function _setTrashObject(&$oTrashVO, $stdObject)
	{
		$oTrashVO->setTrashSrl($stdObject->trash_srl);
		$oTrashVO->setTitle($stdObject->title);
		$oTrashVO->setOriginModule($stdObject->origin_module);
		$oTrashVO->setSerializedObject($stdObject->serialized_object);
		$oTrashVO->setUnserializedObject($stdObject->serialized_object);
		$oTrashVO->setDescription($stdObject->description);
		$oTrashVO->setIpaddress($stdObject->ipaddress);
		$oTrashVO->setRemoverSrl($stdObject->remover_srl);
		$oTrashVO->setUserId($stdObject->user_id);
		$oTrashVO->setNickName($stdObject->nick_name);
		$oTrashVO->setRegdate($stdObject->regdate);
	}
}
/* End of file trash.model.php */
/* Location: ./modules/trash/trash.model.php */
