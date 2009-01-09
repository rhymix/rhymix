<?php
    /**
     * @class tab_newest_document
     * @author zero (zero@nzeo.com)
     * @brief 다중 모듈 선택시 탭 형식으로 표시
     * @version 0.1
     **/

    class tab_newest_document extends WidgetHandler {

        /**
         * @brief 위젯의 실행 부분
         *
         * ./widgets/위젯/conf/info.xml 에 선언한 extra_vars를 args로 받는다
         * 결과를 만든후 print가 아니라 return 해주어야 한다
         **/
        function proc($args) {
            // 정렬 대상
            $widget_info->order_target = $args->order_target;
            if(!in_array($widget_info->order_target, array('list_order','update_order'))) $widget_info->order_target = 'list_order';

            // 정렬 순서
            $widget_info->order_type = $args->order_type;
            if(!in_array($widget_info->order_type, array('asc','desc'))) $widget_info->order_type = 'asc';

            // 글자 제목 길이
            $widget_info->subject_cut_size = (int)$args->subject_cut_size;

            // 목록 수
            $widget_info->list_count = $args->list_count;
            if(!$widget_info->list_count) $widget_info->list_count = 5;

            // 썸네일 생성 방법
            $widget_info->thumbnail_type = $args->thumbnail_type;
            if(!$widget_info->thumbnail_type) $widget_info->thumbnail_type = 'crop';

            // 썸네일 가로 크기
            $widget_info->thumbnail_width = (int)$args->thumbnail_width;
            if(!$widget_info->thumbnail_width) $widget_info->thumbnail_width = 100;

            // 썸네일 세로 크기
            $widget_info->thumbnail_height = (int)$args->thumbnail_height;
            if(!$widget_info->thumbnail_height) $widget_info->thumbnail_height = 100;

            // 노출 여부 체크
            if($args->display_author!='Y') $widget_info->display_author = 'N';
            else $widget_info->display_author = 'Y';
            if($args->display_regdate!='Y') $widget_info->display_regdate = 'N';
            else $widget_info->display_regdate = 'Y';
            if($args->display_readed_count!='Y') $widget_info->display_readed_count = 'N';
            else $widget_info->display_readed_count = 'Y';
            if($args->display_voted_count!='Y') $widget_info->display_voted_count = 'N';
            else $widget_info->display_voted_count = 'Y';

            // 최근 글 표시 시간
            $widget_info->duration_new = (int)$args->duration_new * 60 * 60;
            if(!$widget_info->duration_new) $widget_info->duration_new = 12 * 60 * 60;


            // 대상 모듈 정리
            $mid_list = explode(",",$args->mid_list);

            // 템플릿 파일에서 사용할 변수들을 세팅
            if(count($mid_list)==1) $widget_info->module_name = $mid_list[0];

            $oModuleModel = &getModel('module');
            $oDocumentModel = &getModel('document');

            // 대상 모듈 (mid_list는 기존 위젯의 호환을 위해서 처리하는 루틴을 유지. module_srl로 위젯에서 변경)
            if($args->mid_list) {
                $mid_list = explode(",",$args->mid_list);
                $oModuleModel = &getModel('module');
                if(count($mid_list)) {
                    $module_srl = $oModuleModel->getModuleSrlByMid($mid_list);
                } else {
                    $site_module_info = Context::get('site_module_info');
                    if($site_module_info) {
                        $margs->site_srl = $site_module_info->site_srl;
                        $oModuleModel = &getModel('module');
                        $output = $oModuleModel->getMidList($margs);
                        if(count($output)) $mid_list = array_keys($output);
                        $module_srl = $oModuleModel->getModuleSrlByMid($mid_list);
                    }
                }
            } else $module_srl = explode(',',$args->module_srls);

            $obj->module_srls = implode(',',$module_srl);

            // 모듈 목록을 구함
            $module_list = $oModuleModel->getMidList($obj);
            if(!$module_list || !count($module_list)) return;
            foreach($module_list as $key => $val) $mid_module_list[$val->module_srl] = $key;

            for($i=0;$i<count($module_srl);$i++) $tab_list[$mid_module_list[$module_srl[$i]]] = $module_list[$mid_module_list[$module_srl[$i]]];

            // 각 모듈에 해당하는 문서들을 구함
            $obj = null;
            $obj->list_count = $widget_info->list_count;
            $obj->sort_index = $widget_info->order_target;
            $obj->order_type = $widget_info->order_type=="desc"?"asc":"desc";
            foreach($tab_list as $mid => $module) {
                $obj->module_srl = $module_srl;
                $output = executeQueryArray("widgets.tab_newest_document.getNewestDocuments", $obj);
                unset($data);

                if($output->data && count($output->data)) {
                    foreach($output->data as $k => $v) {
                        $oDocument = null;
                        $oDocument = $oDocumentModel->getDocument();
                        $oDocument->setAttribute($v);
                        $tab_list[$mid]->document_list[] = $oDocument;
                    }
                } else {
                    $tab_list[$mid]->document_list = array();
                }
            }
            
            Context::set('widget_info', $widget_info);
            Context::set('tab_list', $tab_list);

            // 템플릿의 스킨 경로를 지정 (skin, colorset에 따른 값을 설정)
            $tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
            Context::set('colorset', $args->colorset);

            // 템플릿 파일을 지정
            $tpl_file = 'list';

            // 템플릿 컴파일
            $oTemplate = &TemplateHandler::getInstance();
            return $oTemplate->compile($tpl_path, $tpl_file);
        }
    }
?>
