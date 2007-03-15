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
         * @brief 목록 출력 (관리자용)
         **/
        function dispList() {
            // 목록을 구하기 위한 옵션
            $args->page = Context::get('page'); ///< 페이지
            $args->list_count = 50; ///< 한페이지에 보여줄 글 수
            $args->page_count = 10; ///< 페이지 네비게이션에 나타날 페이지의 수

            $args->sort_index = 'list_order'; ///< 소팅 값

            // 목록 구함, comment->getCommentList 에서 걍 알아서 다 해버리는 구조이다... (아.. 이거 나쁜 버릇인데.. ㅡ.ㅜ 어쩔수 없다)
            $oCommentModel = &getModel('comment');
            $output = $oCommentModel->getTotalCommentList($args);

            // 목록의 loop를 돌면서 mid를 구하기 위한 module_srl값을 구함
            $comment_count = count($output->data);
            if($comment_count) {
                foreach($output->data as $key => $val) {
                    $module_srl = $val->module_srl;
                    if(!in_array($module_srl, $module_srl_list)) $module_srl_list[] = $module_srl;
                }
                if(count($module_srl_list)) {
                    $oDB = &DB::getInstance();
                    $args->module_srls = implode(',',$module_srl_list);
                    $mid_output = $oDB->executeQuery('module.getModuleInfoByModuleSrl', $args);
                    if($mid_output->data && !is_array($mid_output->data)) $mid_output->data = array($mid_output->data);
                    for($i=0;$i<count($mid_output->data);$i++) {
                        $mid_info = $mid_output->data[$i];
                        $module_list[$mid_info->module_srl] = $mid_info;
                    }
                }
            }

            // 템플릿에 쓰기 위해서 comment_model::getTotalCommentList() 의 return object에 있는 값들을 세팅
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('comment_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);
            Context::set('module_list', $module_list);

            // 템플릿 지정
            $this->setTemplatePath($this->module_path.'tpl.admin');
            $this->setTemplateFile('comment_list');
        }

    }
?>
