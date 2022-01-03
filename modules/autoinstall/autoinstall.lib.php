<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Module installer
 * @author NAVER (developers@xpressengine.com)
 */
class ModuleInstaller
{

	/**
	 * Package information
	 * @var object
	 */
	var $package = NULL;

	/**
	 * Server's base url
	 * @var string
	 */
	var $base_url;

	/**
	 * Temporary directory
	 * @var string
	 */
	var $temp_dir = './files/cache/autoinstall/';

	/**
	 * Install path
	 * @var string
	 */
	var $target_path;

	/**
	 * Downloaded file path
	 * @var string
	 */
	var $download_file;

	/**
	 * ???
	 * @var string
	 */
	var $url;

	/**
	 * Download path
	 * @var string
	 */
	var $download_path;

	/**
	 * FTP password
	 * @var string
	 */
	var $ftp_password;

	/**
	 * Set server's base url
	 *
	 * @param string $url The url to set
	 * @return void
	 */
	function setServerUrl($url)
	{
		$this->base_url = $url;
	}

	/**
	 * Uninstall
	 *
	 * @return Object
	 */
	function uninstall()
	{
		$oModel = getModel('autoinstall');
		$type = $oModel->getTypeFromPath($this->package->path);
		if($type == "module")
		{
			$output = $this->uninstallModule();
			if(!$output->toBool())
			{
				return $output;
			}
		}

		$output = $this->_connect();
		if(!$output->toBool())
		{
			return $output;
		}

		$output = $this->_removeDir($this->package->path);
		$this->_close();
		return $output;
	}

	/**
	 * Set a FTP password
	 *
	 * @param string $ftp_password The password to set.
	 * @return void
	 */
	function setPassword($ftp_password)
	{
		$this->ftp_password = $ftp_password;
	}

	/**
	 * Download file from server
	 *
	 * @return void
	 */
	function _download()
	{
		if($this->package->path == ".")
		{
			$this->download_file = $this->temp_dir . "xe.tar";
			$this->target_path = "";
			$this->download_path = $this->temp_dir;
		}
		else
		{
			$subpath = trim(substr($this->package->path, 2), '/');
			$this->download_file = $this->temp_dir . $subpath . ".tar";
			$subpatharr = explode("/", $subpath);
			array_pop($subpatharr);
			$this->download_path = $this->temp_dir . implode("/", $subpatharr);
			$this->target_path = implode("/", $subpatharr);
		}

		$postdata = array();
		$postdata["path"] = $this->package->path;
		$postdata["module"] = "resourceapi";
		$postdata["act"] = "procResourceapiDownload";
		$request_config = array(
			'ssl_verify_peer' => FALSE,
			'ssl_verify_host' => FALSE
		);
		$buff = FileHandler::getRemoteResource($this->base_url, NULL, 3, "POST", "application/x-www-form-urlencoded", array(), array(), $postdata, $request_config);
		FileHandler::writeFile($this->download_file, $buff);
	}

	/**
	 * Uninstall module.
	 *
	 * Call module's moduleUninstall() and drop all tables related module
	 *
	 * @return Object
	 */
	function uninstallModule()
	{
		$path_array = explode("/", $this->package->path);
		$target_name = array_pop($path_array);
		$oModule = ModuleModel::getModuleInstallClass($target_name);
		if(!$oModule || !method_exists($oModule, 'moduleUninstall'))
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		$output = $oModule->moduleUninstall();
		if($output instanceof BaseObject && !$output->toBool())
		{
			return $output;
		}

		$schema_dir = sprintf('%s/schemas/', $this->package->path);
		$schema_files = FileHandler::readDir($schema_dir);
		$oDB = DB::getInstance();
		if(is_array($schema_files))
		{
			foreach($schema_files as $file)
			{
				$filename_arr = explode(".", $file);
				$filename = array_shift($filename_arr);
				$oDB->dropTable($filename);
			}
		}
		return new BaseObject();
	}

	/**
	 * Install module
	 *
	 * Call module's moduleInstall(), moduleUpdate() and create tables.
	 *
	 * @return void
	 */
	function installModule()
	{
		$path = $this->package->path;
		if($path != ".")
		{
			$path_array = explode("/", $path);
			$target_name = array_pop($path_array);
			$type = substr(array_pop($path_array), 0, -1);
		}

		if($type == "module")
		{
			$oInstallController = getController('install');
			$module_path = ModuleHandler::getModulePath($target_name);
			if(ModuleModel::checkNeedInstall($target_name))
			{
				$oInstallController->installModule($target_name, $module_path);
			}
			if(ModuleModel::checkNeedUpdate($target_name))
			{
				$oModule = ModuleModel::getModuleInstallClass($target_name);
				if(method_exists($oModule, 'moduleUpdate'))
				{
					$oModule->moduleUpdate();
				}
			}
		}
	}

