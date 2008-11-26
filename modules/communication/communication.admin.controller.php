<?php
    /**
     * @class  communicationAdminController
     * @author zero (zero@nzeo.com)
     * @brief  communication module의 admin controller class
     **/

    class communicationAdminController extends communication {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief communication 모듈 설정 저장
         **/
        function procCommunicationAdminInsertConfig() {
            // 기본 정보를 받음
            $args = Context::gets('skin','colorset','editor_skin','editor_colorset');

            if(!$args->skin) $args->skin = "default";
            if(!$args->colorset) $args->colorset = "white";
            if(!$args->editor_skin) $args->editor_skin = "default";

            // module Controller 객체 생성하여 입력
            $oModuleController = &getController('module');
            $output = $oModuleController->insertModuleConfig('communication',$args);

            return $output;
        }

    }
?>
