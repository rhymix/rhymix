<?php
    /**
     * @class  sessionController
     * @author zero (zero@nzeo.com)
     * @brief  session 모듈의 controller class
     **/

    class sessionController extends session {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        function open() {
            return true;
        }

        function close() {
            return true;
        }

        function write($session_key, $val) {
            if(!$session_key || !$this->session_started) return;

            $args->session_key = $session_key;
            $output = executeQuery('session.getSession', $args);
            $session_info = $output->data;
            if($session_info->session_key == $session_key && $session_info->ipaddress != $_SERVER['REMOTE_ADDR']) {
                executeQuery('session.deleteSession', $args);
                return true;
            }

            $args->expired = date("YmdHis", time()+$this->lifetime);
            $args->val = $val;
            $args->cur_mid = Context::get('mid');
            if(!$args->cur_mid) {
                $module_info = Context::get('current_module_info');
                $args->cur_mid = $module_info->mid;
            }

            if(Context::get('is_logged')) {
                $logged_info = Context::get('logged_info');
                $args->member_srl = $logged_info->member_srl;
            } else {
                $args->member_srl = 0;
            }

            if($session_info->session_key) $output = executeQuery('session.updateSession', $args);
            else $output = executeQuery('session.insertSession', $args);

            return true;
        }

        function destroy($session_key) {
            if(!$session_key || !$this->session_started) return;

            $args->session_key = $session_key;
            executeQuery('session.deleteSession', $args);
            return true;
        }

        function gc($maxlifetime) {
            if(!$this->session_started) return;
            executeQuery('session.gcSession');
            return true;
        }
    }
?>
