<?php
    /**
     * @class  homepageAdminModel
     * @author zero (zero@nzeo.com)
     * @brief  homepage 모듈의 admin model class
     **/

    class homepageAdminModel extends homepage {

        function init() {
        }

        function getHomepageList($page) {
            if(!$page) $page = 1;
            $args->page = $page;
            $output = executeQueryArray('homepage.getHomepageList', $args);
            return $output;
        }
    }

?>
