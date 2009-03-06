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
            // 외부 페이지 모듈의 정보를 구함
            $oOpageModel = &getModel('opage');
            $module_info = $oOpageModel->getOpage($this->module_srl);
            Context::set('module_info', $module_info);

            // 외부 페이지에서 명시된 외부 페이지 경로/ 캐싱 간격을 를 구함
            $path = $module_info->path;
            $caching_interval = $module_info->caching_interval;

            // 캐시 파일 지정
            $cache_file = sprintf("./files/cache/opage/%d.cache.php", $module_info->module_srl);

            // http 인지 내부 파일인지 점검
            if(preg_match("/^([a-z]+):\/\//i",$path)) $content = $this->getHtmlPage($path, $caching_interval, $cache_file);
            else $content = $this->executeFile($path, $caching_interval, $cache_file);

            Context::set('opage_content', $content);

            // 결과 출력 템플릿 지정
            $this->setTemplateFile('content');
        }
        
        /**
         * @brief 외부 http로 요청되는 파일일 경우 파일을 받아와서 저장 후 return
         **/
        function getHtmlPage($path, $caching_interval, $cache_file) {

            // 캐시 검사
            if($caching_interval > 0 && file_exists($cache_file) && filemtime($cache_file) + $caching_interval*60 > time()) {

                $content = FileHandler::readFile($cache_file);

            } else {

                FileHandler::getRemoteFile($path, $cache_file);
                $content = FileHandler::readFile($cache_file);

            }
            
            // opage controller 생성
            $oOpageController = &getController('opage');

            // 외부 서버의 페이지 일 경우 이미지, css, javascript등의 url을 변경
            $content = $oOpageController->replaceSrc($content, $path);

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

            return $content;
        }

        /**
         * @brief 내부 파일일 경우 include하도록 캐시파일을 만들고 처리
         **/
        function executeFile($path, $caching_interval, $cache_file) {
            // 파일이 없으면 취소
            if(!file_exists($path)) return;            

            // 경로와 파일이름을 구함
            $tmp_path = explode('/',$cache_file);
            $filename = $tmp_path[count($tmp_path)-1];
            $filepath = preg_replace('/'.$filename."$/i","",$cache_file);

            // 캐시 검사
            if($caching_interval <1 || !file_exists($cache_file) || filemtime($cache_file) + $caching_interval*60 <= time() || filemtime($cache_file)<filemtime($path) ) {
                if(file_exists($cache_file)) FileHandler::removeFile($cache_file);

                // 일단 대상 파일을 읽어서 내용을 구함
                ob_start();
                @include($path);
                $content = ob_get_clean();

                FileHandler::writeFile($cache_file, $content);

                // include후 결과를 return
                if(!file_exists($cache_file)) return;

                // 컴파일 시도
                $oTemplate = &TemplateHandler::getInstance();
                $script = $oTemplate->compileDirect($filepath, $filename);

                FileHandler::writeFile($cache_file, $script);
            }

            $__Context = &$GLOBALS['__Context__'];
            $__Context->tpl_path = $filepath;
            if($_SESSION['is_logged']) $__Context->logged_info = $_SESSION['logged_info'];

            ob_start();
            @include($cache_file);
            $content = ob_get_clean();

            return $content;
        }

    }
?>
