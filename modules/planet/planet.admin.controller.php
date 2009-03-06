<?php
    /**
     * @class  planetAdminController
     * @author sol (sol@ngleader.com)
     * @brief  planet 모듈의 admin controller class
     **/

    class planetAdminController extends planet {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        function procPlanetAdminInsertConfig() {
            $oModuleController = &getController('module');
            $oModuleModel = &getModel('module');
            $oPlanetModel = &getModel('planet');
            $module_info = $oPlanetModel->getPlanetConfig();

            // 이미 등록된 플래닛의 유무 체크
            $_module_info = $oModuleModel->getModuleInfoByMid($module_info->mid);
            if($module_info->mid && $_module_info) {
                $module_info->module_srl = $_module_info->module_srl;
                $is_registed = true;
            } else {
                $is_registed = false;
            }

            // mid, browser_title, is_default 값이 바뀌면 처리
            $module_info->mid = $args->mid = Context::get('planet_mid');
            $args->browser_title = Context::get('browser_title');
            $args->is_default = Context::get('is_default');
            $args->skin = Context::get('planet_default_skin');
            $args->layout_srl = Context::get('layout_srl');

            $args->module = 'planet';
            $args->module_srl = $is_registed?$module_info->module_srl:getNextSequence();

            if($args->is_default == 'Y') {
                $output = $oModuleController->clearDefaultModule();
                if(!$output->toBool()) return $output;
            }

            if($is_registed) {
                $output = $oModuleController->updateModule($args);
            } else {
                $output = $oModuleController->insertModule($args);
            }
            if(!$output->toBool()) return $output;

            // 그외 정보 처리
            $module_info->planet_default_skin = Context::get('planet_default_skin');
            $module_info->use_mobile = Context::get('use_mobile');
            $module_info->use_me2day = Context::get('use_me2day');

            $tagtab = explode(',',Context::get('planet_tagtab'));
            for($i=0,$c=count($tagtab);$i<$c;$i++){
                if(trim($tagtab[$i])) continue;
                $tagtab[$i] = trim($tagtab[$i]);
            }
            $tagtab = array_unique($tagtab);
            $module_info->tagtab = $tagtab;

            $tagtab_after = explode(',',Context::get('planet_tagtab_after'));
            for($i=0,$c=count($tagtab_after);$i<$c;$i++){
                if(trim($tagtab_after[$i])) continue;
                $tagtab_after[$i] = trim($tagtab_after[$i]);
            }
            $tagtab_after = array_unique($tagtab_after);
            $module_info->tagtab_after = $tagtab_after;


            $smstag = explode(',',Context::get('planet_smstag'));
            for($i=0,$c=count($smstag);$i<$c;$i++){
                if(trim($smstag[$i])) continue;
                $tagtab[$i] = trim($tagtab[$i]);
            }
            $smstag = array_unique($smstag);
            $module_info->smstag = $smstag;


            $module_info->create_message = Context::get('create_message');
            $module_info->use_signup = Context::get('use_signup');
            if($module_info->use_signup != 'Y') $module_info->use_signup = 'N';

            $oPlanetController = &getController('planet');
            $oPlanetController->insertPlanetConfig($module_info);

            $this->setMessage("success_saved");
        }

        function procPlanetAdminInsert() {
            $args = Context::gets('planet_mid','browser_title','description','module_srl');
            $args->mid = $args->planet_mid;
            unset($args->planet_mid);

            if(!$args->module_srl) return new Object(-1,'msg_invalid_request');

            $oPlanetModel = &getModel('planet');
            $oPlanetController = &getController('planet');

            $oPlanet = $oPlanetModel->getPlanet($args->module_srl);
            $planet = $oPlanet->getObjectVars();
            $planet->mid = $args->mid;
            $planet->browser_title = $args->browser_title;
            $planet->description = $args->description;

            $output = $oPlanetController->updatePlanet($planet);

            if(!$output->toBool()) return $output;

            $this->add('module_srl',$output->get('module_srl'));
            $this->setMessage('success_saved');
        }

        function procPlanetAdminDelete() {
            $module_srl = Context::get('module_srl');

            // 원본을 구해온다
            $oModuleController = &getController('module');
            $output = $oModuleController->deleteModule($module_srl);
            if(!$output->toBool()) return $output;

            $args->module_srl = $module_srl;
            executeQuery('planet.deletePlanet', $args);
            executeQuery('planet.deletePlanetFavorites', $args);
            executeQuery('planet.deletePlanetTags', $args);
            executeQuery('planet.deletePlanetVoteLogs', $args);
            executeQuery('planet.deletePlanetMemos', $args);

            $this->add('module','planet');
            $this->add('page',Context::get('page'));
            $this->setMessage('success_deleted');
        }
    }
?>
