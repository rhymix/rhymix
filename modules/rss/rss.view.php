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
            // RSS를 출력하고자 하는 mid를 구함 (없으면 오류)
            $mid = Context::get('mid');

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

            // 변수 설정
            $page = (int)Context::get('page');
            if(!$page) $page = 1;
            $list_count = (int)Context::get('list_count');
            if(!$list_count|| $list_count>100) $list_count = 100;

            // 출력할 컨텐츠 추출을 위한 인자 정리
            $args->module_srl = $module_srl; 
            $args->page = $page;
            $args->list_count = $list_count;
            $args->page_count = 10;
            $args->search_target = Context::get('search_target'); 
            $args->search_keyword = Context::get('search_keyword'); 
            if($module_info->use_category=='Y') $args->category_srl = Context::get('category'); 
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
            $info->date = gmdate("D, d M Y H:i:s");
            $info->language = Context::getLangType();

            // rss2.0으로 출력
            if(count($document_list)) {
                $idx = 0;
                foreach($document_list as $key => $item) {
                    $year = substr($item->get('regdate'),0,4);
                    $month = substr($item->get('regdate'),4,2);
                    $day = substr($item->get('regdate'),6,2);
                    $hour = substr($item->get('regdate'),8,2);
                    $min = substr($item->get('regdate'),10,2);
                    $sec = substr($item->get('regdate'),12,2);
                    $time = mktime($hour,$min,$sec,$month,$day,$year);

                    $item->author = $item->getNickName();
                    $item->link = $item->getPermanentUrl();
                    $item->title = $item->getTitleText();

                    $module_srl = $item->get('module_srl');

                    // 전문 공개일 경우 
                    if($mid_list[$module_srl]->open_rss=='Y') {
                        $item->description = $item->getContent();
                    // 요약 공개일 경우
                    } else {
                        $item->description = cut_str(strip_tags($item->getContent()),100,'...');
                    }
                    $item->date = gmdate("D, d M Y H:i:s", $time);
                    $content[$idx++] = $item;
                }
            } else return $this->dispError();

            // RSS 출력물에서 사용될 변수 세팅
            Context::set('info', $info);
            Context::set('content', $content);

            // 결과 출력을 XMLRPC로 강제 지정
            Context::setResponseMethod("XMLRPC");

            // 템플릿 파일 지정
            $this->setTemplatePath($this->module_path.'tpl/');
            $this->setTemplateFile('rss20');
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