	/**
	 * Install module.
	 *
	 * Download file and install module
	 *
	 * @return Object
	 */
	function install()
	{
		$this->_download();
		$file_list = $this->_unPack();
		$output = $this->_copyDir($file_list);
		if(!$output->toBool())
		{
			FileHandler::removeDir($this->temp_dir);
			return $output;
		}
		$this->installModule();

		FileHandler::removeDir($this->temp_dir);
		return new BaseObject();
	}

	/**
	 * Untar a downloaded tar ball
	 *
	 * @return array Returns file list
	 */
	function _unPack()
	{
		$oTar = new tar();
		$oTar->openTAR($this->download_file);

		$_files = $oTar->files;
		$file_list = array();
		if(is_array($_files))
		{
			foreach($_files as $key => $info)
			{
				FileHandler::writeFile($this->download_path . "/" . $info['name'], $info['file']);
				$file_list[] = $info['name'];
			}
		}
		return $file_list;
	}

	/**
	 * Remove directory
	 *
	 * @param string $path Path to remove
	 * @return Object
	 */
	function _removeDir($path)
	{
		$real_path = FileHandler::getRealPath($path);
		$oDir = dir($path);
		$files = array();
		while($file = $oDir->read())
		{
			if($file == "." || $file == "..")
			{
				continue;
			}
			$files[] = $file;
		}

		foreach($files as $file)
		{
			$file_path = $path . "/" . $file;
			if(is_dir(FileHandler::getRealPath($file_path)))
			{
				$output = $this->_removeDir($file_path);
				if(!$output->toBool())
				{
					return $output;
				}
			}
			else
			{
				$output = $this->_removeFile($file_path);
				if(!$output->toBool())
				{
					return $output;
				}
			}
		}
		$output = $this->_removeDir_real($path);
		return $output;
	}

}

/**
 * Module installer for Direct. Not use FTP
 * @author NAVER (developers@xpressengine.com)
 */
class DirectModuleInstaller extends ModuleInstaller
{
	/**
	 * Constructor
	 *
	 * @param object $package Package information
	 */
	function __construct(&$package)
	{
		$this->package = &$package;
	}

	/**
	 * empty
	 *
	 * @return Object
	 */
	function _connect()
	{
		return new BaseObject();
	}

	/**
	 * Remove file
	 *
	 * @param string $path Path to remove
	 * @return Object
	 */
	function _removeFile($path)
	{
		if(substr($path, 0, 2) == "./")
		{
			$path = substr($path, 2);
		}
		$target_path = FileHandler::getRealPath($path);

		if(!FileHandler::removeFile($target_path))
		{
			return new BaseObject(-1, sprintf(lang('msg_delete_file_failed'), $path));
		}
		return new BaseObject();
	}

	/**
	 * Remove directory
	 * @param string $path Path to remove
	 * @return Object
	 */
	function _removeDir_real($path)
	{
		if(substr($path, 0, 2) == "./")
		{
			$path = substr($path, 2);
		}
		$target_path = FileHandler::getRealPath($path);

		FileHandler::removeDir($target_path);

		return new BaseObject();
	}

	/**
	 * Close
	 *
	 * @return void
	 */
	function _close()
	{
	}

	/**
	 * Copy directory
	 *
	 * @param array $file_list File list to copy
	 * @return Object
	 */
	function _copyDir(&$file_list)
	{
		$output = $this->_connect();
		if(!$output->toBool())
		{
			return $output;
		}
		$target_dir = $this->target_path;
		$copied = array();

		if(is_array($file_list))
		{
			foreach($file_list as $k => $file)
			{
				$org_file = $file;
				if($this->package->path == ".")
				{
					$file = substr($file, 3);
				}
				$path = FileHandler::getRealPath("./" . $this->target_path . "/" . $file);
				$path_list = explode('/', dirname($this->target_path . "/" . $file));
				$real_path = "./";

				for($i = 0; $i < count($path_list); $i++)
				{
					if($path_list == "")
					{
						continue;
					}
					$real_path .= $path_list[$i] . "/";
					if(!file_exists(FileHandler::getRealPath($real_path)))
					{
						FileHandler::makeDir($real_path);
					}
				}
				FileHandler::copyFile( FileHandler::getRealPath($this->download_path . "/" . $org_file), FileHandler::getRealPath("./" . $target_dir . '/' . $file));
				$copied[] = $path;
			}
		}

		$this->_close();

		return new BaseObject();
	}

}
/* End of file autoinstall.lib.php */
/* Location: ./modules/autoinstall/autoinstall.lib.php */
