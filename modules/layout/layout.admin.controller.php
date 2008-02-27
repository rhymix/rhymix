<?php
    /**
     * @class  layoutAdminController
     * @author zero (zero@nzeo.com)
     * @brief  layout 모듈의 admin controller class
     **/

    class layoutAdminController extends layout {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 레이아웃 신규 생성
         * 레이아웃의 신규 생성은 제목만 받아서 layouts테이블에 입력함
         **/
        function procLayoutAdminInsert() {
            $args->layout_srl = getNextSequence();
            $args->layout = Context::get('layout');
            $args->title = Context::get('title');

            $output = $this->insertLayout($args);
            if(!$output->toBool()) return $output;

            $this->add('layout_srl', $args->layout_srl);
        }

        function insertLayout($args) {
            $output = executeQuery("layout.insertLayout", $args);
            return $output;
        }

        /**
         * @brief 레이아웃 정보 변경
         * 생성된 레이아웃의 제목과 확장변수(extra_vars)를 적용한다
         **/
        function procLayoutAdminUpdate() {
            // module, act, layout_srl, layout, title을 제외하면 확장변수로 판단.. 좀 구리다..
            $extra_vars = Context::getRequestVars();
            unset($extra_vars->module);
            unset($extra_vars->act);
            unset($extra_vars->layout_srl);
            unset($extra_vars->layout);
            unset($extra_vars->title);
            unset($extra_vars->apply_layout);
            unset($extra_vars->header_script);

            $args = Context::gets('layout_srl','title');

            // 레이아웃의 정보를 가져옴
            $oLayoutModel = &getModel('layout');
            $layout_info = $oLayoutModel->getLayout($args->layout_srl);
            $menus = get_object_vars($layout_info->menu);
            if(count($menus)) {
                foreach($menus as $menu_id => $val) {
                    $menu_srl = Context::get($menu_id);
                    if(!$menu_srl) continue;
                    $menu_srl_list[] = $menu_srl;
                }

                // 정해진 메뉴가 있으면 모듈 및 메뉴에 대한 레이아웃 연동
                if(count($menu_srl_list) && Context::get('apply_layout')=='Y') {
                    // 해당 메뉴와 레이아웃 값을 매핑
                    $oMenuAdminController = &getAdminController('menu');
                    $oMenuAdminController->updateMenuLayout($args->layout_srl, $menu_srl_list);

                    // 해당 메뉴에 속한 mid의 layout값을 모두 변경
                    $oModuleController = &getController('module');
                    $oModuleController->updateModuleLayout($args->layout_srl, $menu_srl_list);
                }
            }

            // extra_vars의 type이 image일 경우 별도 처리를 해줌
            if($layout_info->extra_var) {
                foreach($layout_info->extra_var as $name => $vars) {
                    if($vars->type!='image') continue;

                    $image_obj = $extra_vars->{$name};
                    $extra_vars->{$name} = $layout_info->extra_var->{$name}->value;

                    // 삭제 요청에 대한 변수를 구함
                    $del_var = $extra_vars->{"del_".$name};
                    unset($extra_vars->{"del_".$name});
                    if($del_var == 'Y') {
                        $extra_vars->{$name} = '';
                        @unlink($extra_vars->{$name});
                        continue;
                    }

                    // 정상적으로 업로드된 파일이 아니면 무시
                    if(!$image_obj['tmp_name'] || !is_uploaded_file($image_obj['tmp_name'])) continue;

                    // 이미지 파일이 아니어도 무시 (swf는 패스~)
                    if(!preg_match("/\.(jpg|jpeg|gif|png|swf)$/i", $image_obj['name'])) continue;

                    // 경로를 정해서 업로드
                    $path = sprintf("./files/attach/images/%s/", $args->layout_srl);

                    // 디렉토리 생성
                    if(!FileHandler::makeDir($path)) continue;

                    $filename = $path.$image_obj['name'];

                    // 파일 이동
                    if(!move_uploaded_file($image_obj['tmp_name'], $filename)) continue;

                    $extra_vars->{$name} = $filename;
                }
            }

            // header script를 레이아웃 모듈의 config에 저장
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');
            $layout_config = $oModuleModel->getModuleConfig('layout');
            $layout_config->header_script[$args->layout_srl] = Context::get('header_script');
            $output = $oModuleController->insertModuleConfig('layout',$layout_config);
            
            // DB에 입력하기 위한 변수 설정 
            $args->extra_vars = serialize($extra_vars);

            $output = $this->updateLayout($args);
            if(!$output->toBool()) return $output;

            $this->setLayoutPath('./common/tpl');
            $this->setLayoutFile('default_layout.html');
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile("top_refresh.html");
        }

        function updateLayout($args) {
            $output = executeQuery('layout.updateLayout', $args);
            if($output->toBool()) {
                $cache_file = sprintf('./files/cache/layout/%s.%s.cache.php', $args->layout_srl, Context::getLangType());
                @unlink($cache_file);
            }
            return $output;
        }

        /**
         * @brief 레이아웃 삭제
         * 삭제시 메뉴 xml 캐시 파일도 삭제
         **/
        function procLayoutAdminDelete() {
            $layout_srl = Context::get('layout_srl');

            // 캐시 파일 삭제 
            $cache_list = FileHandler::readDir("./files/cache/layout","",false,true);
            if(count($cache_list)) {
                foreach($cache_list as $cache_file) {
                    $pos = strpos($cache_file, $layout_srl.'_');
                    if($pos>0) unlink($cache_file);
                }
            }

            $layout_file = sprintf('./files/cache/layout/%d.html', $layout_srl);
            if(file_exists($layout_file)) @unlink($layout_file);

            // 레이아웃 삭제
            $args->layout_srl = $layout_srl;

            $output = $this->deleteLayout($args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_deleted');
        }

        function deleteLayout($args) {
            $output = executeQuery("layout.deleteLayout", $args);
            return $output;
        }

        /**
         * @brief 레이아웃 코드 추가
         **/
        function procLayoutAdminCodeUpdate() {
            $layout_srl = Context::get('layout_srl');
            $code = Context::get('code');
            if(!$layout_srl || !$code) return new Object(-1, 'msg_invalid_request');

            $layout_file = sprintf('./files/cache/layout/%d.html', $layout_srl);
            FileHandler::writeFile($layout_file, $code);

            $this->setMessage('success_updated');
        }

        /**
         * @brief 레이아웃 코드 초기화
         **/
        function procLayoutAdminCodeReset() {
            $layout_srl = Context::get('layout_srl');
            if(!$layout_srl) return new Object(-1, 'msg_invalid_request');

            $layout_file = sprintf('./files/cache/layout/%d.html', $layout_srl);
            @unlink($layout_file);

            $this->setMessage('success_reset');
        }
    }
?>
