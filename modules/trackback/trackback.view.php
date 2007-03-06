<?php
    /**
     * @class  trackbackView
     * @author zero (zero@nzeo.com)
     * @brief  trackback모듈의 View class
     **/

    class trackbackView extends trackback {

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

            // 목록 구함
            $oTrackbackModel = &getModel('trackback');
            $output = $oTrackbackModel->getTotalTrackbackList($args);

            // 목록의 loop를 돌면서 mid를 구하기 위한 module_srl값을 구함
            $trackback_count = count($output->data);
            if($trackback_count) {
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

            // 템플릿에 쓰기 위해서 변수 설정
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('trackback_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);
            Context::set('module_list', $module_list);

            // 템플릿 지정
            $this->setTemplatePath($this->module_path.'tpl.admin');
            $this->setTemplateFile('trackback_list');
        }

        /**
         * @brief 메세지 출력
         * 메세지를 출력하고 그냥 종료 시켜 버림
         **/
        function dispMessage($error, $message) {
            // 헤더 출력
            header("Content-Type: text/xml; charset=UTF-8");
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            header("Cache-Control: no-store, no-cache, must-revalidate");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");
            print '<?xml version="1.0" encoding="utf-8" ?>'."\n";
            print "<response>\n<error>{$error}</error><message>{$message}</message></response>";
            exit();
        }

    }
?>
