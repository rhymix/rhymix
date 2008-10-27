<?php
    /**
     * @class  module
     * @author zero (zero@nzeo.com)
     * @brief  module 모듈의 high class
     **/

    class module extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('module', 'view', 'dispModuleAdminContent');
            $oModuleController->insertActionForward('module', 'view', 'dispModuleAdminList');
            $oModuleController->insertActionForward('module', 'view', 'dispModuleAdminCategory');
            $oModuleController->insertActionForward('module', 'view', 'dispModuleAdminInfo');

            $oDB = &DB::getInstance();
            $oDB->addIndex("module_part_config","idx_module_part_config", array("module","module_srl"));

            // module 모듈에서 사용할 디렉토리 생성
            FileHandler::makeDir('./files/cache/module_info');
            FileHandler::makeDir('./files/cache/triggers');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $oDB = &DB::getInstance();

            // 2008. 10. 27 module_part_config 테이블의 결합 인덱스 추가
            if(!$oDB->isIndexExists("module_part_config","idx_module_part_config")) return true;

            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            $oDB = &DB::getInstance();

            // 2008. 10. 27 module_part_config 테이블의 결합 인덱스 추가하고 기존에 module_config에 몰려 있던 모든 정보를 재점검
            if(!$oDB->isIndexExists("module_part_config","idx_module_part_config")) {
                $oModuleModel = &getModel('module');
                $oModuleController = &getController('module');
                $modules = $oModuleModel->getModuleList();
                foreach($modules as $key => $module_info) {
                    $module = $module_info->module;
                    if(!in_array($module, array('point','trackback','layout','rss','file','comment','editor'))) continue;
                    $config = $oModuleModel->getModuleConfig($module);

                    $module_config = null;
                    switch($module) {
                        case 'point' :
                                $module_config = $config->module_point;
                                unset($config->module_point);
                            break;
                        case 'trackback' :
                        case 'rss' :
                        case 'file' :
                        case 'comment' :
                        case 'editor' :
                                $module_config = $config->module_config;
                                unset($config->module_config);
                                if(is_array($module_config) && count($module_config)) { 
                                    foreach($module_config as $key => $val) {
                                        if(isset($module_config[$key]->module_srl)) unset($module_config[$key]->module_srl);
                                    }
                                }
                            break;
                        case 'layout' :
                                $tmp = $config->header_script;
                                if(is_array($tmp) && count($tmp)) {
                                    foreach($tmp as $k => $v) {
                                        if(!$v && !trim($v)) continue;
                                        $module_config[$k]->header_script = $v;
                                    }
                                }
                                $config = null;
                            break;

                    }

                    $oModuleController->insertModuleConfig($module, $config);

                    if(is_array($module_config) && count($module_config)) {
                        foreach($module_config as $module_srl => $module_part_config) {
                            $oModuleController->insertModulePartConfig($module,$module_srl,$module_part_config);
                        }
                    }
                }
                $oDB->addIndex("module_part_config","idx_module_part_config", array("module","module_srl"));
            }

            return new Object();
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
            // 모듈 정보 캐시 파일 모두 삭제
            FileHandler::removeFilesInDir("./files/cache/module_info");

            // 트리거 정보가 있는 파일 모두 삭제
            FileHandler::removeFilesInDir("./files/cache/triggers");

            // DB캐시 파일을 모두 삭제
            FileHandler::removeFilesInDir("./files/cache/db");

            // 기타 캐시 삭제
            FileHandler::removeFilesInDir("./files/cache/tmp");
        }
    }
?>
