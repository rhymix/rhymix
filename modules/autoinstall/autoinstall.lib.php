<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

require_once(_XE_PATH_ . 'libs/ftp.class.php');

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
		$buff = FileHandler::getRemoteResource($this->base_url, NULL, 3, "POST", "application/x-www-form-urlencoded", array(), array(), $postdata);
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
		$oModule = getModule($target_name, "class");
		if(!$oModule)
		{
			return new Object(-1, 'msg_invalid_request');
		}
		if(!method_exists($oModule, "moduleUninstall"))
		{
			return new Object(-1, 'msg_invalid_request');
		}

		$output = $oModule->moduleUninstall();
		if(is_subclass_of($output, 'Object') && !$output->toBool())
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
		return new Object();
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
			$oModuleModel = getModel('module');
			$oInstallController = getController('install');
			$module_path = ModuleHandler::getModulePath($target_name);
			if($oModuleModel->checkNeedInstall($target_name))
			{
				$oInstallController->installModule($target_name, $module_path);
			}
			if($oModuleModel->checkNeedUpdate($target_name))
			{
				$oModule = getModule($target_name, 'class');
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
		return new Object();
	}

	/**
	 * Untar a downloaded tar ball
	 *
	 * @return array Returns file list
	 */
	function _unPack()
	{
		require_once(_XE_PATH_ . 'libs/tar.class.php');

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
 * Module installer for SFTP
 * @author NAVER (developers@xpressengine.com)
 */
class SFTPModuleInstaller extends ModuleInstaller
{

	/**
	 * FTP information
	 * @var object
	 */
	var $ftp_info = NULL;

	/**
	 * SFTP connection
	 * @var resource
	 */
	var $connection = NULL;

	/**
	 * SFTP resource
	 * @var resource
	 */
	var $sftp = NULL;

	/**
	 * Constructor
	 *
	 * @param object $package Package information
	 * @return void
	 */
	function SFTPModuleInstaller(&$package)
	{
		$this->package = &$package;
		$this->ftp_info = Context::getFTPInfo();
	}

	/**
	 * Connect to SFTP
	 *
	 * @return Object
	 */
	function _connect()
	{
		if(!function_exists('ssh2_connect'))
		{
			return new Object(-1, 'msg_sftp_not_supported');
		}

		if(!$this->ftp_info->ftp_user || !$this->ftp_info->sftp || $this->ftp_info->sftp != 'Y')
		{
			return new Object(-1, 'msg_ftp_invalid_auth_info');
		}

		if($this->ftp_info->ftp_host)
		{
			$ftp_host = $this->ftp_info->ftp_host;
		}
		else
		{
			$ftp_host = "127.0.0.1";
		}
		$this->connection = ssh2_connect($ftp_host, $this->ftp_info->ftp_port);
		if(!@ssh2_auth_password($this->connection, $this->ftp_info->ftp_user, $this->ftp_password))
		{
			return new Object(-1, 'msg_ftp_invalid_auth_info');
		}
		$_SESSION['ftp_password'] = $this->ftp_password;
		$this->sftp = ssh2_sftp($this->connection);
		return new Object();
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
		$target_path = $this->ftp_info->ftp_root_path . $path;

		if(!@ssh2_sftp_unlink($this->sftp, $target_path))
		{
			return new Object(-1, sprintf(Context::getLang('msg_delete_file_failed'), $path));
		}
		return new Object();
	}

	/**
	 * Remove Directory
	 *
	 * @param string $path Path to remove
	 * @return Object
	 */
	function _removeDir_real($path)
	{
		if(substr($path, 0, 2) == "./")
		{
			$path = substr($path, 2);
		}
		$target_path = $this->ftp_info->ftp_root_path . $path;

		if(!@ssh2_sftp_rmdir($this->sftp, $target_path))
		{
			return new Object(-1, sprintf(Context::getLang('msg_delete_dir_failed'), $path));
		}
		return new Object();
	}

	/**
	 * Copy directory
	 *
	 * @param array $file_list File list to copy
	 * @return Object
	 */
	function _copyDir(&$file_list)
	{
		if(!$this->ftp_password)
		{
			return new Object(-1, 'msg_ftp_password_input');
		}

		$output = $this->_connect();
		if(!$output->toBool())
		{
			return $output;
		}
		$target_dir = $this->ftp_info->ftp_root_path . $this->target_path;

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
				$pathname = dirname($target_dir . "/" . $file);

				if(!file_exists(FileHandler::getRealPath($real_path)))
				{
					ssh2_sftp_mkdir($this->sftp, $pathname, 0755, TRUE);
				}

				ssh2_scp_send($this->connection, FileHandler::getRealPath($this->download_path . "/" . $org_file), $target_dir . "/" . $file);
			}
		}
		return new Object();
	}

}

