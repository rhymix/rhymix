<?php
    /**
     * @class  wikiAdminController
     * @author zero (zero@nzeo.com)
     * @brief  wiki 모듈의 admin controller class
     **/

    class wikiAdminController extends wiki {
        /**
         * @brief 초기화
         **/
        function init() {
        }

        function procWikiAdminInsertWiki($args = null) {
            // module 모듈의 model/controller 객체 생성
            $oModuleController = &getController('module');
            $oModuleModel = &getModel('module');

            $args = Context::getRequestVars();
            $args->module = 'wiki';
            $args->mid = $args->wiki_name;
            unset($args->wiki_name);
            if($args->module_srl) {
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
                if($module_info->module_srl != $args->module_srl) unset($args->module_srl);
            }

            if(!$args->module_srl) {
                $output = $oModuleController->insertModule($args);
                $msg_code = 'success_registed';
            } else {
                $output = $oModuleController->updateModule($args);
                $msg_code = 'success_updated';
            }

            if(!$output->toBool()) return $output;

            $this->add('page',Context::get('page'));
            $this->add('module_srl',$output->get('module_srl'));
            $this->setMessage($msg_code);
        }

        function procWikiAdminDeleteWiki() {
            $module_srl = Context::get('module_srl');

            // 원본을 구해온다
            $oModuleController = &getController('module');
            $output = $oModuleController->deleteModule($module_srl);
            if(!$output->toBool()) return $output;

            $this->add('module','wiki');
            $this->add('page',Context::get('page'));
            $this->setMessage('success_deleted');
        }

    }
?>
