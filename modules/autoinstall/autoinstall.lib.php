<?php
	require_once(_XE_PATH_.'libs/ftp.class.php');

    class ModuleInstaller {
        var $package = null;
		var $base_url;
		var $temp_dir = './files/cache/autoinstall/';
        var $target_path;
        var $download_file;
        var $url;
        var $download_path;
        var $ftp_password;

		function setServerUrl($url)
		{
			$this->base_url = $url;
		}

		function uninstall()
		{
			$oModel =& getModel('autoinstall');
			$type = $oModel->getTypeFromPath($this->package->path);
			if($type == "module") {
				$output = $this->uninstallModule();
				if(!$output->toBool()) return $output;
			}

            $output = $this->_connect();
            if(!$output->toBool()) return $output;

			$output = $this->_removeDir($this->package->path);
			$this->_close();
			return $output;
		}

        function setPassword($ftp_password)
        {
            $this->ftp_password = $ftp_password;
        }

        function _download()
        {
            if($this->package->path == ".")
            {
                $this->download_file = $this->temp_dir."xe.tar";
                $this->target_path = "";
                $this->download_path = $this->temp_dir;
            }
            else
            {
                $subpath = substr($this->package->path,2);
                $this->download_file = $this->temp_dir.$subpath.".tar";
                $subpatharr = explode("/", $subpath);
                array_pop($subpatharr);
                $this->download_path = $this->temp_dir.implode("/", $subpatharr);
                $this->target_path = implode("/", $subpatharr);
            }

            $postdata = array();
            $postdata["path"] = $this->package->path;
            $postdata["module"] = "resourceapi";
            $postdata["act"] = "procResourceapiDownload";
            $buff = FileHandler::getRemoteResource($this->base_url, null, 3, "POST", "application/x-www-form-urlencoded", array(), array(), $postdata);
            FileHandler::writeFile($this->download_file, $buff);
        }

		function uninstallModule()
		{
            $path_array = explode("/", $this->package->path);
            $target_name = array_pop($path_array);
			$oModule =& getModule($target_name, "class");
			if(!$oModule) return new Object(-1, 'msg_invalid_request');
			if(!method_exists($oModule, "moduleUninstall")) return new Object(-1, 'msg_invalid_request');

			$output = $oModule->moduleUninstall();
			if(is_subclass_of($output, 'Object') && !$output->toBool()) return $output;

            $schema_dir = sprintf('%s/schemas/', $this->package->path);
            $schema_files = FileHandler::readDir($schema_dir);
			$oDB =& DB::getInstance();
			foreach($schema_files as $file)
			{
				$filename_arr = explode(".", $file);
				$filename = array_shift($filename_arr);
				$oDB->dropTable($filename);
			}
			return new Object();
		}

        function installModule()
        {
			$path = $this->package->path;
			if($path != ".") {
				$path_array = explode("/", $path);
				$target_name = array_pop($path_array);
				$type = substr(array_pop($path_array), 0, -1);
			}

            if($type == "module")
            {
                $oModuleModel = &getModel('module');
                $oInstallController = &getController('install');
                $module_path = ModuleHandler::getModulePath($target_name);
                if($oModuleModel->checkNeedInstall($target_name))
                {
                    $oInstallController->installModule($target_name, $module_path);
                }
                if($oModuleModel->checkNeedUpdate($target_name))
                {
                    $oModule = &getModule($target_name, 'class');
                    if(method_exists($oModule, 'moduleUpdate'))
                    {
                        $oModule->moduleUpdate();
                    }
                }
            }
        }

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

		function _unPack(){
            require_once(_XE_PATH_.'libs/tar.class.php');

            $oTar = new tar();
            $oTar->openTAR($this->download_file);

			$_files = $oTar->files;
            $file_list = array();
            foreach($_files as $key => $info) {
                FileHandler::writeFile($this->download_path."/".$info['name'], $info['file']);
                $file_list[] = $info['name'];
            }
            return $file_list;
		}

		function _removeDir($path) {
			$real_path = FileHandler::getRealPath($path);
			$oDir = dir($path);
			$files = array();
			while($file = $oDir->read()) {
				if($file == "." || $file == "..") continue;
				$files[] = $file;
			}

			foreach($files as $file)
			{
				$file_path = $path."/".$file;
				if(is_dir(FileHandler::getRealPath($file_path)))
				{
					$output = $this->_removeDir($file_path);
					if(!$output->toBool()) return $output;
				}
				else
				{
					$output = $this->_removeFile($file_path);
					if(!$output->toBool()) return $output;
				}
			}
			$output = $this->_removeDir_real($path);
			return $output;
		}

    }

    class SFTPModuleInstaller extends ModuleInstaller {
		var $ftp_info = null;
		var $connection = null;
		var $sftp = null;

        function SFTPModuleInstaller(&$package)
        {
            $this->package =& $package;
			$this->ftp_info = Context::getFTPInfo();
        }

		function _connect() {
            if(!$this->ftp_info->ftp_user || !$this->ftp_info->sftp || $this->ftp_info->sftp != 'Y') return new Object(-1,'msg_ftp_invalid_auth_info');

            if($this->ftp_info->ftp_host)
            {
                $ftp_host = $this->ftp_info->ftp_host;
            }
            else
            {
                $ftp_host = "127.0.0.1";
            }
            $this->connection = ssh2_connect($ftp_host, $this->ftp_info->ftp_port);
            if(!ssh2_auth_password($this->connection, $this->ftp_info->ftp_user, $this->ftp_password))
            {
                return new Object(-1,'msg_ftp_invalid_auth_info');
            }
            $_SESSION['ftp_password'] = $this->ftp_password;
            $this->sftp = ssh2_sftp($this->connection);
			return new Object();
		}

		function _close() {
		}

		function _removeFile($path)
		{
			if(substr($path, 0, 2) == "./") $path = substr($path, 2);
			$target_path = $this->ftp_info->ftp_root_path.$path;

			if(!@ssh2_sftp_unlink($this->sftp, $target_path))
			{
				return new Object(-1, "failed to delete file ".$path);
			}
			return new Object();
		}

		function _removeDir_real($path)
		{
			if(substr($path, 0, 2) == "./") $path = substr($path, 2);
			$target_path = $this->ftp_info->ftp_root_path.$path;

			if(!@ssh2_sftp_rmdir($this->sftp, $target_path))
			{
				return new Object(-1, "failed to delete directory ".$path);
			}
			return new Object();
		}

        function _copyDir(&$file_list){
            if(!$this->ftp_password) return new Object(-1,'msg_ftp_password_input');

			$output = $this->_connect();
			if(!$output->toBool()) return $output;
            $target_dir = $this->ftp_info->ftp_root_path.$this->target_path;

            foreach($file_list as $k => $file){
                $org_file = $file;
                if($this->package->path == ".")
                {
                    $file = substr($file,3);
                }
                $path = FileHandler::getRealPath("./".$this->target_path."/".$file);
                $pathname = dirname($target_dir."/".$file);

                if(!file_exists(FileHandler::getRealPath($real_path)))
                {
                    ssh2_sftp_mkdir($this->sftp, $pathname, 0755, true);
                }

                ssh2_scp_send($this->connection, FileHandler::getRealPath($this->download_path."/".$org_file), $target_dir."/".$file);
            }
            return new Object();
        }
    }


    class PHPFTPModuleInstaller extends ModuleInstaller {
		var $ftp_info = null;
		var $connection = null;

        function PHPFTPModuleInstaller(&$package)
        {
            $this->package =& $package;
			$this->ftp_info = Context::getFTPInfo();
        }

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
            if(!$this->connection) return new Object(-1, 'msg_ftp_not_connected');

            $login_result = @ftp_login($this->connection, $this->ftp_info->ftp_user, $this->ftp_password);
            if(!$login_result)
            {
				$this->_close();
                return new Object(-1,'msg_ftp_invalid_auth_info');
            }

            $_SESSION['ftp_password'] = $this->ftp_password;
			if($this->ftp_info->ftp_pasv != "N")
			{
				ftp_pasv($this->connection, true);
			}
			return new Object();
		}

		function _removeFile($path)
		{
			if(substr($path, 0, 2) == "./") $path = substr($path, 2);
			$target_path = $this->ftp_info->ftp_root_path.$path;

			if(!@ftp_delete($this->connection, $target_path))
			{
				return new Object(-1, "failed to delete file ".$path);
			}
			return new Object();
		}

		function _removeDir_real($path)
		{
			if(substr($path, 0, 2) == "./") $path = substr($path, 2);
			$target_path = $this->ftp_info->ftp_root_path.$path;

			if(!@ftp_rmdir($this->connection, $target_path))
			{
				return new Object(-1, "failed to delete directory ".$path);
			}
			return new Object();
		}


		function _close() {
            ftp_close($this->connection);
		}

        function _copyDir(&$file_list) {
            if(!$this->ftp_password) return new Object(-1,'msg_ftp_password_input');

            $output = $this->_connect();
			if(!$output->toBool()) return $output;
            $target_dir = $this->ftp_info->ftp_root_path.$this->target_path;

            foreach($file_list as $k => $file){
                $org_file = $file;
                if($this->package->path == ".")
                {
                    $file = substr($file,3);
                }
                $path = FileHandler::getRealPath("./".$this->target_path."/".$file);
                $path_list = explode('/', dirname($this->target_path."/".$file));

                $real_path = "./";
                $ftp_path = $this->ftp_info->ftp_root_path;

                for($i=0;$i<count($path_list);$i++)
                {
                    if($path_list=="") continue;
                    $real_path .= $path_list[$i]."/";
                    $ftp_path .= $path_list[$i]."/";
                    if(!file_exists(FileHandler::getRealPath($real_path)))
                    {
                        if(!@ftp_mkdir($this->connection, $ftp_path))
                        {
                            return new Object(-1, "msg_make_directory_failed");
                        }

                        if(!stristr(PHP_OS, 'win'))
                        {
                            if (function_exists('ftp_chmod')) {
                                if(!ftp_chmod($this->connection, 0755, $ftp_path))
                                {
                                    return new Object(-1, "msg_permission_adjust_failed");
                                }
                            }
                            else
                            {
                                if(!ftp_site($this->connection, "CHMOD 755 ".$ftp_path))
                                {
                                    return new Object(-1, "msg_permission_adjust_failed");
                                }
                            }
                        }
                    }
                }
                if(!ftp_put($this->connection, $target_dir .'/'. $file, FileHandler::getRealPath($this->download_path."/".$org_file), FTP_BINARY))
                {
                    return new Object(-1, "msg_ftp_upload_failed");
                }
            }

			$this->_close();
            return new Object();
        }
    }

    class FTPModuleInstaller extends ModuleInstaller {
		var $oFtp = null;
		var $ftp_info = null;

        function FTPModuleInstaller(&$package)
        {
            $this->package =& $package;
            $this->ftp_info =  Context::getFTPInfo();
        }

		function _connect() {
            if($this->ftp_info->ftp_host)
            {
                $ftp_host = $this->ftp_info->ftp_host;
            }
            else
            {
                $ftp_host = "127.0.0.1";
            }

            $this->oFtp = new ftp();
            if(!$this->oFtp->ftp_connect($ftp_host, $this->ftp_info->ftp_port)) return new Object(-1,'msg_ftp_not_connected');
            if(!$this->oFtp->ftp_login($this->ftp_info->ftp_user, $this->ftp_password)) {
				$this->_close();
                return new Object(-1,'msg_ftp_invalid_auth_info');
            }
            $_SESSION['ftp_password'] = $this->ftp_password;
			return new Object();
		}

		function _removeFile($path)
		{
			if(substr($path, 0, 2) == "./") $path = substr($path, 2);
			$target_path = $this->ftp_info->ftp_root_path.$path;

			if(!$this->oFtp->ftp_delete($target_path))
			{
				return new Object(-1, "failed to delete file ".$path);
			}
			return new Object();
		}

		function _removeDir_real($path)
		{
			if(substr($path, 0, 2) == "./") $path = substr($path, 2);
			$target_path = $this->ftp_info->ftp_root_path.$path;

			if(!$this->oFtp->ftp_rmdir($target_path))
			{
				return new Object(-1, "failed to delete directory ".$path);
			}
			return new Object();
		}

		function _close() {
            $this->oFtp->ftp_quit();
		}

		function _copyDir(&$file_list){
            if(!$this->ftp_password) return new Object(-1,'msg_ftp_password_input');

			$output = $this->_connect();
			if(!$output->toBool()) return $output;

			$oFtp =& $this->oFtp;
            $target_dir = $this->ftp_info->ftp_root_path.$this->target_path;

            foreach($file_list as $k => $file){
                $org_file = $file;
                if($this->package->path == ".")
                {
                    $file = substr($file,3);
                }
                $path = FileHandler::getRealPath("./".$this->target_path."/".$file);
                $path_list = explode('/', dirname($this->target_path."/".$file));

                $real_path = "./";
                $ftp_path = $this->ftp_info->ftp_root_path;

                for($i=0;$i<count($path_list);$i++)
                {
                    if($path_list=="") continue;
                    $real_path .= $path_list[$i]."/";
                    $ftp_path .= $path_list[$i]."/";
                    if(!file_exists(FileHandler::getRealPath($real_path)))
                    {
                        $oFtp->ftp_mkdir($ftp_path);
                        $oFtp->ftp_site("CHMOD 755 ".$ftp_path);
                    }
                }
                $oFtp->ftp_put($target_dir .'/'. $file, FileHandler::getRealPath($this->download_path."/".$org_file));
            }

			$this->_close();

            return new Object();
		}
    }

?>
