<?php
    /**
     * @class  session
     * @author zero (zero@nzeo.com)
     * @brief  session 모듈의 high class
     * @version 0.1
     *
     * session 관리를 하는 class
     **/

    class session extends ModuleObject {

        var $lifetime = 18000;
        var $session_started = false;

        function session() {
            if(Context::isInstalled()) $this->session_started= true;
        }

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('session', 'view', 'dispSessionAdminIndex');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $oDB = &DB::getInstance();
            $oModuleModel = &getModel('module');

            if(!$oModuleModel->getActionForward('dispSessionAdminIndex')) return true;

            if(!$oDB->isTableExists('session')) return true;

            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            $oDB = &DB::getInstance();
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');
            
            if(!$oDB->isTableExists('session')) $oDB->createTableByXmlFile($this->module_path.'schemas/session.xml');

            if(!$oModuleModel->getActionForward('dispSessionAdminIndex')) 
                $oModuleController->insertActionForward('document', 'view', 'dispSessionAdminIndex');
        }

        /**
         * @brief session string decode
         **/
        function unSerializeSession($val) {
            $vars = preg_split('/([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff^|]*)\|/', $val,-1,PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
            for($i=0; $vars[$i]; $i++) $result[$vars[$i++]] = unserialize($vars[$i]);
            return $result;
        }

        /**
         * @brief session string encode
         **/
        function serializeSession($data) {
            if(!count($data)) return;

            $str = '';
            foreach($data as $key => $val) $str .= $key.'|'.serialize($val);
            return substr($str, 0, strlen($str)-1).'}';
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
            // 기존 파일 기반의 세션 삭제
            FileHandler::removeDir(_XE_PATH_."files/sessions");
        }
    }
?>