/**
 * Module installer for PHP FTP
 * @author NAVER (developers@xpressengine.com)
 */
class PHPFTPModuleInstaller extends ModuleInstaller
{

	/**
	 * FTP information
	 * @var object
	 */
	var $ftp_info = NULL;

	/**
	 * FTP connection
	 * @var resource
	 */
	var $connection = NULL;

	/**
	 * Constructor
	 *
	 * @param object $package Package information
	 * @var void
	 */
	function PHPFTPModuleInstaller(&$package)
	{
		$this->package = &$package;
		$this->ftp_info = Context::getFTPInfo();
	}

	/**
	 * Connect to FTP
	 *
	 * @return Object
	 */
	function _connect()
	{
		if($this->ftp_info->ftp_host)
		{
			$ftp_host = $this->ftp_info->ftp_host;
		}
		else
		{
			$ftp_host = "127.0.0.1";
		}

		$this->connection = ftp_connect($ftp_host, $this->ftp_info->ftp_port);
		if(!$this->connection)
		{
			return new Object(-1, sprintf(Context::getLang('msg_ftp_not_connected'), $ftp_host));
		}

		$login_result = @ftp_login($this->connection, $this->ftp_info->ftp_user, $this->ftp_password);
		if(!$login_result)
		{
			$this->_close();
			return new Object(-1, 'msg_ftp_invalid_auth_info');
		}

		$_SESSION['ftp_password'] = $this->ftp_password;
		if($this->ftp_info->ftp_pasv != "N")
		{
			ftp_pasv($this->connection, TRUE);
		}
		return new Object();
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
		$target_path = $this->ftp_info->ftp_root_path . $path;

		if(!@ftp_delete($this->connection, $target_path))
		{
			return new Object(-1, "failed to delete file " . $path);
		}
		return new Object();
	}

	/**
	 * Remove directory
	 *
	 * @param string $path Path to remove
	 * @return Object
	 */
	function _removeDir_real($path)
	{
		if(substr($path, 0, 2) == "./")
		{
			$path = substr($path, 2);
		}
		$target_path = $this->ftp_info->ftp_root_path . $path;

		if(!@ftp_rmdir($this->connection, $target_path))
		{
			return new Object(-1, "failed to delete directory " . $path);
		}
		return new Object();
	}

	/**
	 * Close
	 *
	 * @return void
	 */
	function _close()
	{
		ftp_close($this->connection);
	}

	/**
	 * Copy directory
	 *
	 * @param array $file_list File list to copy
	 * @return Object
	 */
	function _copyDir(&$file_list)
	{
		if(!$this->ftp_password)
		{
			return new Object(-1, 'msg_ftp_password_input');
		}

		$output = $this->_connect();
		if(!$output->toBool())
		{
			return $output;
		}

		if(!$this->target_path)
		{
			$this->target_path = '.';
		}
		if(substr($this->download_path, -1) == '/')
		{
			$this->download_path = substr($this->download_path, 0, -1);
		}
		$target_dir = $this->ftp_info->ftp_root_path . $this->target_path;

		if(is_array($file_list))
		{
			foreach($file_list as $k => $file)
			{
				if(!$file)
				{
					continue;
				}
				$org_file = $file;
				if($this->package->path == ".")
				{
					$file = substr($file, 3);
				}
				$path = FileHandler::getRealPath("./" . $this->target_path . "/" . $file);
				$path_list = explode('/', dirname($this->target_path . "/" . $file));

				$real_path = "./";
				$ftp_path = $this->ftp_info->ftp_root_path;

				for($i = 0; $i < count($path_list); $i++)
				{
					if($path_list == "")
					{
						continue;
					}
					$real_path .= $path_list[$i] . "/";
					$ftp_path .= $path_list[$i] . "/";
					if(!file_exists(FileHandler::getRealPath($real_path)))
					{
						if(!@ftp_mkdir($this->connection, $ftp_path))
						{
							return new Object(-1, "msg_make_directory_failed");
						}

						if(strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN')
						{
							if(function_exists('ftp_chmod'))
							{
								if(!ftp_chmod($this->connection, 0755, $ftp_path))
								{
									return new Object(-1, "msg_permission_adjust_failed");
								}
							}
							else
							{
								if(!ftp_site($this->connection, "CHMOD 755 " . $ftp_path))
								{
									return new Object(-1, "msg_permission_adjust_failed");
								}
							}
						}
					}
				}
				if(!ftp_put($this->connection, $target_dir . '/' . $file, FileHandler::getRealPath($this->download_path . "/" . $org_file), FTP_BINARY))
				{
					return new Object(-1, "msg_ftp_upload_failed");
				}
			}
		}
		$this->_close();
		return new Object();
	}

}

/**
 * Module installer for FTP
 * @author NAVER (developers@xpressengine.com)
 */
class FTPModuleInstaller extends ModuleInstaller
{

