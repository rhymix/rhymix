<?php
    /**
     * @class  pageView
     * @author NHN (developers@xpressengine.com)
     * @brief  page 모듈의 view 클래스
     **/

    class pageView extends page {

        var $module_srl = 0;
        var $list_count = 20;
        var $page_count = 10;

        /**
         * @brief 초기화
         **/
        function init() {
            // 템플릿 경로 구함 (page의 경우 tpl에 관리자용 템플릿 모아놓음)
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * @brief 일반 요청시 출력
         **/
        function dispPageIndex() {
            // 템플릿에서 사용할 변수를 Context::set()
            if($this->module_srl) Context::set('module_srl',$this->module_srl);

            // 캐시 파일 지정
            $cache_file = sprintf("%sfiles/cache/page/%d.%s.cache.php", _XE_PATH_, $this->module_info->module_srl, Context::getLangType());
            $interval = (int)($this->module_info->page_caching_interval);
            if($interval>0) {
                if(!file_exists($cache_file)) $mtime = 0;
                else $mtime = filemtime($cache_file);

                if($mtime + $interval*60 > time()) {
                    $page_content = FileHandler::readFile($cache_file); 
                } else {
                    $oWidgetController = &getController('widget');
                    $page_content = $oWidgetController->transWidgetCode($this->module_info->content);
                    FileHandler::writeFile($cache_file, $page_content);
                }
            } else {
                if(file_exists($cache_file)) FileHandler::removeFile($cache_file);
                $page_content = $this->module_info->content;
            }
            
            Context::set('module_info', $this->module_info);
            Context::set('page_content', $page_content);

            $this->setTemplateFile('content');
        }
    }
?>
