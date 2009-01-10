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
            $start_date = Context::get('start_date');
            $end_date = Context::get('end_date');

            $oModuleModel = &getModel('module');

            $module_srls = array();
            $rss_config = array();

            // 하나의 mid가 지정되어 있으면 그 mid에 대한 것만 추출
            if($mid) {
                $module_srl = $this->module_info->module_srl;
                $config = $oModuleModel->getModulePartConfig('rss', $module_srl);
                if($config->open_rss && $config->open_rss != 'N') {
                   $module_srls[] = $module_srl; 
                   $rss_config[$module_srl] = $config->open_rss;
                }

            // mid 가 선택되어 있지 않으면 전체
            } else {
                $rss_config = $oModuleModel->getModulePartConfigs('rss');
                if($rss_config) {
                    foreach($rss_config as $module_srl => $config) {
                        if($config && $config->open_rss != 'N') {
                            $module_srls[] = $module_srl;
                            $rss_config[$module_srl] = $config->open_rss;
                        }
                    }
                }
            }

            if(!count($module_srls)) return $this->dispError();

            $args->module_srl = implode(',',$module_srls);
            $module_list = $oModuleModel->getMidList($args);

            $args->search_target = 'is_secret';
            $args->search_keyword = 'N';
            $args->page = (int)Context::get('page');
            $args->list_count = 15;
            if(!$args->page) $args->page = 1;
            if($start_date) $args->start_date = $start_date;
            if($end_date) $args->end_date = $end_date;

            $args->sort_index = 'list_order'; 
            $args->order_type = 'asc';

            // 대상 문서들을 가져옴
            $oDocumentModel = &getModel('document');
            $output = $oDocumentModel->getDocumentList($args);
            $document_list = $output->data;

            // rss 제목 및 정보등을 추출 Context::getBrowserTitle 
            if($mid) {
                $info->title = Context::getBrowserTitle();
                $info->title = str_replace('\'', '&apos;',$info->title);
                $info->description = $this->module_info->description;
                $info->link = getUrl('','mid',$mid);
            } else {
                $site_module_info = Context::get('site_module_info');
                $info->title = $site_module_info->browser_title;
                $info->title = str_replace('\'', '&apos;', htmlspecialchars($info->title));
                $info->link = Context::getRequestUri();
            }
            $info->total_count = $output->total_count;
            $info->total_page = $output->total_page;
            $info->date = date("D, d M Y H:i:s").' '.$GLOBALS['_time_zone'];
            $info->language = Context::getLangType();

            // RSS 출력물에서 사용될 변수 세팅
            Context::set('info', $info);
            Context::set('rss_config', $rss_config);
            Context::set('document_list', $document_list);

            // 결과 출력을 XMLRPC로 강제 지정
            Context::setResponseMethod("XMLRPC");

            // 결과물을 얻어와서 에디터 컴포넌트등의 전처리 기능을 수행시킴
            $path = $this->module_path.'tpl/';
            //if($args->start_date || $args->end_date) $file = 'xe_rss';
            //else $file = 'rss20';
            $file = 'rss20';

            $oTemplate = new TemplateHandler();
            $oContext = &Context::getInstance();

            $content = $oTemplate->compile($path, $file);
            $content = $oContext->transContent($content);
            Context::set('content', $content);

            // 템플릿 파일 지정
            $this->setTemplatePath($path);
            $this->setTemplateFile('display');
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
