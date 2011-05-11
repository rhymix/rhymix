<?php
    /**
     * @class  opageAdminController
     * @author NHN (developers@xpressengine.com)
     * @brief admin controller class of the opage module 
     **/

    class opageAdminController extends opage {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief Add an external page
         **/
        function procOpageAdminInsert() {
            // Create model/controller object of the module module
            $oModuleController = &getController('module');
            $oModuleModel = &getModel('module');
            // Set board module
            $args = Context::getRequestVars();
            $args->module = 'opage';
            $args->mid = $args->opage_name;
            unset($args->opage_name);
            // Check if an original module exists by using module_srl
            if($args->module_srl) {
				$columnList = array('module_srl');
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl, $columnList);
                if($module_info->module_srl != $args->module_srl) unset($args->module_srl);
            }
            // Insert/update depending on module_srl
            if(!$args->module_srl) {
                $args->module_srl = getNextSequence();
                $output = $oModuleController->insertModule($args);
                $msg_code = 'success_registed';
            } else {
                $output = $oModuleController->updateModule($args);
                $msg_code = 'success_updated';
                // Delete cache files
                $cache_file = sprintf("./files/cache/opage/%d.cache.php", $module_info->module_srl);
                if(file_exists($cache_file)) FileHandler::removeFile($cache_file);
            }

            if(!$output->toBool()) return $output;
            // Messages to output when successfully registered
            $this->add("module_srl", $output->get('module_srl'));
            $this->add("opage", Context::get('opage'));
            $this->setMessage($msg_code);
        }

        /**
         * @brief Delete an external page
         **/
        function procOpageAdminDelete() {
            $module_srl = Context::get('module_srl');
            // Get an original
            $oModuleController = &getController('module');
            $output = $oModuleController->deleteModule($module_srl);
            if(!$output->toBool()) return $output;

            $this->add('module','opage');
            $this->add('opage',Context::get('opage'));
            $this->setMessage('success_deleted');
        }

        /**
         * @brief Add information of an external page
         **/
        function procOpageAdminInsertConfig() {
            // Get the basic info
            $args = Context::gets('test');

        }

    }
?>
