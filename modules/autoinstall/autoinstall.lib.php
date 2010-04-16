<?php

    class ModuleInstaller {
        var $package = null;
		var $base_url = 'http://download.xpressengine.com/';
		var $temp_dir = './files/cache/autoinstall/';
        var $target_path;
        var $download_file;
        var $url;
        var $download_path;
        var $ftp_password;

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
            $buff = FileHandler::getRemoteResource($this->base_url, null, 3, "POST", "application/x-www-form-urlencoded; charset=utf-8", array(), array(), $postdata);
            FileHandler::writeFile($this->download_file, $buff);
        }

        function installModule()
        {
            $path_array = explode("/", $this->package->path);
            $target_name = array_pop($path_array);
            $type = substr(array_pop($path_array), 0, -1);
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

    }

    class SFTPModuleInstaller extends ModuleInstaller {
        function SFTPModuleInstaller(&$package)
        {
            $this->package =& $package;
        }

        function _copyDir(&$file_list){
            if(!$this->ftp_password) return new Object(-1,'msg_ftp_password_input');

            $ftp_info =  Context::getFTPInfo();
            if(!$ftp_info->ftp_user || !$ftp_info->sftp || $ftp_info->sftp != 'Y') return new Object(-1,'msg_ftp_invalid_auth_info');
            
            if($ftp_info->ftp_host)
            {
                $ftp_host = $ftp_info->ftp_host;
            }
            else
            {
                $ftp_host = "127.0.0.1";
            }
            $connection = ssh2_connect($ftp_host, $ftp_info->ftp_port);
            if(!ssh2_auth_password($connection, $ftp_info->ftp_user, $this->ftp_password))
            {
                return new Object(-1,'msg_ftp_invalid_auth_info');
            }
            $_SESSION['ftp_password'] = $this->ftp_password;

            $sftp = ssh2_sftp($connection);

            $target_dir = $ftp_info->ftp_root_path.$this->target_path;

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
                    ssh2_sftp_mkdir($sftp, $pathname, 0755, true);
                }

                ssh2_scp_send($connection, FileHandler::getRealPath($this->download_path."/".$org_file), $target_dir."/".$file);
            } 
            return new Object();
        }
    }


    class PHPFTPModuleInstaller extends ModuleInstaller {
        function PHPFTPModuleInstaller(&$package)
        {
            $this->package =& $package;
        }

        function _copyDir(&$file_list) {
            if(!$this->ftp_password) return new Object(-1,'msg_ftp_password_input');

            $ftp_info =  Context::getFTPInfo();
            if($ftp_info->ftp_host)
            {
                $ftp_host = $ftp_info->ftp_host;
            }
            else
            {
                $ftp_host = "127.0.0.1";
            }

            $connection = ftp_connect($ftp_host, $ftp_info->ftp_port);
            if(!$connection) return new Object(-1, 'msg_ftp_not_connected');
            $login_result = @ftp_login($connection, $ftp_info->ftp_user, $this->ftp_password); 
            if(!$login_result)
            {
                return new Object(-1,'msg_ftp_invalid_auth_info');
            }
            $_SESSION['ftp_password'] = $this->ftp_password;
			if($ftp_info->ftp_pasv != "N") 
			{
				ftp_pasv($connection, true);
			}

            $target_dir = $ftp_info->ftp_root_path.$this->target_path;

            foreach($file_list as $k => $file){
                $org_file = $file;
                if($this->package->path == ".") 
                {
                    $file = substr($file,3);
                }
                $path = FileHandler::getRealPath("./".$this->target_path."/".$file);
                $path_list = explode('/', dirname($this->target_path."/".$file));

                $real_path = "./";
                $ftp_path = $ftp_info->ftp_root_path;

                for($i=0;$i<count($path_list);$i++)
                {
                    if($path_list=="") continue;
                    $real_path .= $path_list[$i]."/";
                    $ftp_path .= $path_list[$i]."/";
                    if(!file_exists(FileHandler::getRealPath($real_path)))
                    {
                        if(!ftp_mkdir($connection, $ftp_path))
                        {
                            return new Object(-1, "msg_make_directory_failed");  
                        }

                        if(!stristr(PHP_OS, 'win'))
                        {
                            if (function_exists('ftp_chmod')) {
                                if(!ftp_chmod($connection, 0755, $ftp_path))
                                {
                                    return new Object(-1, "msg_permission_adjust_failed");
                                }
                            }
                            else
                            {
                                if(!ftp_site($connection, "CHMOD 755 ".$ftp_path))
                                {
                                    return new Object(-1, "msg_permission_adjust_failed");
                                }
                            }
                        }
                    }
                }
                if(!ftp_put($connection, $target_dir .'/'. $file, FileHandler::getRealPath($this->download_path."/".$org_file), FTP_BINARY))
                {
                    return new Object(-1, "msg_ftp_upload_failed");
                }
            } 

            ftp_close($connection);
            return new Object();
        }
    }

    class FTPModuleInstaller extends ModuleInstaller {
        function FTPModuleInstaller(&$package)
        {
            $this->package =& $package;
        }

		function _copyDir(&$file_list){
            $ftp_info =  Context::getFTPInfo();
            if(!$this->ftp_password) return new Object(-1,'msg_ftp_password_input');

            require_once(_XE_PATH_.'libs/ftp.class.php');

            if($ftp_info->ftp_host)
            {
                $ftp_host = $ftp_info->ftp_host;
            }
            else
            {
                $ftp_host = "127.0.0.1";
            }

            $oFtp = new ftp();
            if(!$oFtp->ftp_connect($ftp_host, $ftp_info->ftp_port)) return new Object(-1,'msg_ftp_not_connected');
            if(!$oFtp->ftp_login($ftp_info->ftp_user, $this->ftp_password)) {
                $oFtp->ftp_quit();
                return new Object(-1,'msg_ftp_invalid_auth_info');
            }
            $_SESSION['ftp_password'] = $this->ftp_password;

            $_list = $oFtp->ftp_rawlist($ftp_info->ftp_root_path);

            $target_dir = $ftp_info->ftp_root_path.$this->target_path;

            foreach($file_list as $k => $file){
                $org_file = $file;
                if($this->package->path == ".") 
                {
                    $file = substr($file,3);
                }
                $path = FileHandler::getRealPath("./".$this->target_path."/".$file);
                $path_list = explode('/', dirname($this->target_path."/".$file));

                $real_path = "./";
                $ftp_path = $ftp_info->ftp_root_path;

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
            $oFtp->ftp_quit();

            return new Object();
		}
    }

?>
