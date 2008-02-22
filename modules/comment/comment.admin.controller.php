<?php
    /**
     * @class  commentAdminController
     * @author zero (zero@nzeo.com)
     * @brief  comment 모듈의 admin controller class
     **/

    class commentAdminController extends comment {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 관리자 페이지에서 선택된 댓글들을 삭제
         **/
        function procCommentAdminDeleteChecked() {

            // 선택된 글이 없으면 오류 표시
            $cart = Context::get('cart');
            if(!$cart) return $this->stop('msg_cart_is_null');
            $comment_srl_list= explode('|@|', $cart);
            $comment_count = count($comment_srl_list);
            if(!$comment_count) return $this->stop('msg_cart_is_null');

            $oCommentController = &getController('comment');

            $deleted_count = 0;

            // 글삭제
            for($i=0;$i<$comment_count;$i++) {
                $comment_srl = trim($comment_srl_list[$i]);
                if(!$comment_srl) continue;

                $output = $oCommentController->deleteComment($comment_srl, true);
                if(!$output->toBool()) continue;

                $deleted_count ++;
            }

            $this->setMessage( sprintf(Context::getLang('msg_checked_comment_is_deleted'), $deleted_count) );
        }

        /**
         * @brief 신고대상을 취소 시킴
         **/
        function procCommentAdminCancelDeclare() {
            $comment_srl = trim(Context::get('comment_srl'));

            if($comment_srl) {
                $args->comment_srl = $comment_srl;
                $output = executeQuery('comment.deleteDeclaredComments', $args);
                if(!$output->toBool()) return $output;
            }
        }

        /**
         * @brief 특정 모듈의 모든 댓글 삭제
         **/
        function deleteModuleComments($module_srl) {
            $args->module_srl = $module_srl;
            $output = executeQuery('comment.deleteModuleComments', $args);
            if(!$output->toBool()) return $output;

            $output = executeQuery('comment.deleteModuleCommentsList', $args);
            return $output;
        }


        /**
         * @brief 댓글의 모듈별 추가 확장 폼을 저장
         **/
        function procCommentAdminInsertModuleConfig() {
            // 기존 설정을 가져옴 
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('comment');

            // 대상을 구함
            $module_srl = Context::get('target_module_srl');

            // 여러개의 모듈 일괄 설정일 경우
            if(preg_match('/^([0-9,]+)$/',$module_srl)) $module_srl = explode(',',$module_srl);
            else $module_srl = array($module_srl);

            $comment_config = null;

            $comment_config->comment_count = (int)Context::get('comment_count');
            if(!$comment_config->comment_count) $comment_config->comment_count = 50;

            for($i=0;$i<count($module_srl);$i++) {
                $srl = trim($module_srl[$i]);
                if(!$srl) continue;
                $config->module_config[$srl] = $comment_config;
            }

            // module Controller 객체 생성하여 입력
            $oModuleController = &getController('module');
            $output = $oModuleController->insertModuleConfig('comment',$config);

            $this->setError(-1);
            $this->setMessage('success_updated');
        }


    }
?>
