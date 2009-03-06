<?php
    /**
     * @class  layoutAdminView
     * @author zero (zero@nzeo.com)
     * @brief  layout 모듈의 admin view class
     **/

    class layoutAdminView extends layout {

        /**
         * @brief 초기화
         **/
        function init() {
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * @brief 레이아웃 관리의 첫 페이지
         **/
        function dispLayoutAdminContent() {
            $oLayoutModel = &getModel('layout');
            $layout_list = $oLayoutModel->getLayoutList();
            Context::set('layout_list', $layout_list);

            $this->setTemplateFile('index');
        }

        /**
         * @brief 레이아웃 등록 페이지
         * 1차적으로 레이아웃만 선택한 후 DB 에 빈 값을 넣고 그 후 상세 값 설정하는 단계를 거침
         **/
        function dispLayoutAdminInsert() {
            // 레이아웃 목록을 세팅
            $oLayoutModel = &getModel('layout');
            $layout_list = $oLayoutModel->getDownloadedLayoutList();
            Context::set('layout_list', $layout_list);

            $this->setTemplateFile('insert_layout');
        }

        /**
         * @brief 레이아웃 세부 정보 입력
         **/
        function dispLayoutAdminModify() {

            // 선택된 레이아웃의 정보르 구해서 세팅
            $layout_srl = Context::get('layout_srl');

            // 레이아웃의 정보를 가져옴
            $oLayoutModel = &getModel('layout');
            $layout_info = $oLayoutModel->getLayout($layout_srl);

            // 등록된 레이아웃이 없으면 오류 표시
            if(!$layout_info) return $this->dispLayoutAdminContent();

            // faceoff면 경로를 보여줄 필요는 없다
            if($layout_info->type == 'faceoff') unset($layout_info->path);
            Context::set('selected_layout', $layout_info);

            // 메뉴 목록을 가져옴
            $oMenuAdminModel = &getAdminModel('menu');
            $menu_list = $oMenuAdminModel->getMenus();
            Context::set('menu_list', $menu_list);

            $this->setTemplateFile('layout_modify');
        }

        /**
         * @brief 레이아웃 코드 편집
         **/
        function dispLayoutAdminEdit() {
            // 선택된 레이아웃의 정보르 구해서 세팅
            $layout_srl = Context::get('layout_srl');

            // 레이아웃의 정보를 가져옴
            $oLayoutModel = &getModel('layout');
            $layout_info = $oLayoutModel->getLayout($layout_srl);

            // 등록된 레이아웃이 없으면 오류 표시
            if(!$layout_info) return $this->dispLayoutAdminContent();
            Context::set('selected_layout', $layout_info);

            // 레이아웃 코드 가져오기
            $oLayoutModel = &getModel('layout');
            $layout_file = $oLayoutModel->getUserLayoutHtml($layout_info->layout_srl);
            if(!file_exists($layout_file)){
                // faceoff 면
                if($oLayoutModel->useDefaultLayout($layout_info->layout_srl)){
                    $layout_file  = $oLayoutModel->getDefaultLayoutHtml($layout_info->layout);
                }else{
                    $layout_file = sprintf('%s%s', $layout_info->path, 'layout.html');
                }
            }

            $layout_css_file = $oLayoutModel->getUserLayoutCss($layout_info->layout_srl);
            if(file_exists($layout_css_file)){
                $layout_code_css = FileHandler::readFile($layout_css_file);
                Context::set('layout_code_css', $layout_code_css);
            }

            $layout_code = FileHandler::readFile($layout_file);
            Context::set('layout_code', $layout_code);

            // set User Images
            $layout_image_list = $oLayoutModel->getUserLayoutImageList($layout_info->layout_srl);
            Context::set('layout_image_list', $layout_image_list);

            $layout_image_path = $oLayoutModel->getUserLayoutImagePath($layout_info->layout_srl);
            Context::set('layout_image_path', $layout_image_path);

            // 위젯 목록을 세팅
            $oWidgetModel = &getModel('widget');
            $widget_list = $oWidgetModel->getDownloadedWidgetList();
            Context::set('widget_list', $widget_list);

            $this->setTemplateFile('layout_edit');
        }

        /**
         * @brief 레이아웃 목록을 보여줌
         **/
        function dispLayoutAdminDownloadedList() {
            // 레이아웃 목록을 세팅
            $oLayoutModel = &getModel('layout');
            $layout_list = $oLayoutModel->getDownloadedLayoutList();
            Context::set('layout_list', $layout_list);

            $this->setTemplateFile('downloaded_layout_list');
        }

        /**
         * @brief 레이아웃 미리 보기
         **/
        function dispLayoutAdminPreview() {
//            debugPrint(Context::getRequestVars());
            $layout_srl = Context::get('layout_srl');
            $code = Context::get('code');

            if(!$layout_srl || !$code) return new Object(-1, 'msg_invalid_request');
            //$code = str_replace(array('&lt;','&gt;','&quot;'), array('<','>','"'), $code);

            // 레이아웃 정보 가져오기
            $oLayoutModel = &getModel('layout');
            $layout_info = $oLayoutModel->getLayout($layout_srl);
            if(!$layout_info) return new Object(-1, 'msg_invalid_request');

            // faceoff 레이아웃일 경우 별도 처리
            if($layout_info && $layout_info->type == 'faceoff') {
                $oLayoutModel->doActivateFaceOff($layout_info);
            }

            // 관리자 레이아웃 수정화면에서 변경된 CSS가 있는지 조사
            $edited_layout_css = $oLayoutModel->getUserLayoutCss($layout_srl);
            if(file_exists($edited_layout_css)) Context::addCSSFile($edited_layout_css);

            // 레이아웃 정보중 extra_vars의 이름과 값을 $layout_info에 입력
            if($layout_info->extra_var_count) {
                foreach($layout_info->extra_var as $var_id => $val) {
                    $layout_info->{$var_id} = $val->value;
                }
            }

            // 레이아웃 정보중 menu를 Context::set
            if($layout_info->menu_count) {
                foreach($layout_info->menu as $menu_id => $menu) {
                    if(file_exists($menu->php_file)) @include($menu->php_file);
                    Context::set($menu_id, $menu);
                }
            }

            Context::set('layout_info', $layout_info);
            Context::set('content', Context::getLang('layout_preview_content'));

            // 코드를 임시로 저장
            $edited_layout_file = sprintf('./files/cache/layout/tmp.tpl');
            FileHandler::writeFile($edited_layout_file, $code);

            // 컴파일
            $oTemplate = &TemplateHandler::getInstance();

            $layout_path = $layout_info->path;
            $layout_file = 'layout';

            $layout_tpl = $oTemplate->compile($layout_path, $layout_file, $edited_layout_file);

            // 위젯등을 변환
            $oContext = &Context::getInstance();
            $layout_tpl = $oContext->transContent($layout_tpl);
            Context::set('layout_tpl', $layout_tpl);

            // 임시 파일 삭제
            FileHandler::removeFile($edited_layout_file);

            $this->setTemplateFile('layout_preview');
        }

        /**
         * @brief 레이아웃의 상세 정보(conf/info.xml)를 팝업 출력
         **/
        function dispLayoutAdminInfo() {
            // 선택된 레이아웃 정보를 구함
            $oLayoutModel = &getModel('layout');
            $layout_info = $oLayoutModel->getLayoutInfo(Context::get('selected_layout'));
            Context::set('layout_info', $layout_info);

            // 레이아웃을 팝업으로 지정
            $this->setLayoutFile('popup_layout');

            // 템플릿 파일 지정
            $this->setTemplateFile('layout_detail_info');
        }


        /**
         * @brief faceoff의 관리자 layout 수정
         **/
        function dispLayoutAdminLayoutModify(){
            // widget 을 수정용으로 컴파일
            Context::setTransWidgetCodeIncludeInfo(true);

            //layout_srl 를 가져온다
            $current_module_info = Context::get('current_module_info');
            $layout_srl = $current_module_info->layout_srl;

            // 파일로 임시저장을 하기때문에 남아 있을지 모르는 tmp를 지운다
            // to do 개선이 필요
            $delete_tmp = Context::get('delete_tmp');
            if($delete_tmp =='Y'){
                $oLayoutAdminController = &getAdminController('layout');
                $oLayoutAdminController->deleteUserLayoutTempFile($layout_srl);
            }

            $oLayoutModel = &getModel('layout');

            // layout file들은 temp로 사용한다.
            $oLayoutModel->setUseUserLayoutTemp();

            // css 를 inline style로 뽑는다
            $faceoffcss = $oLayoutModel->_getUserLayoutFaceOffCss($current_module_info->layout_srl);

            $css = FileHandler::readFile($faceoffcss);
            $match = null;
            preg_match_all('/([^\{]+)\{([^\}]*)\}/is',$css,$match);
            for($i=0,$c=count($match[1]);$i<$c;$i++) {
                $name = trim($match[1][$i]);
                $css = trim($match[2][$i]);
                if(!$css) continue;
                $css = str_replace('./images/',Context::getRequestUri().$oLayoutModel->getUserLayoutImagePath($layout_srl),$css);
                $style[] .= sprintf('"%s":"%s"',$name,$css);
            }

            if(count($style)) {
                $script = '<script type="text/javascript"> var faceOffStyle = {'.implode(',',$style).'}; </script>';
                Context::addHtmlHeader($script);
            }

            $oTemplate = &TemplateHandler::getInstance();
            Context::set('content', $oTemplate->compile($this->module_path.'tpl','about_faceoff'));

            // 템플릿 파일 지정
            $this->setTemplateFile('faceoff_layout_edit');
        }

        function dispLayoutAdminLayoutImageList(){
            $layout_srl = Context::get('layout_srl');
            $oLayoutModel = &getModel('layout');

            // 이미지 목록
            $layout_image_list = $oLayoutModel->getUserLayoutImageList($layout_srl);
            Context::set('layout_image_list',$layout_image_list);

            // 경로
            $layout_image_path = $oLayoutModel->getUserLayoutImagePath($layout_srl);
            Context::set('layout_image_path',$layout_image_path);

            $this->setLayoutFile('popup_layout');

            $this->setTemplateFile('layout_image_list');
        }
    }
?>
