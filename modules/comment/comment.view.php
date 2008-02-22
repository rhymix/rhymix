<?php
    /**
     * @class  commentView
     * @author zero (zero@nzeo.com)
     * @brief  comment 모듈의 view 클래스
     **/

    class commentView extends comment {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 모듈의 추가 설정에서 댓글 설정을 하는 form 추가
         **/
        function triggerDispCommentAdditionSetup(&$obj) {
            $current_module_srl = Context::get('module_srl');
            $current_module_srls = Context::get('module_srls');

            if(!$current_module_srl && !$current_module_srls) {
                // 선택된 모듈의 정보를 가져옴
                $current_module_info = Context::get('current_module_info');
                $current_module_srl = $current_module_info->module_srl;
                if(!$current_module_srl) return new Object();
            }

            // 댓글 설정을 구함
            $oCommentModel = &getModel('comment');
            $comment_config = $oCommentModel->getCommentConfig($current_module_srl);
            Context::set('comment_config', $comment_config);

            // 그룹 목록을 구함
            $oMemberModel = &getModel('member');
            $group_list = $oMemberModel->getGroups();
            Context::set('group_list', $group_list);

            // 템플릿 파일 지정
            $oTemplate = &TemplateHandler::getInstance();
            $tpl = $oTemplate->compile($this->module_path.'tpl', 'comment_module_config');
            $obj .= $tpl;

            return new Object();
        }
    }
?>
