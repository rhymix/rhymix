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
            // 레이아웃 생성과 관련된 기본 정보를 받음
            $site_module_info = Context::get('site_module_info');
            $args->site_srl = (int)$site_module_info->site_srl;
            $args->layout_srl = getNextSequence();
            $args->layout = Context::get('layout');
            $args->title = Context::get('title');

            // DB 입력
            $output = $this->insertLayout($args);
            if(!$output->toBool()) return $output;

            // faceOff 레이아웃일 경우 init 필요
            $this->initLayout($args->layout_srl, $args->layout);

            // 결과 리턴
            $this->add('layout_srl', $args->layout_srl);
        }

        // 레이아웃 정보를 DB에 입력
        function insertLayout($args) {
            $output = executeQuery("layout.insertLayout", $args);
            return $output;
        }

        // faceOff 레이아웃을 경우 init
        function initLayout($layout_srl, $layout_name){
            $oLayoutModel = &getModel('layout');

            // faceOff일 경우 sample import
            if($oLayoutModel->useDefaultLayout($layout_name)) {
                $this->importLayout($layout_srl, $this->module_path.'tpl/faceOff_sample.tar');
            // 디렉토리 제거
            } else {
                FileHandler::removeDir($oLayoutModel->getUserLayoutPath($layout_srl));
            }
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

            $args = Context::gets('layout_srl','title');

            // 레이아웃의 정보를 가져옴
            $oLayoutModel = &getModel('layout');
            $oMenuAdminModel = &getAdminModel('menu');
            $layout_info = $oLayoutModel->getLayout($args->layout_srl);
            $menus = get_object_vars($layout_info->menu);
            if(count($menus) ) {
                foreach($menus as $menu_id => $val) {
                    $menu_srl = Context::get($menu_id);
                    if(!$menu_srl) continue;

                    $output = $oMenuAdminModel->getMenu($menu_srl);
                    $menu_srl_list[] = $menu_srl;
                    $menu_name_list[$menu_srl] = $output->title;

                    if(Context::get('apply_layout')=='Y') {
                        $menu_args = null;
                        $menu_args->menu_srl = $menu_srl;
                        $menu_args->site_srl = $layout_info->site_srl;
                        $output = executeQueryArray('layout.getLayoutModules', $menu_args);
                        if($output->data) {
                            $modules = array();
                            for($i=0;$i<count($output->data);$i++) {
                                $modules[] = $output->data[$i]->module_srl;
                            }

                            if(count($modules)) {
                                $update_args->module_srls = implode(',',$modules);
                                $update_args->layout_srl = $args->layout_srl;
                                $output = executeQuery('layout.updateModuleLayout', $update_args);
                            }
                        }
                    }
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
                    // 삭제 요청이 있거나, 새로운 파일이 업로드 되면, 기존 파일 삭제
                    if($del_var == 'Y' || $image_obj['tmp_name']) {
                        FileHandler::removeFile($extra_vars->{$name});
                        $extra_vars->{$name} = '';
                        if($del_var == 'Y' && !$image_obj['tmp_name']) continue;
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
            $layout_config->header_script = Context::get('header_script');
            $oModuleController->insertModulePartConfig('layout',$args->layout_srl,$layout_config);

            //menu의 title도 저장하자
            $extra_vars->menu_name_list = $menu_name_list;

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
                $oLayoutModel = &getModel('layout');
                $cache_file = $oLayoutModel->getUserLayoutCache($args->layout_srl, Context::getLangType());
                FileHandler::removeFile($cache_file);
            }
            return $output;
        }

        /**
         * @brief 레이아웃 삭제
         * 삭제시 메뉴 xml 캐시 파일도 삭제
         **/
        function procLayoutAdminDelete() {
            $layout_srl = Context::get('layout_srl');
            return $this->deleteLayout($layout_srl);
        }

        function deleteLayout($layout_srl) {
            $oLayoutModel = &getModel('layout');

            $path = $oLayoutModel->getUserLayoutPath($layout_srl);
            FileHandler::removeDir($path);

            $layout_file = $oLayoutModel->getUserLayoutHtml($layout_srl);
            if(file_exists($layout_file)) FileHandler::removeFile($layout_file);

            // 레이아웃 삭제
            $args->layout_srl = $layout_srl;
            $output = executeQuery("layout.deleteLayout", $args);
            if(!$output->toBool()) return $output;

            return new Object(0,'success_deleted');
        }

        /**
         * @brief 레이아웃 코드 추가
         **/
        function procLayoutAdminCodeUpdate() {
            $layout_srl = Context::get('layout_srl');
            $code = Context::get('code');
            $code_css = Context::get('code_css');
            if(!$layout_srl || !$code) return new Object(-1, 'msg_invalid_request');

            $oLayoutModel = &getModel('layout');
            $layout_file = $oLayoutModel->getUserLayoutHtml($layout_srl);
            FileHandler::writeFile($layout_file, $code);

            $layout_css_file = $oLayoutModel->getUserLayoutCss($layout_srl);
            FileHandler::writeFile($layout_css_file, $code_css);
            $this->setMessage('success_updated');
        }

        /**
         * @brief 레이아웃 코드 초기화
         **/
        function procLayoutAdminCodeReset() {
            $layout_srl = Context::get('layout_srl');
            if(!$layout_srl) return new Object(-1, 'msg_invalid_request');

            // delete user layout file
            $oLayoutModel = &getModel('layout');
            $layout_file = $oLayoutModel->getUserLayoutHtml($layout_srl);
            FileHandler::removeFile($layout_file);

            $info = $oLayoutModel->getLayout($layout_srl);

            // if face off delete, tmp file
            if($oLayoutModel->useDefaultLayout($info->layout)){
                $this->deleteUserLayoutTempFile($layout_srl);
                $faceoff_css = $oLayoutModel->getUserLayoutFaceOffCss($layout_srl);
                FileHandler::removeFile($faceoff_css);
            }

            $this->initLayout($layout_srl, $info->layout);
            $this->setMessage('success_reset');
        }


        /**
         * @brief 레이아웃 설정페이지 -> 이미지 업로드
         *
         **/
        function procLayoutAdminUserImageUpload(){
            if(!Context::isUploaded()) exit();

            $image = Context::get('user_layout_image');
            $layout_srl = Context::get('layout_srl');
            if(!is_uploaded_file($image['tmp_name'])) exit();

            if(!preg_match('/\.(gif|jpg|jpeg|gif|png|swf|flv)$/i', $image['name'])){
                return false;
            }

            $this->insertUserLayoutImage($layout_srl, $image);
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile("top_refresh.html");
        }

        /**
         * @brief 레이아웃 설정페이지 -> 이미지 업로드
         *
         **/
        function insertUserLayoutImage($layout_srl,$source){
            $oLayoutModel = &getModel('layout');
            $path = $oLayoutModel->getUserLayoutImagePath($layout_srl);
            if(!is_dir($path)) FileHandler::makeDir($path);

            $filename = strtolower($source['name']);
            if($filename != urlencode($filename)){
                $ext = substr(strrchr($filename,'.'),1);
                $filename = sprintf('%s.%s', md5($filename), $ext);
            }

            if(file_exists($path .'/'. $filename)) @unlink($path . $filename);
            if(!move_uploaded_file($source['tmp_name'], $path . $filename )) return false;
            return true;
        }


        /**
         * @brief 레이아웃 설정페이지 -> 이미지 삭제
         *
         **/
        function removeUserLayoutImage($layout_srl,$filename){
            $oLayoutModel = &getModel('layout');
            $path = $oLayoutModel->getUserLayoutImagePath($layout_srl);
            @unlink($path . $filename);
        }

        /**
         * @brief 레이아웃 설정페이지 -> 이미지 삭제
         *
         **/
        function procLayoutAdminUserImageDelete(){
            $filename = Context::get('filename');
            $layout_srl = Context::get('layout_srl');
            $this->removeUserLayoutImage($layout_srl,$filename);
            $this->setMessage('success_deleted');
        }


        /**
         * @brief 레이아웃 설정 저장
         * ini 로 저장한다 faceoff 용
         **/
        function procLayoutAdminUserValueInsert(){
            $oModuleModel = &getModel('module');

            $mid = Context::get('mid');
            if(!$mid) return new Object(-1, 'msg_invalid_request');

            $site_module_info = Context::get('site_module_info');
            $module_info = $oModuleModel->getModuleInfoByMid($mid, $site_module_info->site_srl);
            $layout_srl = $module_info->layout_srl;
            if(!$layout_srl) return new Object(-1, 'msg_invalid_request');

            $oLayoutModel = &getModel('layout');

            // save tmp?
            $temp = Context::get('saveTemp');
            if($temp =='Y'){
                $oLayoutModel->setUseUserLayoutTemp();
            }else{
                // delete temp files
                $this->deleteUserLayoutTempFile($layout_srl);
            }

            $this->add('saveTemp',$temp);

            // write user layout
            $extension_obj = Context::gets('e1','e2','neck','knee');

            $file = $oLayoutModel->getUserLayoutHtml($layout_srl);
            $content = FileHandler::readFile($file);
            $content = $this->addExtension($layout_srl,$extension_obj,$content);
            FileHandler::writeFile($file,$content);

            // write faceoff.css
            $css = Context::get('css');

            $css_file = $oLayoutModel->getUserLayoutFaceOffCss($layout_srl);
            FileHandler::writeFile($css_file,$css);

            // write ini
            $obj = Context::gets('type','align','column');
            $obj = (array)$obj;
            $src = $oLayoutModel->getUserLayoutIniConfig($layout_srl);
            foreach($obj as $key => $val) $src[$key] = $val;
            $this->insertUserLayoutValue($layout_srl,$src);
        }

        /**
         * @brief 레이아웃 설정 ini 저장
         *
         **/
        function insertUserLayoutValue($layout_srl,$arr){
            $oLayoutModel = &getModel('layout');
            $file = $oLayoutModel->getUserLayoutIni($layout_srl);
            FileHandler::writeIniFile($file, $arr);
        }

        function writeUserLayoutCss(){

        }

        /**
         * @brief faceoff용 위젯코드를 사용자 layout 파일에 직접 추가한다
         *
         **/
        function addExtension($layout_srl,$arg,$content){
            $oLayoutModel = &getModel('layout');
             $reg = '(<\!\-\- start\-e1 \-\->)(.*)(<\!\-\- end\-e1 \-\->)';
             $extension_content =  '\1' .stripslashes($arg->e1) . '\3';
             $content = eregi_replace($reg,$extension_content,$content);

             $reg = '(<\!\-\- start\-e2 \-\->)(.*)(<\!\-\- end\-e2 \-\->)';
             $extension_content =  '\1' .stripslashes($arg->e2) . '\3';
             $content = eregi_replace($reg,$extension_content,$content);

             $reg = '(<\!\-\- start\-neck \-\->)(.*)(<\!\-\- end\-neck \-\->)';
             $extension_content =  '\1' .stripslashes($arg->neck) . '\3';
             $content = eregi_replace($reg,$extension_content,$content);

             $reg = '(<\!\-\- start\-knee \-\->)(.*)(<\!\-\- end\-knee \-\->)';
             $extension_content =  '\1' .stripslashes($arg->knee) . '\3';
             $content = eregi_replace($reg,$extension_content,$content);
            return $content;
        }


        /**
         * @brief faceoff용 temp file들을 지운다
         *
         **/
         function deleteUserLayoutTempFile($layout_srl){
             $oLayoutModel = &getModel('layout');
             $file_list = $oLayoutModel->getUserLayoutTempFileList($layout_srl);
             foreach($file_list as $key => $file){
                FileHandler::removeFile($file);
             }
         }

        /**
         * @brief faceoff export
         *
         **/
         function procLayoutAdminUserLayoutExport(){
            $layout_srl = Context::get('layout_srl');
            if(!$layout_srl) return new Object('-1','msg_invalid_request');

            require_once(_XE_PATH_.'libs/tar.class.php');

            // 압축할 파일 목록을 가져온다
            $oLayoutModel = &getModel('layout');
            $file_list = $oLayoutModel->getUserLayoutFileList($layout_srl);

            // 압축을 한다.
            $tar = new tar();
            $user_layout_path = FileHandler::getRealPath($oLayoutModel->getUserLayoutPath($layout_srl));
            chdir($user_layout_path);
            $replace_path = getNumberingPath($layout_srl,3);
            foreach($file_list as $key => $file) $tar->addFile($file,$replace_path,'__LAYOUT_PATH__');

            $stream = $tar->toTarStream();
            $filename = 'faceoff_' . date('YmdHis') . '.tar';
            header("Cache-Control: ");
            header("Pragma: ");
            header("Content-Type: application/x-compressed");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
//            header("Content-Length: " .strlen($stream)); ?? why??
            header('Content-Disposition: attachment; filename="'. $filename .'"');
            header("Content-Transfer-Encoding: binary\n");
            echo $stream;

            // Context를 강제로 닫고 종료한다.
            Context::close();
            exit();
         }

        /**
         * @brief faceoff import
         *
         **/
         function procLayoutAdminUserLayoutImport(){
            // check upload
            if(!Context::isUploaded()) exit();
            $file = Context::get('file');
            if(!is_uploaded_file($file['tmp_name'])) exit();
            if(!preg_match('/\.(tar)$/i', $file['name'])) exit();

            $layout_srl = Context::get('layout_srl');
            if(!$layout_srl) exit();

            $oLayoutModel = &getModel('layout');
            $user_layout_path = FileHandler::getRealPath($oLayoutModel->getUserLayoutPath($layout_srl));
            if(!move_uploaded_file($file['tmp_name'], $user_layout_path . 'faceoff.tar')) exit();

            $this->importLayout($layout_srl, $user_layout_path.'faceoff.tar');
        }

        function importLayout($layout_srl, $source_file) {
            $oLayoutModel = &getModel('layout');
            $user_layout_path = FileHandler::getRealPath($oLayoutModel->getUserLayoutPath($layout_srl));
            $file_list = $oLayoutModel->getUserLayoutFileList($layout_srl);
            foreach($file_list as $key => $file){
                FileHandler::removeFile($user_layout_path . $file);
            }

            require_once(_XE_PATH_.'libs/tar.class.php');
            $image_path = $oLayoutModel->getUserLayoutImagePath($layout_srl);
            FileHandler::makeDir($image_path);
            $tar = new tar();
            $tar->openTAR($source_file);

            // layout.ini 파일이 없으면 
            if(!$tar->getFile('layout.ini')) return;

            $replace_path = getNumberingPath($layout_srl,3);
            foreach($tar->files as $key => $info) {
                FileHandler::writeFile($user_layout_path . $info['name'],str_replace('__LAYOUT_PATH__',$replace_path,$info['file']));
            }

            // 업로드한 파일을 삭제
            FileHandler::removeFile($source_file);
         }
    }
?>