	/**
	 * FTP instance
	 * @var FTP
	 */
	var $oFtp = NULL;

	/**
	 * FTP information
	 * @var object
	 */
	var $ftp_info = NULL;

	/**
	 * Constructor
	 *
	 * @param object $package Package information
	 */
	function FTPModuleInstaller(&$package)
	{
		$this->package = &$package;
		$this->ftp_info = Context::getFTPInfo();
	}

	/**
	 * Connect to FTP
	 *
	 * @return Object
	 */
	function _connect()
	{
		if($this->ftp_info->ftp_host)
		{
			$ftp_host = $this->ftp_info->ftp_host;
		}
		else
		{
			$ftp_host = "127.0.0.1";
		}

		$this->oFtp = new ftp();
		if(!$this->oFtp->ftp_connect($ftp_host, $this->ftp_info->ftp_port))
		{
			return new Object(-1, sprintf(Context::getLang('msg_ftp_not_connected'), $ftp_host));
		}
		if(!$this->oFtp->ftp_login($this->ftp_info->ftp_user, $this->ftp_password))
		{
			$this->_close();
			return new Object(-1, 'msg_ftp_invalid_auth_info');
		}
		$_SESSION['ftp_password'] = $this->ftp_password;
		return new Object();
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
		$target_path = $this->ftp_info->ftp_root_path . $path;

		if(!$this->oFtp->ftp_delete($target_path))
		{
			return new Object(-1, sprintf(Context::getLang('msg_delete_file_failed'), $path));
		}
		return new Object();
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
		$target_path = $this->ftp_info->ftp_root_path . $path;

		if(!$this->oFtp->ftp_rmdir($target_path))
		{
			return new Object(-1, sprintf(Context::getLang('msg_delete_dir_failed'), $path));
		}
		return new Object();
	}

	/**
	 * Close
	 *
	 * @return void
	 */
	function _close()
	{
		$this->oFtp->ftp_quit();
	}

	/**
	 * Copy directory
	 *
	 * @param array $file_list File list to copy
	 * @return Object
	 */
	function _copyDir(&$file_list)
	{
		if(!$this->ftp_password)
		{
			return new Object(-1, 'msg_ftp_password_input');
		}

		$output = $this->_connect();
		if(!$output->toBool())
		{
			return $output;
		}

		$oFtp = &$this->oFtp;
		$target_dir = $this->ftp_info->ftp_root_path . $this->target_path;

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
				$ftp_path = $this->ftp_info->ftp_root_path;

				for($i = 0; $i < count($path_list); $i++)
				{
					if($path_list == "")
					{
						continue;
					}
					$real_path .= $path_list[$i] . "/";
					$ftp_path .= $path_list[$i] . "/";
					if(!file_exists(FileHandler::getRealPath($real_path)))
					{
						$oFtp->ftp_mkdir($ftp_path);
						$oFtp->ftp_site("CHMOD 755 " . $ftp_path);
					}
				}
				$oFtp->ftp_put($target_dir . '/' . $file, FileHandler::getRealPath($this->download_path . "/" . $org_file));
			}
		}

		$this->_close();

		return new Object();
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
	function DirectModuleInstaller(&$package)
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
		return new Object();
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
			return new Object(-1, sprintf(Context::getLang('msg_delete_file_failed'), $path));
		}
		return new Object();
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

		return new Object();
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
			}
		}

		$this->_close();

		return new Object();
	}

}
/* End of file autoinstall.lib.php */
/* Location: ./modules/autoinstall/autoinstall.lib.php */
