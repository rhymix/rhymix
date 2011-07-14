<?php

class trashModel extends trash
{
	/**
	 * @brief get one trash object
	 **/
	function getTrash($trashSrl, $columnList = array())
	{
		$oTrashVO = new TrashVO();
		if(!$trashSrl) return $oTrashVO;

		$args->trashSrl = $trashSrl;
		$output = executeQuery('trash.getTrash', $args, $columnList);

		$this->_setTrashObject($oTrashVO, $output->data);
		$output->data = $oTrashVO;

		return $output;
	}

	/**
	 * @brief get trash object list
	 **/
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
	 * @brief set trash object from std object
	 **/
	function _setTrashObject(&$oTrashVO, $stdObject)
	{
		$oTrashVO->setTrashSrl($stdObject->trash_srl);
		$oTrashVO->setTitle($stdObject->title);
		$oTrashVO->setOriginModule($stdObject->origin_module);
		$oTrashVO->setSerializedObject($stdObject->serialized_object);
		$oTrashVO->setDescription($stdObject->description);
		$oTrashVO->setIpaddress($stdObject->ipaddress);
		$oTrashVO->setRemoverSrl($stdObject->remover_srl);
		$oTrashVO->setUserId($stdObject->user_id);
		$oTrashVO->setNickName($stdObject->nick_name);
		$oTrashVO->setRegdate($stdObject->regdate);
	}
}

