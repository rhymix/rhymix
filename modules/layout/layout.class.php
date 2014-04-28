<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  layout
 * @author NAVER (developers@xpressengine.com)
 * high class of the layout module 
 */
class layout extends ModuleObject
{
	/**
	 * Implement if additional tasks are necessary when installing
	 * @return Object
	 */
	function moduleInstall()
	{
		// Create a directory to be used in the layout
		FileHandler::makeDir('./files/cache/layout');

		return new Object();
	}

	/**
	 * a method to check if successfully installed
	 * @return boolean
	 */
	function checkUpdate()
	{
		$oDB = &DB::getInstance();
		// 2009. 02. 11 Add site_srl to layout table
		if(!$oDB->isColumnExists('layouts', 'site_srl')) return true;
		// 2009. 02. 26 Move the previous layout for faceoff
		$files = FileHandler::readDir('./files/cache/layout');
		for($i=0,$c=count($files);$i<$c;$i++)
		{
			$filename = $files[$i];
			if(preg_match('/([0-9]+)\.html/i',$filename)) return true;
		}

		if(!$oDB->isColumnExists('layouts', 'layout_type')) return true;

		$args = new stdClass();
		$args->layout = '.';
		$output = executeQueryArray('layout.getLayoutDotList', $args);
		if($output->data && count($output->data) > 0)
		{
			foreach($output->data as $layout)
			{
				$layout_path = explode('.', $layout->layout);
				if(count($layout_path) != 2) continue;
				if(is_dir(sprintf(_XE_PATH_ . 'themes/%s/layouts/%s', $layout_path[0], $layout_path[1]))) return true;
			}
		}

		return false;
	}

	/**
	 * Execute update
	 * @return Object
	 */
	function moduleUpdate()
	{
		$oDB = &DB::getInstance();
		// 2009. 02. 11 Add site_srl to menu table
		if(!$oDB->isColumnExists('layouts', 'site_srl'))
		{
			$oDB->addColumn('layouts','site_srl','number',11,0,true);
		}
		// 2009. 02. 26 Move the previous layout for faceoff
		$oLayoutModel = getModel('layout');
		$files = FileHandler::readDir('./files/cache/layout');
		for($i=0,$c=count($files);$i<$c;$i++)
		{
			$filename = $files[$i];
			if(!preg_match('/([0-9]+)\.html/i',$filename,$match)) continue;
			$layout_srl = $match[1];
			if(!$layout_srl) continue;
			$path = $oLayoutModel->getUserLayoutPath($layout_srl);
			if(!is_dir($path)) FileHandler::makeDir($path);
			FileHandler::copyFile('./files/cache/layout/'.$filename, $path.'layout.html');
			@unlink('./files/cache/layout/'.$filename);
		}

		if(!$oDB->isColumnExists('layouts', 'layout_type'))
		{
			$oDB->addColumn('layouts','layout_type','char',1,'P',true);
		}

		$args->layout = '.';
		$output = executeQueryArray('layout.getLayoutDotList', $args);
		if($output->data && count($output->data) > 0)
		{
			foreach($output->data as $layout)
			{
				$layout_path = explode('.', $layout->layout);
				if(count($layout_path) != 2) continue;
				if(is_dir(sprintf(_XE_PATH_ . 'themes/%s/layouts/%s', $layout_path[0], $layout_path[1])))
				{
					$args->layout = implode('|@|', $layout_path);
					$args->layout_srl = $layout->layout_srl;
					$output = executeQuery('layout.updateLayout', $args);
				}
			}
		}
		return new Object(0, 'success_updated');
	}

	/**
	 * Re-generate the cache file
	 * @return void
	 */
	function recompileCache()
	{
		$path = './files/cache/layout';
		if(!is_dir($path))
		{
			FileHandler::makeDir($path);
			return;
		}
	}
}
/* End of file layout.class.php */
/* Location: ./modules/layout/layout.class.php */
