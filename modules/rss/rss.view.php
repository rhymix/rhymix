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
        function dispRss() {
            // RSS를 출력하고자 하는 mid를 구함 (없으면 오류)
            $mid = Context::get('mid');
            if(!$mid) return $this->dispError();

            // 모듈의 설정 정보를 받아옴 (module model 객체를 이용)
            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByMid($mid);
            if($module_info->mid != $mid) return $this->dispError();

            // RSS 비활성화 되었는지 체크하여 비활성화시 에러 출력
            if($config->open_rss == 'N') return $this->dispError();

            // 출력할 컨텐츠 추출
            $args->module_srl = $module_info->module_srl; 
            $args->page = Context::get('page'); 
            $args->list_count = 20;
            $args->page_count = 10;

            $args->search_target = Context::get('search_target'); 
            $args->search_keyword = Context::get('search_keyword'); 
            if($module_info->use_category=='Y') $args->category_srl = Context::get('category'); 
            $args->sort_index = 'list_order'; 

            $oDocumentModel = &getModel('document');
            $output = $oDocumentModel->getDocumentList($args);
            $document_list = $output->data;

            // rss 제목 및 정보등을 추출
            $info->title = Context::getBrowserTitle();
            $info->description = $this->module_info->description;
            $info->language = Context::getLangType();
            $info->date = gmdate("D, d M Y H:i:s");
            $info->link = sprintf("%s?mid=%s", Context::getRequestUri(), Context::get('mid'));
            $info->total_count = $output->total_count;

            if(count($document_list)) {
                $idx = 0;
                foreach($document_list as $key => $item) {
                    $year = substr($item->regdate,0,4);
                    $month = substr($item->regdate,4,2);
                    $day = substr($item->regdate,6,2);
                    $hour = substr($item->regdate,8,2);
                    $min = substr($item->regdate,10,2);
                    $sec = substr($item->regdate,12,2);
                    $time = mktime($hour,$min,$sec,$month,$day,$year);

                    $item->author = $item->user_name;
                    $item->link = sprintf("%s?document_srl=%d", Context::getRequestUri(), $item->document_srl);

                    // 전문 공개일 경우 
                    if($module_info->open_rss=='Y') {
                        $item->description = $item->content;
                    // 요약 공개일 경우
                    } else {
                        $item->description = cut_str(strip_tags($item->content),100,'...');
                    }
                    $item->date = gmdate("D, d M Y H:i:s", $time);
                    $content[$idx++] = $item;
                }
            }

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
