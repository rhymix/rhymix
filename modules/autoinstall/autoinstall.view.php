<?php

    /**
     * @class  autoinstallView
     * @author NHN (developers@xpressengine.com)
     * @brief  autoinstall 모듈의 View class
     **/

    class autoinstallView extends autoinstall {

        /**
         * @brief 초기화
         **/
        function init() {
		}

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
