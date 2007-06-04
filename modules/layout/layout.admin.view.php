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
            $layout_file = sprintf('./files/cache/layout/%d.html', $layout_info->layout_srl);
            if(!file_exists($layout_file)) $layout_file = sprintf('%s%s', $layout_info->path, 'layout.html');

            $layout_code = FileHandler::readFile($layout_file);
            Context::set('layout_code', $layout_code);

            // 플러그인 목록을 세팅
            $oPluginModel = &getModel('plugin');
            $plugin_list = $oPluginModel->getDownloadedPluginList();
            Context::set('plugin_list', $plugin_list);

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
            $layout_srl = Context::get('layout_srl');
            $code = Context::get('code');

            if(!$layout_srl || !$code) return new Object(-1, 'msg_invalid_request');
            //$code = str_replace(array('&lt;','&gt;','&quot;'), array('<','>','"'), $code);

            // 레이아웃 정보 가져오기
            $oLayoutModel = &getModel('layout');
            $layout_info = $oLayoutModel->getLayout($layout_srl);
            if(!$layout_info) return new Object(-1, 'msg_invalid_request');

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

            // 플러그인등을 변환
            $oContext = &Context::getInstance();
            $layout_tpl = $oContext->transContent($layout_tpl);
            Context::set('layout_tpl', $layout_tpl);
            
            // 임시 파일 삭제
            @unlink($edited_layout_file);

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
    }
?>
