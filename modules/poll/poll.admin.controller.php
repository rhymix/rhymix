<?php
    /**
     * @class  pollAdminController
     * @author zero (zero@nzeo.com)
     * @brief  poll모듈의 admin controller class
     **/

    class pollAdminController extends poll {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 설정 저장
         **/
        function procPollAdminInsertConfig() {
            $config->skin = Context::get('skin');
            $config->colorset = Context::get('colorset');

            $oModuleController = &getController('module');
            $oModuleController->insertModuleConfig('poll', $config);

            $this->setMessage('success_updated');
        }

        /**
         * @brief 관리자 페이지에서 선택된 설문조사들을 삭제
         **/
        function procPollAdminDeleteChecked() {
            // 선택된 글이 없으면 오류 표시
            $cart = Context::get('cart');
            if(!$cart) return $this->stop('msg_cart_is_null');

            $poll_srl_list= explode('|@|', $cart);
            $poll_count = count($poll_srl_list);
            if(!$poll_count) return $this->stop('msg_cart_is_null');

            // 글삭제
            for($i=0;$i<$poll_count;$i++) {
                $poll_index_srl = trim($poll_srl_list[$i]);
                if(!$poll_index_srl) continue;

                $output = $this->deletePollTitle($poll_index_srl, true);
                if(!$output->toBool()) return $output;
            }

            $this->setMessage( sprintf(Context::getLang('msg_checked_poll_is_deleted'), $poll_count) );
        }

        /**
         * @brief 설문조사 삭제 (한번에 여러개의 설문 등록시 그 중 하나의 설문만 삭제)
         **/
        function deletePollTitle($poll_index_srl) {
            $args->poll_index_srl = $poll_index_srl;

            $oDB = &DB::getInstance();
            $oDB->begin();

            $output = $oDB->executeQuery('poll.deletePollTitle', $args);
            if(!$output) {
                $oDB->rollback();
                return $output;
            }

            $output = $oDB->executeQuery('poll.deletePollItem', $args);
            if(!$output) {
                $oDB->rollback();
                return $output;
            }

            $oDB->commit();

            return new Object();
        }

        /**
         * @brief 설문조사 삭제 (하나의 묶인 설문조사를 통째로 삭제)
         **/
        function deletePoll($poll_srl) {
            $args->poll_srl = $poll_srl;

            $oDB = &DB::getInstance();
            $oDB->begin();

            $output = $oDB->executeQuery('poll.deletePoll', $args);
            if(!$output) {
                $oDB->rollback();
                return $output;
            }

            $output = $oDB->executeQuery('poll.deletePollTitle', $args);
            if(!$output) {
                $oDB->rollback();
                return $output;
            }

            $output = $oDB->executeQuery('poll.deletePollItem', $args);
            if(!$output) {
                $oDB->rollback();
                return $output;
            }

            $oDB->commit();

            return new Object();
        }
    }
?>
