<?php
    /**
     * @class  krzipAdminController
     * @author NHN (developers@xpressengine.com)
     * @brief admin controller class of the krzip module 
     **/

    class krzipAdminController extends krzip {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief Configuration
         **/
        function procKrzipAdminInsertConfig() {
            // Get the basic information
            $args = Context::gets('krzip_server_hostname','krzip_server_port','krzip_server_query');
            if(!$args->krzip_server_hostname) $args->krzip_server_hostname = $this->hostname;
            if(!$args->krzip_server_port) $args->krzip_server_port = $this->port;
            if(!$args->krzip_server_query) $args->krzip_server_query = $this->query;
            // Insert by creating the module Controller object
            $oModuleController = &getController('module');
            $output = $oModuleController->insertModuleConfig('krzip',$args);
            return $output;
        }
        
    }
?>
