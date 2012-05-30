<?php

    /**
     * View class of the autoinstall module
     * @author NHN (developers@xpressengine.com)
     **/
    class autoinstallView extends autoinstall {

        /**
         * Initialization
		 *
		 * @return void
         **/
        function init() {
		}

		/**
		 * Test
		 *
		 * @return Object
		 */
		function dispAutoinstallTest(){
			$file = "modules.test.tar";
            $checksum = '549989037bd8401d39b83ca2393d8131';
			$file = "modules.test.skins.test.tar";
			$oAutoinstallAdminController = &getAdminController('autoinstall');
			$output = $oAutoinstallAdminController->install($file, $checksum);
            return $output;
		}
    }
?>
