<?php
    /**
     * @class  trackbackAdminController
     * @author zero (zero@nzeo.com)
     * @brief  trackback모듈의 admin controller class
     **/

    class trackbackAdminController extends trackback {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 관리자 페이지에서 선택된 엮인글들을 삭제
         **/
        function procTrackbackAdminDeleteChecked() {
            // 선택된 글이 없으면 오류 표시
            $cart = Context::get('cart');
            if(!$cart) return $this->stop('msg_cart_is_null');
            $trackback_srl_list= explode('|@|', $cart);
            $trackback_count = count($trackback_srl_list);
            if(!$trackback_count) return $this->stop('msg_cart_is_null');

            $oTrackbackController = &getController('trackback');

            // 글삭제
            for($i=0;$i<$trackback_count;$i++) {
                $trackback_srl = trim($trackback_srl_list[$i]);
                if(!$trackback_srl) continue;

                $oTrackbackController->deleteTrackback($trackback_srl, true);
            }

            $this->setMessage( sprintf(Context::getLang('msg_checked_trackback_is_deleted'), $trackback_count) );
        }

        /**
         * @brief 모듈에 속한 모든 트랙백 삭제
         **/
        function deleteModuleTrackbacks($module_srl) {
            // 삭제
            $args->module_srl = $module_srl;
            $output = executeQuery('trackback.deleteModuleTrackbacks', $args);

            return $output;
        }

    }
?>
