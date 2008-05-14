<?php
    /**
     * @class  pointView
     * @author zero (zero@nzeo.com)
     * @brief  point module의 view class
     *
     * POINT 2.0형식으로 문서 출력
     *
     **/

    class pointView extends point {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 서비스형 모듈의 추가 설정을 위한 부분
         * point의 사용 형태에 대한 설정만 받음
         **/
        function triggerDispPointAdditionSetup(&$obj) {
            $current_module_srl = Context::get('module_srl');
            $current_module_srls = Context::get('module_srls');

            if(!$current_module_srl && !$current_module_srls) {
                // 선택된 모듈의 정보를 가져옴
                $current_module_info = Context::get('current_module_info');
                $current_module_srl = $current_module_info->module_srl;
                if(!$current_module_srl) return new Object();
            }

            // 설정 정보 가져오기
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');

            if($config->module_point[$current_module_srl]) $module_config = $config->module_point[$current_module_srl];
            else {
                $module_config['insert_document'] = $config->insert_document;
                $module_config['insert_comment'] = $config->insert_comment;
                $module_config['upload_file'] = $config->upload_file;
                $module_config['download_file'] = $config->download_file;
                $module_config['read_document'] = $config->read_document;

		//2008.05.13 haneul
		$module_config['voted'] = $config->voted;
		$module_config['blamed'] = $config->blamed;
            }

            $module_config['module_srl'] = $current_module_srl;
            $module_config['point_name'] = $config->point_name;
            
            Context::set('module_config', $module_config);

            // 템플릿 파일 지정
            $oTemplate = &TemplateHandler::getInstance();
            $tpl = $oTemplate->compile($this->module_path.'tpl', 'point_module_config');
            $obj .= $tpl;

            return new Object();
        }
    }
?>
