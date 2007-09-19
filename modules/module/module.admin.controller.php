<?php
    /**
     * @class  moduleAdminController
     * @author zero (zero@nzeo.com)
     * @brief  module 모듈의 admin controller class
     **/

    class moduleAdminController extends module {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 모듈 카테고리 추가
         **/
        function procModuleAdminInsertCategory() {
            $args->title = Context::get('title');
            $output = executeQuery('module.insertModuleCategory', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage("success_registed");
        }

        /**
         * @brief 카테고리의 내용 수정
         **/
        function procModuleAdminUpdateCategory() {
            $mode = Context::get('mode');

            switch($mode) {
                case 'delete' :
                        $output = $this->doDeleteModuleCategory();
                        $msg_code = 'success_deleted';
                    break;
                case 'update' :
                        $output = $this->doUpdateModuleCategory();
                        $msg_code = 'success_updated';
                    break;
            }
            if(!$output->toBool()) return $output;

            $this->setMessage($msg_code);
        }

        /**
         * @brief 모듈 카테고리의 제목 변경
         **/
        function doUpdateModuleCategory() {
            $args->title = Context::get('title');
            $args->module_category_srl = Context::get('module_category_srl');
            return executeQuery('module.updateModuleCategory', $args);
        }

        /**
         * @brief 모듈 카테고리 삭제
         **/
        function doDeleteModuleCategory() {
            $args->module_category_srl = Context::get('module_category_srl');
            return executeQuery('module.deleteModuleCategory', $args);
        }

        /**
         * @brief 모듈 복사
         **/
        function procModuleAdminCopyModule() {
            // 복사하려는 대상 모듈의 정보를 구함
            $module_srl = Context::get('module_srl');
            if(!$module_srl) return;

            // 새로 생성하려는 모듈들의 이름/브라우저 제목을 구함
            $clones = array();
            $args = Context::getAll();
            for($i=1;$i<=10;$i++) {
                $mid = $args->{"mid_".$i};
                $browser_title = $args->{"browser_title_".$i};
                if(!$mid) continue;
                if($mid && !$browser_title) $browser_title = $mid;
                $clones[$mid] = $browser_title;
            }
            if(!count($clones)) return;

            // 원 모듈의 정보를 직접 구해옴
            $obj->module_srl = $module_srl;
            $output = executeQuery("module.getMidInfo", $obj);
            $module_info = $output->data;
            unset($module_info->module_srl);
            unset($module_info->regdate);

            $oDB = &DB::getInstance();
            $oDB->begin();

            // 모듈 복사
            foreach($clones as $mid => $browser_title) {
                $clone_args = null;
                $clone_args = clone($module_info);
                $clone_args->module_srl = getNextSequence();
                $clone_args->mid = $mid;
                $clone_args->browser_title = $browser_title;
                $clone_args->is_default = 'N';
                $output = executeQuery('module.insertModule', $clone_args);
            }

            $oDB->commit();
            $this->setMessage('success_registed');
        }

    }
?>
