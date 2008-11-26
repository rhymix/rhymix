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
            $config = $oPlanetModel->getPlanetConfig();

            // mid, browser_title, is_default 값이 바뀌면 처리
            $config->mid = $args->mid = Context::get('planet_mid');
            $args->browser_title = Context::get('browser_title');
            $args->is_default = Context::get('is_default');
            $args->skin = Context::get('planet_default_skin');
            


            $args->module = 'planet';
            $args->module_srl = $config->module_srl;
            if($args->is_default == 'Y') {
                $output = $oModuleController->clearDefaultModule();
                if(!$output->toBool()) return $output;
            }
            $output = $oModuleController->updateModule($args);
            if(!$output->toBool()) return $output;

            // 그외 정보 처리
            $config->planet_default_skin = Context::get('planet_default_skin');
            $config->use_mobile = Context::get('use_mobile');
            $config->use_me2day = Context::get('use_me2day');


            $tagtab = explode(',',Context::get('planet_tagtab'));
            for($i=0,$c=count($tagtab);$i<$c;$i++){
                if(trim($tagtab[$i])) continue;
                $tagtab[$i] = trim($tagtab[$i]);
            }
            $tagtab = array_unique($tagtab);
            $config->tagtab = $tagtab;

            $tagtab_after = explode(',',Context::get('planet_tagtab_after'));
            for($i=0,$c=count($tagtab_after);$i<$c;$i++){
                if(trim($tagtab_after[$i])) continue;
                $tagtab_after[$i] = trim($tagtab_after[$i]);
            }
            $tagtab_after = array_unique($tagtab_after);
            $config->tagtab_after = $tagtab_after;


            $smstag = explode(',',Context::get('planet_smstag'));
            for($i=0,$c=count($smstag);$i<$c;$i++){
                if(trim($smstag[$i])) continue;
                $tagtab[$i] = trim($tagtab[$i]);
            }
            $smstag = array_unique($smstag);
            $config->smstag = $smstag;


            $config->create_message = Context::get('create_message');
            $config->use_signup = Context::get('use_signup');
            if($config->use_signup != 'Y') $config->use_signup = 'N';

            $grant_list = array('access','create','manager','write_document');
            foreach($grant_list as $key) {
                $tmp = trim(Context::get($key));
                if(!$tmp) {
                    $config->grants[$key] = null;
                    continue;
                }
                $config->grants[$key] = explode('|@|', $tmp);
            }

            $oPlanetController = &getController('planet');
            $oPlanetController->insertPlanetConfig($config);

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

        function procPlanetAdminUpdateSkinInfo() {
            $oPlanetModel = &getModel('planet');
            $config = $oPlanetModel->getPlanetConfig();
            $skin = $config->planet_default_skin;

            $oModuleModel = &getModel('module');
            $skin_info = $oModuleModel->loadSkinInfo($this->module_path, $skin);

            $obj = Context::getRequestVars();
            unset($obj->act);
            unset($obj->module_srl);
            unset($obj->page);
            unset($obj->module);

            $config->colorset = $obj->colorset;

            // 원 skin_info에서 extra_vars의 type이 image일 경우 별도 처리를 해줌
            if($skin_info->extra_vars) {
                foreach($skin_info->extra_vars as $vars) {
                    if($vars->type!='image') {
                        $config->{$vars->name} = $obj->{$vars->name};
                        continue;
                    }

                    $image_obj = $obj->{$vars->name};

                    // 삭제 요청에 대한 변수를 구함
                    $del_var = $obj->{"del_".$vars->name};
                    unset($obj->{"del_".$vars->name});
                    if($del_var == 'Y') {
                        FileHandler::removeFile($config->{$vars->name});
                        $config->{$vars->name} = '';
                        continue;
                    }

                    // 업로드 되지 않았다면 이전 데이터를 그대로 사용
                    if(!$image_obj['tmp_name']) continue;

                    // 정상적으로 업로드된 파일이 아니면 무시
                    if(!is_uploaded_file($image_obj['tmp_name'])) continue;

                    // 이미지 파일이 아니어도 무시
                    if(!preg_match("/\.(jpg|jpeg|gif|png)$/i", $image_obj['name'])) continue;

                    // 경로를 정해서 업로드
                    $path = sprintf("./files/attach/planet/", $module_srl);

                    // 디렉토리 생성
                    if(!FileHandler::makeDir($path)) return false;

                    $filename = $path.$image_obj['name'];

                    // 파일 이동
                    if(!move_uploaded_file($image_obj['tmp_name'], $filename)) {
                        unset($obj->{$vars->name});
                        continue;
                    }

                    // 변수를 바꿈
                    $config->{$vars->name} = $filename;
                }
            }
            
            $oPlanetController = &getController('planet');
            $oPlanetController->insertPlanetConfig($config);

            $this->setLayoutPath('./common/tpl');
            $this->setLayoutFile('default_layout.html');
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile("top_refresh.html");
        }


    }
?>
