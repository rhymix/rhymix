<?php
    /**
     * @class  pagemaker
     * @author zero (zero@nzeo.com)
     * @brief  pagemaker 모듈의 high class
     **/

    class pagemaker extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // plugin 에서 사용할 cache디렉토리 생성
            FileHandler::makeDir('./files/cache/page');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function moduleIsInstalled() {
            return new Object();
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            return new Object();
        }

        /**
         * @brief 관리자에서 요청될때 초기화 할 것들을 정리
         **/
        function initAdmin() {

            // pagemaker 모듈로 등록된 module_srl을 구함
            $oPagemakerModel = &getModel('pagemaker');
            $this->module_srl = $oPagemakerModel->getModuleSrl();


            // 카테고리를 사용하는지 확인후 사용시 카테고리 목록을 구해와서 Context에 세팅
            /*
            if($this->module_info->use_category=='Y') {
                $oDocumentModel = &getModel('document');
                $this->category_list = $oDocumentModel->getCategoryList($this->module_srl);
                Context::set('category_list', $this->category_list);
            }
            */

            // 에디터 세팅
            $editor = "default";
            Context::set('editor', $editor);
            $editor_path = sprintf("./editor/%s/", $editor);
            Context::set('editor_path', $editor_path);
            Context::loadLang($editor_path);

            // 템플릿에서 사용할 변수를 Context::set()
            if($this->module_srl) Context::set('module_srl',$this->module_srl);

            // 업로드 권한 부여
            $this->grant->fileupload = true;
        }

    }
?>
