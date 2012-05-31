<?php
    /**
     * High class of counter module
	 *
     * @author NHN (developers@xpressengine.com)
     **/

    class counter extends ModuleObject {

        /**
         * Implement if additional tasks are necessary when installing
		 * @return Object
         **/
        function moduleInstall() {
            $oCounterController = &getController('counter');
            // add a row for the total visit history 
            //$oCounterController->insertTotalStatus();
            // add a row for today's status
            //$oCounterController->insertTodayStatus();

            return new Object();
        }

        /**
         * method if successfully installed
		 *
		 * @return bool
         **/
        function checkUpdate() {
            // Add site_srl to the counter
            $oDB = &DB::getInstance();
            if(!$oDB->isColumnExists('counter_log', 'site_srl')) return true;
            if(!$oDB->isIndexExists('counter_log','idx_site_counter_log')) return true;
            
            return false;
        }

        /**
         * Module update
		 *
		 * @return Object
         **/
        function moduleUpdate() {
            // Add site_srl to the counter
            $oDB = &DB::getInstance();
            if(!$oDB->isColumnExists('counter_log', 'site_srl')) $oDB->addColumn('counter_log','site_srl','number',11,0,true);
            if(!$oDB->isIndexExists('counter_log','idx_site_counter_log')) $oDB->addIndex('counter_log','idx_site_counter_log',array('site_srl','ipaddress'),false);

            return new Object(0, 'success_updated');
        }

        /**
         * re-generate the cache file
		 *
		 * @return Object
         **/
        function recompileCache() {
        }
    }
?>
