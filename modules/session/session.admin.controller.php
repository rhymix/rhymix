<?php
    /**
     * @class  sessionAdminController
     * @author zero (zero@nzeo.com)
     * @brief  session 모듈의 admin controller class
     **/

    class sessionAdminController extends session {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 더비 세션 정리하는 action
         **/
        function procSessionAdminClear() {
            $oSessionController = &getController('session');
            $oSessionController->gc(0);

            $this->add('result',Context::getLang('session_cleared'));
        }
    }
?>
