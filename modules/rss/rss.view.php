<?php
    /**
     * @class  rssView
     * @author zero (zero@nzeo.com)
     * @brief  rss module의 view class
     *
     * RSS 2.0형식으로 문서 출력
     *
     **/

    class rssView extends rss {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief RSS 출력
         **/
        function rss() {
            /**
             * RSS 출력을 위한 변수 설정
             **/
            $mid = Context::get('mid'); ///< 대상 모듈 id, 없으면 전체로

            $page = (int)Context::get('page'); ///< 페이지, 없으면 1
            if(!$page) $page = 1;

            $list_count = (int)Context::get('list_count'); ///< 목록 갯수, 기본 10, 최고 100개
            if(!$list_count|| $list_count>100) $list_count = 10;

            $start_date = Context::get('start_date'); ///< 시작 일자, 없으면 무시
            if(strlen($start_date)!=14 || !ereg("^([0-9]){14}$", $start_date) ) unset($start_date);

            $end_date = Context::get('end_date'); ///< 종료 일자, 없으면 무시
            if(strlen($end_date)!=14 || !ereg("^([0-9]){14}$", $end_date) ) unset($end_date);

            /**
             * 요청된 모듈 혹은 전체 모듈의 정보를 구하고 open_rss의 값을 체크
             **/
            $oModuleModel = &getModel('module');
            $mid_list = array();

            // mid값이 없으면 전체 mid중 open_rss == 'Y|H'인 걸 고름
            if(!$mid) {
                $args->open_rss = "'Y','H'";
                $module_list = $oModuleModel->getMidList($args);
                if(!$module_list) return $this->dispError();

                // 대상 모듈을 정리함
                $module_srl_list = array();
                foreach($module_list as $mid => $val) {
                    $module_srl_list[] = $val->module_srl;
                    $mid_list[$val->module_srl] = $val;
                }
                if(!count($module_srl_list)) return $this->dispError();
                unset($output);
                unset($args);

                $module_srl = implode(',',$module_srl_list);

            // 있으면 해당 모듈의 정보를 구함
            } else {
                // 모듈의 설정 정보를 받아옴 (module model 객체를 이용)
                $module_info = $oModuleModel->getModuleInfoByMid($mid);
                if($module_info->mid != $mid) return $this->dispError();

                // RSS 비활성화 되었는지 체크하여 비활성화시 에러 출력
                if($module_info->open_rss == 'N') return $this->dispError();

                $module_srl = $module_info->module_srl;
                $mid_list[$module_info->module_srl] = $module_info;

                unset($args);
            }

            /**
             * 출력할 컨텐츠 추출을 위한 인자 정리
             **/
            $args->module_srl = $module_srl; 
            $args->page = $page;
            $args->list_count = $list_count;
            $args->page_count = 10;
            if($start_date) $args->start_date = $start_date;
            if($end_date) $args->end_date = $end_date;

            $args->sort_index = 'update_order'; 
            $args->order_type = 'asc';

            // 대상 문서들을 가져옴
            $oDocumentModel = &getModel('document');
            $output = $oDocumentModel->getDocumentList($args);
            $document_list = $output->data;

            // rss 제목 및 정보등을 추출
            if($this->mid) {
                $info->title = Context::getBrowserTitle();
                $info->description = $this->module_info->description;
                $info->link = getUrl('','mid',Context::get('mid'));
            } else {
                $info->title = $info->link = Context::getRequestUri();
            }
            $info->total_count = $output->total_count;
            $info->total_page = $output->total_page;
            $info->date = date("D, d M Y H:i:s").' '.$GLOBALS['_time_zone'];
            $info->language = Context::getLangType();

            // RSS 출력물에서 사용될 변수 세팅
            Context::set('info', $info);
            Context::set('mid_list', $mid_list);
            Context::set('document_list', $document_list);

            // 결과 출력을 XMLRPC로 강제 지정
            Context::setResponseMethod("XMLRPC");

            // 템플릿 파일 지정
            $this->setTemplatePath($this->module_path.'tpl/');

            if($args->start_date || $args->end_date) $this->setTemplateFile('xe_rss');
            else $this->setTemplateFile('rss20');
        }

        /**
         * @brief 에러 출력
         **/
        function dispError() {

            // 결과 출력을 XMLRPC로 강제 지정
            Context::setResponseMethod("XMLRPC");

            // 출력 메세지 작성
            Context::set('error', -1);
            Context::set('message', Context::getLang('msg_rss_is_disabled') );

            // 템플릿 파일 지정
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile("error");
        }
    }
?>
