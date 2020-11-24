<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * XML Generater
 * @author NAVER (developers@xpressengine.com)
 */
class XmlGenerater
{

	/**
	 * Generate XML using given data
	 *
	 * @param array $params The data
	 * @return string Returns xml string
	 */
	public static function generate(&$params)
	{
		$xmlDoc = '<?xml version="1.0" encoding="utf-8" ?><methodCall><params>';
		if(!is_array($params))
		{
			return NULL;
		}

		$params["module"] = "resourceapi";
		foreach($params as $key => $val)
		{
			$xmlDoc .= sprintf("<%s><![CDATA[%s]]></%s>", $key, $val, $key);
		}
		$xmlDoc .= "</params></methodCall>";
		return $xmlDoc;
	}

	/**
	 * Request data to server and returns result
	 *
	 * @param array $params Request data
	 * @return object
	 */
	public static function getXmlDoc(&$params)
	{
		$body = self::generate($params);
		$request_config = array(
			'ssl_verify_peer' => FALSE,
			'ssl_verify_host' => FALSE
		);

		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleConfig('autoinstall');
		$location_site = $module_info->location_site ? : 'https://xe1.xpressengine.com/';
		$download_server = $module_info->download_server ? : 'https://download.xpressengine.com/';

		$buff = FileHandler::getRemoteResource($download_server, $body, 3, "POST", "application/xml", array(), array(), array(), $request_config);
		if(!$buff)
		{
			return;
		}

		$xml = new XeXmlParser();
		$xmlDoc = $xml->parse($buff);
		return $xmlDoc;
	}

}

/**
 * High class of the autoinstall module
 * @author NAVER (developers@xpressengine.com)
 */
class autoinstall extends ModuleObject
{

	/**
	 * Temporary directory path
	 */
	var $tmp_dir = './files/cache/autoinstall/';

	/**
	 * For additional tasks required when installing
	 *
	 * @return Object
	 */
	function moduleInstall()
	{
	}

	/**
	 * Method to check if installation is succeeded
	 *
	 * @return bool
	 */
	function checkUpdate()
	{
		$oDB = DB::getInstance();
		$oModuleModel = getModel('module');

		if(!FileHandler::exists('./modules/autoinstall/schemas/autoinstall_installed_packages.xml') && $oDB->isTableExists("autoinstall_installed_packages"))
		{
			return TRUE;
		}
		if(!FileHandler::exists('./modules/autoinstall/schemas/autoinstall_remote_categories.xml')
				&& $oDB->isTableExists("autoinstall_remote_categories"))
		{
			return TRUE;
		}

		// 2011.08.08 add column 'list_order' in ai_remote_categories
		if(!$oDB->isColumnExists('ai_remote_categories', 'list_order'))
		{
			return TRUE;
		}

		// 2012.11.12 add column 'have_instance' in autoinstall_packages
		if(!$oDB->isColumnExists('autoinstall_packages', 'have_instance'))
		{
			return TRUE;
		}
		
		return FALSE;
	}

	/**
	 * Execute update
	 *
	 * @return Object
	 */
	function moduleUpdate()
	{
		$oDB = DB::getInstance();
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');

		if(!FileHandler::exists('./modules/autoinstall/schemas/autoinstall_installed_packages.xml')
				&& $oDB->isTableExists("autoinstall_installed_packages"))
		{
			$oDB->dropTable("autoinstall_installed_packages");
		}
		if(!FileHandler::exists('./modules/autoinstall/schemas/autoinstall_remote_categories.xml')
				&& $oDB->isTableExists("autoinstall_remote_categories"))
		{
			$oDB->dropTable("autoinstall_remote_categories");
		}

		// 2011.08.08 add column 'list_order' in 'ai_remote_categories
		if(!$oDB->isColumnExists('ai_remote_categories', 'list_order'))
		{
			$oDB->addColumn('ai_remote_categories', 'list_order', 'number', 11, NULL, TRUE);
			$oDB->addIndex('ai_remote_categories', 'idx_list_order', array('list_order'));
		}

		// 2012.11.12 add column 'have_instance' in autoinstall_packages
		if(!$oDB->isColumnExists('autoinstall_packages', 'have_instance'))
		{
			$oDB->addColumn('autoinstall_packages', 'have_instance', 'char', '1', 'N', TRUE);
		}
	}

	/**
	 * Re-generate the cache file
	 * @return Object
	 */
	function recompileCache()
	{
		
	}

}
/* End of file autoinstall.class.php */
/* Location: ./modules/autoinstall/autoinstall.class.php */
