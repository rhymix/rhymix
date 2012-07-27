<?php
	/**
	 * adminlogging class
	 * Base class of adminlogging module
	 *
	 * @author NHN (developers@xpressengine.com)
	 * @package /modules/adminlogging
	 * @version 0.1
	 */
    class adminlogging extends ModuleObject {
		/**
		 * Install adminlogging module
		 * @return Object
		 */
        function moduleInstall() {
            return new Object();
        }

		/**
		 * If update is necessary it returns true
		 * @return bool
		 */
        function checkUpdate() {
            return false;
        }

		/**
		 * Update module
		 * @return Object
		 */
        function moduleUpdate() {
            return new Object();
        }

		/**
		 * Regenerate cache file
		 * @return void
		 */
        function recompileCache() {
        }
    }
?>
