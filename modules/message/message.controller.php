<?php
    /**
     * @class  messageController
     * @author zero (zero@nzeo.com)
     * @brief  message module의 view class
     **/

    class messageController extends message {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 설정
         **/
        function procInsertConfig() {
            // 기본 정보를 받음
            $args->skin = Context::get('skin');

            // module Controller 객체 생성하여 입력
            $oModuleController = &getController('module');
            $output = $oModuleController->insertModuleConfig('message',$args);
            return $output;
        }
    }
?>
