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
         * @brief 설정 
         **/
        function dispConfig() {
            // 설정 정보를 받아옴 (module model 객체를 이용)
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('rss');
            Context::set('config',$config);
            Context::set('rss_types',$this->rss_types);

            // 템플릿 파일 지정
            $this->setTemplatePath($this->module_path.'tpl.admin/');
            $this->setTemplateFile('index');
        }

        /**
         * @brief RSS 출력
         **/
        function dispRss($info, $content) {
            // 설정 정보를 받아옴 (module model 객체를 이용)
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('rss');

            // RSS 비활성화 되었는지 체크하여 비활성화시 에러 출력
            if($config->rss_disable=='Y') return $this->dispError();

            // RSS 출력 형식을 체크
            $rss_type = $config->rss_type;
            if(!$this->rss_types->{$rss_type}) $rss_type = $this->default_rss_type;

            if(count($content)) {
                foreach($content as $key => $item) {
                    $year = substr($item->regdate,0,4);
                    $month = substr($item->regdate,4,2);
                    $day = substr($item->regdate,6,2);
                    $hour = substr($item->regdate,8,2);
                    $min = substr($item->regdate,10,2);
                    $sec = substr($item->regdate,12,2);
                    $time = mktime($hour,$min,$sec,$month,$day,$year);

                    $item->author = $item->user_name;
                    $item->link = sprintf("%s?document_srl=%d", Context::getRequestUri(), $item->document_srl);
                    $item->description = $item->content;
                    $item->date = gmdate("D, d M Y H:i:s", $time);
                    $content[$key] = $item;
                }
            }

            // RSS 출력물에서 사용될 변수 세팅
            Context::set('info', $info);
            Context::set('content', $content);

            // 결과 출력을 XMLRPC로 강제 지정
            Context::setResponseMethod("XMLRPC");

            // 템플릿 파일 지정
            $this->setTemplatePath($this->module_path.'tpl/');
            $this->setTemplateFile($rss_type);
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
            $this->setTemplatePath($this->module_path.'tpl.admin/');
            $this->setTemplateFile("error");
        }
    }
?>
