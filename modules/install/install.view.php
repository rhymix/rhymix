<?php
    /**
     * @class  installView
     * @author NHN (developers@xpressengine.com)
     * @brief View class of install module
     **/

    class installView extends install {

        var $install_enable = false;

        /**
         * @brief Initialization
         **/
        function init() {
            // Specify the template path
            $this->setTemplatePath($this->module_path.'tpl');
            // Error occurs if already installed
            if(Context::isInstalled()) return $this->stop('msg_already_installed');
            // Install a controller
            $oInstallController = &getController('install');
            $this->install_enable = $oInstallController->checkInstallEnv();
            // If the environment is installable, execute installController::makeDefaultDirectory()
            if($this->install_enable) $oInstallController->makeDefaultDirectory();
        }

        /**
         * @brief Display license messages
         **/
        function dispInstallIntroduce() {
			$install_config_file = FileHandler::getRealPath('./config/install.config.php');
			if(file_exists($install_config_file)){
				include $install_config_file;
				if(is_array($install_config)){
					foreach($install_config as $k => $v) Context::set($k,$v,true);
					unset($GLOBALS['__DB__']);
					$oInstallController = &getController('install');
					$oInstallController->procInstall();
					header("location: ./");
					exit;
				}
			}

			$this->setTemplateFile('introduce');
        }

        /**
         * @brief Display messages about installation environment
         **/
        function dispInstallCheckEnv() {
            $this->setTemplateFile('check_env');
        }


        /**
         * @brief Choose a DB
         **/
        function dispInstallSelectDB() {
            // Display check_env if it is not installable
            if(!$this->install_enable) return $this->dispInstallCheckEnv();
            // Enter ftp information
            if(ini_get('safe_mode') && !Context::isFTPRegisted()) {
                $this->setTemplateFile('ftp');
            } else {
                $this->setTemplateFile('select_db');
            }
        }

        /**
         * @brief Display a screen to enter DB and administrator's information
         **/
        function dispInstallForm() {
            // Display check_env if not installable
            if(!$this->install_enable) return $this->dispInstallCheckEnv();
            // Return to the start-up screen if db_type is not specified
            if(!Context::get('db_type')) return $this->dispInstallSelectDB();

            Context::set('time_zone', $GLOBALS['time_zone']);
            // Output the file, disp_db_info_form.html
            $tpl_filename = sprintf('form.%s', Context::get('db_type'));
            $this->setTemplateFile($tpl_filename);
        }

    }
?>
