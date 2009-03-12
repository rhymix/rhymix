<?php
    /**
     * @class  issuetrackerAdminController
     * @author zero (zero@nzeo.com)
     * @brief  issuetracker 모듈의 admin controller class
     **/

    class issuetrackerAdminController extends issuetracker {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        function procIssuetrackerAdminInsertProject() {
            // module 모듈의 model/controller 객체 생성
            $oModuleController = &getController('module');
            $oModuleModel = &getModel('module');

            // 일단 입력된 값들을 모두 받아서 db 입력항목과 그외 것으로 분리
            $args = Context::getRequestVars();
            $args->module = 'issuetracker';
            $args->mid = $args->project_name;
            unset($args->project_name);

            // module_srl이 넘어오면 원 모듈이 있는지 확인
            if($args->module_srl) {
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
                if($module_info->module_srl != $args->module_srl) unset($args->module_srl);
            }

            // module_srl의 값에 따라 insert/update
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

        /**
         * @brief 프로젝트 삭제
         **/
        function procIssuetrackerAdminDeleteIssuetracker() {
            $module_srl = Context::get('module_srl');

            // 원본을 구해온다
            $oModuleController = &getController('module');
            $output = $oModuleController->deleteModule($module_srl);
            if(!$output->toBool()) return $output;

            $args->module_srl = $module_srl;
            $output = executeQuery('issue.deleteMilestones', $args);
            $output = executeQuery('issue.deleteTypes', $args);
            $output = executeQuery('issue.deletePriorities', $args);
            $output = executeQuery('issue.deleteComponents', $args);

            $this->add('module','issuetracker');
            $this->add('page',Context::get('page'));
            $this->setMessage('success_deleted');
        }


        function procIssuetrackerAdminInsertMilestone()
        {
            $args = Context::getRequestVars();
            if($args->is_default=='Y') executeQuery('issuetracker.clearMilestoneDefault', $args);

            if(!$args->milestone_srl)
            {
                $args->milestone_srl = getNextSequence();
                executeQuery("issuetracker.insertMilestone", $args);
            }
            else
            {
                executeQuery("issuetracker.updateMilestone", $args);
            }
        }

        function procIssuetrackerAdminInsertType()
        {
            $args = Context::getRequestVars();
            if($args->is_default=='Y') executeQuery('issuetracker.clearTypeDefault', $args);

            if($args->type_srl) {
                $output = executeQuery("issuetracker.updateType", $args);

            } else {
                $args->type_srl = getNextSequence();
                executeQuery("issuetracker.insertType", $args);
            }
        }

        function procIssuetrackerAdminInsertComponent()
        {
            $args = Context::getRequestVars();
            
            if($args->is_default=='Y') executeQuery('issuetracker.clearComponentsDefault', $args);

            if($args->component_srl) {
                $output = executeQuery("issuetracker.updateComponent", $args);

            } else {

                $args->component_srl = getNextSequence();
                $output = executeQuery("issuetracker.insertComponent", $args);
            }
        }

        function procIssuetrackerAdminModifyDisplayOption()
        {
            $args = Context::getRequestVars();
            
            $oModuleController = &getController('module');
            $module_config->display_option = explode('|@|', $args->displayopts);
            $oModuleController->insertModulePartConfig('issuetracker',$args->module_srl,$module_config);
        }
        
        function procIssuetrackerAdminInsertPriority()
        {
            $args = Context::getRequestVars();
            if($args->is_default=='Y') executeQuery('issuetracker.clearPrioritiesDefault',$args);

            if($args->priority_srl) {
                $output = executeQuery("issuetracker.updatePriority", $args);

            } else {
                $oIssuetrackerModel = &getModel('issuetracker');
                $listorder = $oIssuetrackerModel->getPriorityMaxListorder($args->module_srl);
                if($listorder<0) return;
                $args->priority_srl = getNextSequence();
                $args->listorder = $listorder+ 1;
                $output = executeQuery("issuetracker.insertPriority", $args);
            }
        }

        function procIssuetrackerAdminDeleteMilestone()
        {
            $args = Context::getRequestVars();
            $output = executeQuery("issuetracker.deleteMilestone", $args);
            $this->setMessage('success_deleted');
        }

        function procIssuetrackerAdminDeletePriority() 
        {
            $args = Context::getRequestVars();
            $output = executeQuery("issuetracker.deletePriority", $args);
            $this->setMessage('success_deleted');
        }

        function procIssuetrackerAdminDeleteType() 
        {
            $args = Context::getRequestVars();
            $output = executeQuery("issuetracker.deleteType", $args);
            $this->setMessage('success_deleted');
        }

        function procIssuetrackerAdminDeleteComponent()  
        {
            $args = Context::getRequestVars();
            $output = executeQuery("issuetracker.deleteComponent", $args);
            $this->setMessage('success_deleted');
        }

        function procIssuetrackerAdminInsertPackage()
        {
            $args = Context::getRequestVars();

            if(!$args->package_srl)
            {
                $args->package_srl = getNextSequence();
                executeQuery("issuetracker.insertPackage", $args);
            }
            else
            {
                executeQuery("issuetracker.updatePackage", $args);
            }
        }

        function procIssuetrackerAdminInsertRelease()
        {
            $args = Context::getRequestVars();

            if(!$args->release_srl)
            {
                $args->release_srl = getNextSequence();
                executeQuery("issuetracker.insertRelease", $args);
            }
            else
            {
                executeQuery("issuetracker.updateRelease", $args);
            }
        }

        function procIssuetrackerAdminDeletePackage()
        {
            $args = Context::getRequestVars();
            $package_srl = $args->package_srl;
            if(!$package_srl) return new Object(-1, 'msg_invalid_request');

            $oIssuetrackerModel= &getModel('issuetracker');
            $release_list = $oIssuetrackerModel->getReleaseList($package_srl);

            $output = executeQuery("issuetracker.deletePackage", $args);
            if(!$output->toBool()) return $output;

            if(!count($release_list)) return;

            foreach($release_list as $release_srl => $release) {
                $this->deleteRelease($release_srl);
            }
        }


        function procIssuetrackerAdminDeleteRelease()
        {
            $release_srl = Context::get('release_srl');
            $this->deleteRelease($release_srl);
            $this->setMessage('success_deleted');
        }

        function deleteRelease($release_srl) {
            $args->release_srl = $release_srl;
            $output = executeQuery("issuetracker.deleteRelease", $args);
            if(!$output->toBool()) return $output;

            $oFileController = &getController('file');
            $oFileController->deleteFiles($args->release_srl);
        }

        function procIssuetrackerAdminAttachRelease() {
            $module_srl = Context::get('module_srl');
            $module = Context::get('module');
            $mid = Context::get('mid');
            $release_srl = Context::get('release_srl');
            $package_srl = Context::get('package_srl');
            $comment = Context::get('comment');
            $file_info = Context::get('file');

            if(!Context::isUploaded() || !$module_srl || !$release_srl) {
                $msg = Context::getLang('msg_invalid_request');    
            } else if(!is_uploaded_file($file_info['tmp_name'])) {
                $msg = Context::getLang('msg_not_attached');
            } else {
                $oFileController = &getController('file');
                $output = $oFileController->insertFile($file_info, $module_srl, $release_srl, 0);
                $msg = Context::getLang('msg_attached');
                $oFileController->setFilesValid($release_srl);
                $file_srl = $output->get('file_srl');
                Context::set('file_srl', $file_srl);

                if($comment) {
                    $comment_args->file_srl = $file_srl;
                    $comment_args->comment = $comment;
                    executeQuery('issuetracker.updateReleaseFile', $comment_args);
                }
            }
            Context::set('msg', $msg);
            Context::set('layout','none');
            $this->setTemplatePath(sprintf("%stpl/",$this->module_path));
            $this->setTemplateFile('attached');
        }

        function procIssuetrackerAdminDeleteFile()
        {
            $file_srl = Context::get('file_srl');
            if(!$file_srl) return new Object(-1, 'msg_invalid_request');

            $oFileController = &getController('file');
            return $oFileController->deleteFile($file_srl);
        }

        function procIssuetrackerAdminManageCheckedIssue() {
            $module_srl = Context::get('module_srl');
            $cart = Context::get('cart');
            if($cart) $document_srl_list = explode('|@|', $cart);
            else $document_srl_list = array();

            $document_srl_count = count($document_srl_list);
            $objs = Context::gets('priority_srl', 'component_srl', 'type_srl', 'milestone_srl');
            $oController = &getController('issuetracker');
            foreach($document_srl_list as $target_srl)
            {
                $output = $oController->insertHistory($target_srl, $objs, $module_srl, true);
                if(!$output->toBool())
                {
                    return $output;
                }
            }

            $_SESSION['document_management'] = array();

            $this->setMessage('success_updated');
        }




    }
?>
