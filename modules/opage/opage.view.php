<?php
    /**
     * @class  opageView
     * @author zero (zero@nzeo.com)
     * @brief  opage 모듈의 view 클래스
     **/

    class opageView extends opage {

        /**
         * @brief 초기화
         **/
        function init() {
            // 템플릿 경로 구함 (opage의 경우 tpl에 관리자용 템플릿 모아놓음)
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * @brief 일반 요청시 출력
         **/
        function dispOpageIndex() {
            // 권한 체크
            if(!$this->grant->view) return $this->stop('msg_not_permitted'); 
            
            // opage controller 생성
            $oOpageController = &getController('opage');

            // 외부 페이지 모듈의 정보를 구함
            $oOpageModel = &getModel('opage');
            $module_info = $oOpageModel->getOpage($this->module_srl);
            Context::set('module_info', $module_info);

            // 외부 페이지에서 명시된 외부 페이지 경로/ 캐싱 간격을 를 구함
            $path = $module_info->path;
            $caching_interval = $module_info->caching_interval;

            // 캐시 파일 지정
            $cache_file = sprintf("./files/cache/opage/%d.cache", $module_info->module_srl);

            // 캐시 검사
            if($caching_interval > 0 && file_exists($cache_file) && filemtime($cache_file) + $caching_interval*60 > time()) {

                $content = FileHandler::readFile($cache_file);

            } else {

                // 경로에 http://가 있는 경우와 없는 경우를 비교
                if(eregi("^http:\/\/",$path)) {
                    FileHandler::getRemoteFile($path, $cache_file);
                    $content = FileHandler::readFile($cache_file);

                // 서버 내부에 있는 경우
                } elseif(file_exists($path)) {
                    ob_start();
                    @include($path);
                    $content = ob_get_contents();
                    ob_end_clean();
                    FileHandler::writeFile($cache_file, $content);
                }

            }

            // 외부 서버의 페이지 일 경우 이미지, css, javascript등의 url을 변경
            if(eregi("^http:\/\/",$path)) {
                $content = $oOpageController->replaceSrc($content, $path);
            }

            // 해당 문서를 utf-8로 변경
            $buff->content = $content;
            $buff = Context::convertEncoding($buff);
            $content = $buff->content;

            // title 추출
            $title = $oOpageController->getTitle($content);
            if($title) Context::setBrowserTitle($title);

            // header script 추출
            $head_script = $oOpageController->getHeadScript($content);
            if($head_script) Context::addHtmlHeader($head_script);

            // body 내용 추출
            $body_script = $oOpageController->getBodyScript($content);
            if(!$body_script) $body_script = $content;

            Context::set('opage_content', $body_script);

            // 결과 출력 템플릿 지정
            $this->setTemplateFile('content');
        }

    }
?>
