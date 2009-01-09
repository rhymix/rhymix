<?php
    /**
     * @class newest_images
     * @author zero (zero@nzeo.com)
     * @brief 최근 이미지를 출력하는 위젯
     * @version 0.1
     **/

    class newest_images extends WidgetHandler {

        /**
         * @brief 위젯의 실행 부분
         *
         * ./widgets/위젯/conf/info.xml 에 선언한 extra_vars를 args로 받는다
         * 결과를 만든후 print가 아니라 return 해주어야 한다
         **/
        function proc($args) {
            // 글자 제목 길이
            $widget_info->title_length = (int)$args->title_length;
            if(!$widget_info->title_length) $widget_info->title_length = 10;

            // 썸네일 생성 방법
            $widget_info->thumbnail_type = $args->thumbnail_type;
            if(!$widget_info->thumbnail_type) $widget_info->thumbnail_type = 'crop';

            // 썸네일 가로 크기
            $widget_info->thumbnail_width = (int)$args->thumbnail_width;
            if(!$widget_info->thumbnail_width) $widget_info->thumbnail_width = 100;

            // 썸네일 세로 크기
            $widget_info->thumbnail_height = (int)$args->thumbnail_height;
            if(!$widget_info->thumbnail_height) $widget_info->thumbnail_height = 100;

            // 세로 이미지 수
            $widget_info->rows_list_count = (int)$args->rows_list_count;
            if(!$widget_info->rows_list_count) $widget_info->rows_list_count = 1;

            // 가로 이미지 수
            $widget_info->cols_list_count = (int)$args->cols_list_count;
            if(!$widget_info->cols_list_count) $widget_info->cols_list_count = 5;

            // 노출 여부 체크
            if($args->display_author!='Y') $widget_info->display_author = 'N';
            else $widget_info->display_author = 'Y';
            if($args->display_regdate!='Y') $widget_info->display_regdate = 'N';
            else $widget_info->display_regdate = 'Y';
            if($args->display_readed_count!='Y') $widget_info->display_readed_count = 'N';
            else $widget_info->display_readed_count = 'Y';
            if($args->display_voted_count!='Y') $widget_info->display_voted_count = 'N';
            else $widget_info->display_voted_count = 'Y';

            // 제목
            $widget_info->title = $args->title;

            $oModuleModel = &getModel('module');

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

            $obj->module_srls = implode(",",$module_srl);
            $obj->direct_download = 'Y';
            $obj->isvalid = 'Y';
            $oDocumentModel = &getModel('document');

            // 정해진 모듈에서 문서별 파일 목록을 구함
            $files_output = executeQueryArray("file.getOneFileInDocument", $obj);
            $files_count = count($files_output->data);

            $document_srl_list = array();
            $document_list = array();

            if($files_count>0) {
                for($i=0;$i<$files_count;$i++) $document_srl_list[] = $files_output->data[$i]->document_srl;

                $tmp_document_list = $oDocumentModel->getDocuments($document_srl_list);
                if(count($tmp_document_list)) {
                    foreach($tmp_document_list as $val) $document_list[] = $val;
                }
            }

            $document_count = count($document_list);
            $total_count = $widget_info->rows_list_count * $widget_info->cols_list_count;
            for($i=$document_count;$i<$total_count;$i++) $document_list[] = new DocumentItem();
            $widget_info->document_list = $document_list;

            Context::set('widget_info', $widget_info);

            // 템플릿의 스킨 경로를 지정 (skin, colorset에 따른 값을 설정)
            $tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
            Context::set('colorset', $args->colorset);

            // 템플릿 파일을 지정
            $tpl_file = 'list';

            // 템플릿 컴파일
            $oTemplate = &TemplateHandler::getInstance();
            $output = $oTemplate->compile($tpl_path, $tpl_file);
            return $output;
        }
    }
?>
