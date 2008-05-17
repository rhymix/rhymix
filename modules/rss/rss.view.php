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

            // rss module config를 가져옴
            $oModuleModel = &getModel('module');
            $rss_config = $oModuleModel->getModuleConfig('rss');

            /**
             * 요청된 모듈 혹은 전체 모듈의 정보를 구하고 open_rss의 값을 체크
             **/
            $mid_list = array();

            // mid값이 없으면 전체 mid중 open_rss == 'Y|H'인 걸 고름
            if(!$mid) {

                $module_srl_list = null;

                // rss config에 등록된 모듈중 rss 공개하는 것들의 module_srl을 고름
                if($rss_config->module_config && count($rss_config->module_config)) {
                    foreach($rss_config->module_config as $key => $val) {
                        if($val->open_rss == 'N') continue;
                        $module_srl_list[] = $val->module_srl;
                    }
                }
                // 선택된 모듈이 없으면 패스
                if(!$module_srl_list || !count($module_srl_list)) return $this->dispError();

                // 선택된 모듈들을 정리
                $args->module_srls = implode(',',$module_srl_list);
                $module_list = $oModuleModel->getMidList($args);
                if(!$module_list) return $this->dispError();

                // 대상 모듈을 정리함
                $module_srl_list = array();
                foreach($module_list as $mid => $val) {
                    $val->open_rss = $rss_config->module_config[$val->module_srl]->open_rss;
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

                // 해당 모듈이 rss를 사용하는지 확인
                $rss_module_config = $rss_config->module_config[$module_info->module_srl];

                // RSS 비활성화 되었는지 체크하여 비활성화시 에러 출력
                if($rss_module_config->open_rss == 'N') return $this->dispError();

                $module_srl = $module_info->module_srl;
                $module_info->open_rss = $rss_module_config->open_rss;
                $mid_list[$module_info->module_srl] = $module_info;

                unset($args);
            }

            /**
             * 출력할 컨텐츠 추출을 위한 인자 정리
             **/
            $args->module_srl = $module_srl; 
            $args->search_target = 'is_secret';
            $args->search_keyword = 'N';
            $args->page = 1;
            $args->list_count = 15;
            if($start_date) $args->start_date = $start_date;
            if($end_date) $args->end_date = $end_date;

            $args->sort_index = 'list_order'; 
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

        /**
         * @brief 서비스형 모듈의 추가 설정을 위한 부분
         * rss의 사용 형태에 대한 설정만 받음
         **/
        function triggerDispRssAdditionSetup(&$obj) {
            $current_module_srl = Context::get('module_srl');
            $current_module_srls = Context::get('module_srls');

            if(!$current_module_srl && !$current_module_srls) {
                // 선택된 모듈의 정보를 가져옴
                $current_module_info = Context::get('current_module_info');
                $current_module_srl = $current_module_info->module_srl;
                if(!$current_module_srl) return new Object();
            }

            // 선택된 모듈의 rss설정을 가져옴
            $oRssModel = &getModel('rss');
            $rss_config = $oRssModel->getRssModuleConfig($current_module_srl);
            Context::set('rss_config', $rss_config);

            // 템플릿 파일 지정
            $oTemplate = &TemplateHandler::getInstance();
            $tpl = $oTemplate->compile($this->module_path.'tpl', 'rss_module_config');
            $obj .= $tpl;

            return new Object();
        }
    }
?>
