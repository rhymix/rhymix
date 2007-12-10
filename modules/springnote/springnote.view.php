<?php
    /**
     * @class  springnoteView
     * @author zero (zero@nzeo.com)
     * @brief  springnote 모듈의 admin view 클래스
     **/

    class springnoteView extends springnote {

        /**
         * @brief 초기화
         **/
        function init() {
            /**
             * 템플릿에서 사용할 변수를 Context::set()
             * 혹시 사용할 수 있는 module_srl 변수를 설정한다.
             **/
            if($this->module_srl) Context::set('module_srl',$this->module_srl);

            /**
             * 현재 방명록 모듈의 정보를 module_info라는 이름으로 템플릿에서 사용할 수 있게 하기 위해 세팅한다
             **/
            Context::set('module_info',$this->module_info);

            /**
             * 모듈정보에서 넘어오는 skin값을 이용하여 최종 출력할 템플릿의 위치를 출력한다.
             * $this->module_path는 ./modules/guestbook/의 값을 가지고 있다
             **/
            $template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
            $this->setTemplatePath($template_path);
        }

        /**
         * @brief 목록 출력 (관리자용)
         **/
        function dispSpringnoteContent() {
            // 권한 체크
            if(!$this->grant->list) return $this->dispSpringnoteMessage('msg_not_permitted');

            $pageid = (int)Context::get('pageid');
            if(!$pageid) $pageid = $this->module_info->pageid;

            $q = Context::get('q');

            $oSpringnoteModel = &getModel('springnote');
            $oSpringnoteModel->setInfo($this->module_info->openid, $this->module_info->userkey);
            
            // 특정 페이지 선택시 페이지 정보 가져오기
            if($this->grant->view && $pageid) {
                $page = $oSpringnoteModel->getPage($pageid);
                for($i=0;$i<count($page->css_files);$i++) {
                    $css_file = $page->css_files[$i];
                    Context::addCssFile($css_file);
                }
            }

            // 페이지 목록 가져오기
            $pages = $oSpringnoteModel->getPages($q, true);

            Context::set('page', $page);
            Context::set('pages', $pages);

            $this->setTemplateFile('list');
        }

        /**
         * @brief 메세지 출력
         **/
        function dispSpringnoteMessage($msg_code) {
            $msg = Context::getLang($msg_code);
            if(!$msg) $msg = $msg_code;
            Context::set('message', $msg);
            $this->setTemplateFile('message');
        }

    }
?>
