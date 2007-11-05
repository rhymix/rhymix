<?php
    /**
     * @class  tagAdminController
     * @author zero (zero@nzeo.com)
     * @brief  tag 모듈의 admin controller class
     **/

    class tagAdminController extends tag {
        /**
         * @brief 특정 모듈의 태그 전체 삭제
         **/
        function deleteModuleTags($module_srl) {
            $args->module_srl = $module_srl;
            return executeQuery('tag.deleteModuleTags', $args);
        }
    }
?>
