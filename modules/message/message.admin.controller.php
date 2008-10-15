<?php
    /**
     * @class  messageAdminController
     * @author zero (zero@nzeo.com)
     * @brief  message module의 admin controller class
     **/

    class messageAdminController extends message {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 설정
         **/
        function procMessageAdminInsertConfig() {
            // 기본 정보를 받음
            $args->skin = Context::get('skin');

            // module Controller 객체 생성하여 입력
            $oModuleController = &getController('module');
            $output = $oModuleController->insertModuleConfig('message',$args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_updated');
        }
    }
?>
