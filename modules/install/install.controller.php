<?php
    /**
     * @class  installController
     * @author NHN (developers@xpressengine.com)
     * @brief install module of the Controller class
     **/

    class installController extends install {

        /**
         * @brief Initialization
         **/
        function init() {
            // Error occurs if already installed
            if(Context::isInstalled()) {
                return new Object(-1, 'msg_already_installed');
            }
        }

        /**
         * @brief Install with received information
         **/
        function procInstall() {
            // Check if it is already installed
            if(Context::isInstalled()) return new Object(-1, 'msg_already_installed');
            // Assign a temporary administrator when installing
            $logged_info->is_admin = 'Y';
            $_SESSION['logged_info'] = $logged_info;
            Context::set('logged_info', $logged_info);
            // Get DB-related variables
            $db_info = Context::gets('db_type','db_port','db_hostname','db_userid','db_password','db_database','db_table_prefix','time_zone','use_rewrite');
            if($db_info->use_rewrite!='Y') $db_info->use_rewrite = 'N';
            if(!$db_info->default_url) $db_info->default_url = Context::getRequestUri();
            $db_info->lang_type = Context::getLangType();
            // Set DB type and information
            Context::setDBInfo($db_info);
            // Create DB Instance
            $oDB = &DB::getInstance();
            // Check if available to connect to the DB
            $output = $oDB->getError();
            if(!$oDB->isConnected()) return $oDB->getError();
            // When installing firebire DB, transaction will not be used
            if($db_info->db_type != "firebird") $oDB->begin();
            // Install all the modules
            $this->installDownloadedModule();

            if($db_info->db_type != "firebird") $oDB->commit();
            // Create a config file
            if(!$this->makeConfigFile()) return new Object(-1, 'msg_install_failed');

			// load script
            $scripts = FileHandler::readDir('./modules/install/script','/(\.php)$/');
			if(count($scripts)>0){
				sort($scripts);
				foreach($scripts as $script){
					$output = include(FileHandler::getRealPath('./modules/install/script/'.$script));
				}
			}

            // Display a message that installation is completed
            $this->setMessage('msg_install_completed');
        }

        /**
         * @brief Set FTP Information
         **/
        function procInstallFTP() {
            if(Context::isInstalled()) return new Object(-1, 'msg_already_installed');
            $ftp_info = Context::gets('ftp_host', 'ftp_user','ftp_password','ftp_port','ftp_root_path');
            $ftp_info->ftp_port = (int)$ftp_info->ftp_port;
            if(!$ftp_info->ftp_port) $ftp_info->ftp_port = 21;
			if(!$ftp_info->ftp_host) $ftp_info->ftp_host = '127.0.0.1';
			if(!$ftp_info->ftp_root_path) $ftp_info->ftp_root_path = '/';

            $buff = '<?php if(!defined("__ZBXE__")) exit();'."\n";
            foreach($ftp_info as $key => $val) {
                $buff .= sprintf("\$ftp_info->%s = '%s';\n", $key, str_replace("'","\\'",$val));
            }
            $buff .= "?>";
            // If safe_mode
            if(ini_get('safe_mode')) {
                if(!$ftp_info->ftp_user || !$ftp_info->ftp_password) return new Object(-1,'msg_safe_mode_ftp_needed');

                require_once(_XE_PATH_.'libs/ftp.class.php');
                $oFtp = new ftp();
                if(!$oFtp->ftp_connect($ftp_info->ftp_host, $ftp_info->ftp_port)) return new Object(-1,'msg_ftp_not_connected');

                if(!$oFtp->ftp_login($ftp_info->ftp_user, $ftp_info->ftp_password)) {
                    $oFtp->ftp_quit();
                    return new Object(-1,'msg_ftp_invalid_auth_info');
                }

                if(!is_dir(_XE_PATH_.'files') && !$oFtp->ftp_mkdir($ftp_info->ftp_root_path.'files')) {
                    $oFtp->ftp_quit();
                    return new Object(-1,'msg_ftp_mkdir_fail');
                }

                if(!$oFtp->ftp_site("CHMOD 777 ".$ftp_info->ftp_root_path.'files')) {
                    $oFtp->ftp_quit();
                    return new Object(-1,'msg_ftp_chmod_fail');
                }

                if(!is_dir(_XE_PATH_.'files/config') && !$oFtp->ftp_mkdir($ftp_info->ftp_root_path.'files/config')) {
                    $oFtp->ftp_quit();
                    return new Object(-1,'msg_ftp_mkdir_fail');
                }

                if(!$oFtp->ftp_site("CHMOD 777 ".$ftp_info->ftp_root_path.'files/config')) {
                    $oFtp->ftp_quit();
                    return new Object(-1,'msg_ftp_chmod_fail');
                }

                $oFtp->ftp_quit();
            } 

            $config_file = Context::getFTPConfigFile();
            FileHandler::WriteFile($config_file, $buff);
        }

        function procInstallCheckFtp() {
            $ftp_info = Context::gets('ftp_user','ftp_password','ftp_port','sftp');
            $ftp_info->ftp_port = (int)$ftp_info->ftp_port;
            if(!$ftp_info->ftp_port) $ftp_info->ftp_port = 21;
            if(!$ftp_info->sftp) $ftp_info->sftp = 'N';

            if(!$ftp_info->ftp_user || !$ftp_info->ftp_password) return new Object(-1,'msg_safe_mode_ftp_needed');

            if($ftp_info->sftp == 'Y')
            {
                $connection = ssh2_connect('localhost', $ftp_info->ftp_port);
                if(!ssh2_auth_password($connection, $ftp_info->ftp_user, $ftp_info->ftp_password))
                {
                    return new Object(-1,'msg_ftp_invalid_auth_info');
                }
            }
            else
            {
                require_once(_XE_PATH_.'libs/ftp.class.php');
                $oFtp = new ftp();
                if(!$oFtp->ftp_connect('localhost', $ftp_info->ftp_port)) return new Object(-1,'msg_ftp_not_connected');

                if(!$oFtp->ftp_login($ftp_info->ftp_user, $ftp_info->ftp_password)) {
                    $oFtp->ftp_quit();
                    return new Object(-1,'msg_ftp_invalid_auth_info');
                }

                $oFtp->ftp_quit();
            }

            $this->setMessage('msg_ftp_connect_success');
        }

        /**
         * @brief Result returned after checking the installation environment
         **/
        function checkInstallEnv() {
            // Check each item
            $checklist = array();
            // 0. check your version of php (5.2.2 is not supported)
            if(phpversion()=='5.2.2') $checklist['php_version'] = false;
            else $checklist['php_version'] = true;
            // 1. Check permission
            if(is_writable('./')||is_writable('./files')) $checklist['permission'] = true;
            else $checklist['permission'] = false;
            // 2. Check if xml_parser_create exists
            if(function_exists('xml_parser_create')) $checklist['xml'] = true;
            else $checklist['xml'] = false;
            // 3. Check if ini_get (session.auto_start) == 1 
            if(ini_get(session.auto_start)!=1) $checklist['session'] = true;
            else $checklist['session'] = false;
            // 4. Check if iconv exists
            if(function_exists('iconv')) $checklist['iconv'] = true;
            else $checklist['iconv'] = false;
            // 5. Check gd(imagecreatefromgif function)
            if(function_exists('imagecreatefromgif')) $checklist['gd'] = true;
            else $checklist['gd'] = false;

            if(!$checklist['php_version'] || !$checklist['permission'] || !$checklist['xml'] || !$checklist['session']) $install_enable = false;
            else $install_enable = true;
            // Save the checked result to the Context
            Context::set('checklist', $checklist);
            Context::set('install_enable', $install_enable);

            return $install_enable;
        }

        /**
         * @brief Create files and subdirectories
         * Local evironment setting before installation by using DB information
         **/
        function makeDefaultDirectory() {
            $directory_list = array(
                    './files/config',
                    './files/cache/queries',
                    './files/cache/js_filter_compiled',
                    './files/cache/template_compiled',
                );

            foreach($directory_list as $dir) {
                FileHandler::makeDir($dir);
            }
        }

        /**
         * @brief Install all the modules
         *
         * Create a table by using schema xml file in the shcema directory of each module
         **/
        function installDownloadedModule() { 
            $oModuleModel = &getModel('module');
            // Create a table ny finding schemas/*.xml file in each module
            $module_list = FileHandler::readDir('./modules/', NULL, false, true);
            foreach($module_list as $module_path) {
                // Get module name
                $tmp_arr = explode('/',$module_path);
                $module = $tmp_arr[count($tmp_arr)-1];

                $xml_info = $oModuleModel->getModuleInfoXml($module);
                if(!$xml_info) continue;
                $modules[$xml_info->category][] = $module;
            }
            // Install "module" module in advance
            $this->installModule('module','./modules/module');
            $oModule = &getClass('module');
            if($oModule->checkUpdate()) $oModule->moduleUpdate();
            // Determine the order of module installation depending on category                      
            $install_step = array('system','content','member');
            // Install all the remaining modules
            foreach($install_step as $category) {
                if(count($modules[$category])) {
                    foreach($modules[$category] as $module) {
                        if($module == 'module') continue;
                        $this->installModule($module, sprintf('./modules/%s', $module));

                        $oModule = &getClass($module);
                        if($oModule->checkUpdate()) $oModule->moduleUpdate();
                    }
                    unset($modules[$category]);
                }
            }
            // Install all the remaining modules
            if(count($modules)) {
                foreach($modules as $category => $module_list) {
                    if(count($module_list)) {
                        foreach($module_list as $module) {
                            if($module == 'module') continue;
                            $this->installModule($module, sprintf('./modules/%s', $module));

                            $oModule = &getClass($module);
							if($oModule && method_exists($oModule, 'checkUpdate') && method_exists($oModule, 'moduleUpdate'))
							{
								if($oModule->checkUpdate()) $oModule->moduleUpdate();
							}
                        }
                    }
                }
            }

            return new Object();
        }

        /**
         * @brief Install an each module
         **/
        function installModule($module, $module_path) {
            // create db instance
            $oDB = &DB::getInstance();
            // Create a table if the schema xml exists in the "schemas" directory of the module
            $schema_dir = sprintf('%s/schemas/', $module_path);
            $schema_files = FileHandler::readDir($schema_dir, NULL, false, true);

            $file_cnt = count($schema_files);
            for($i=0;$i<$file_cnt;$i++) {
                $file = trim($schema_files[$i]);
                if(!$file || substr($file,-4)!='.xml') continue;
                $output = $oDB->createTableByXmlFile($file);
            }
            // Create a table and module instance and then execute install() method
            unset($oModule);
            $oModule = &getClass($module);
            if(method_exists($oModule, 'moduleInstall')) $oModule->moduleInstall();

            return new Object();
        }

        /**
         * @brief Create config file
         * Create the config file when all settings are completed
         **/
        function makeConfigFile() {
            $config_file = Context::getConfigFile();
            //if(file_exists($config_file)) return;

            $db_info = Context::getDbInfo();
            if(!$db_info) return;

            $buff = '<?php if(!defined("__ZBXE__")) exit();'."\n";
            foreach($db_info as $key => $val) {
                $buff .= sprintf("\$db_info->%s = '%s';\n", $key, str_replace("'","\\'",$val));
            }
            $buff .= "?>";

            FileHandler::writeFile($config_file, $buff);

            if(@file_exists($config_file)) return true;
            return false;
        }

		function installByConfig($install_config_file){
			include $install_config_file;
			if(!is_array($auto_config)) return false;

			$auto_config['module'] = 'install';
			$auto_config['act'] = 'procInstall';

			$fstr = "<%s><![CDATA[%s]]></%s>\r\n";
			$fheader = "POST %s HTTP/1.1\r\nHost: %s\r\nContent-Type: application/xml\r\nContent-Length: %s\r\n\r\n%s\r\n";
			$body = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\r\n<methodCall>\r\n<params>\r\n";
			foreach($auto_config as $k => $v){
				if(!in_array($k,array('host','port','path'))) $body .= sprintf($fstr,$k,$v,$k);
			}
			$body .= "</params>\r\n</methodCall>";

			$header = sprintf($fheader,$auto_config['path'],$auto_config['host'],strlen($body),$body);
			$fp = @fsockopen($auto_config['host'], $auto_config['port'], $errno, $errstr, 5); 
			if($fp){
				fputs($fp, $header);
				while(!feof($fp)) {
					$line = trim(fgets($fp, 4096));
					if(preg_match("/^<error>/i",$line)){
						fclose($fp);
						return false;
					}
				}   
				fclose($fp);
			}
			return true;

		}
    }
?>
