<?php
    /**
     * @class rank_download
     * @author Simulz.com (k10206@naver.com)
     * @brief 파일 다운로드 랭킹
     **/

    class rank_download extends WidgetHandler {

        /**
         * @brief 위젯의 실행 부분
         *
         * ./widgets/위젯/conf/info.xml 에 선언한 extra_vars를 args로 받는다
         * 결과를 만든후 print가 아니라 return 해주어야 한다
         **/
        function proc($args) {
            // 제목
            $title = $args->title;

            // 정렬 대상
            $order_target = $args->order_target;

            // 정렬 순서
            $order_type = $args->order_type;
            if(!in_array($order_type, array('asc','desc'))) $order_type = 'asc';

            // 출력된 목록 수
            $list_count = (int)$args->list_count;
            if(!$list_count) $list_count = 5;

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

            // FileModel::getFileList()를 이용하기 위한 변수 정리
            $obj->s_module_srl = implode(',',$module_srl);
            $obj->direct_download = ($args->attach_type == "noimage") ? "N": (($args->attach_type == "image") ? "Y" : "");

            if($args->without_image == "true") $obj->direct_download = "N";
            $obj->list_count = $list_count;
            $obj->sort_index = $order_target;
            $obj->order_type = $args->order_type;

            // 다운로드 횟수 1이상만 검색
            $obj->s_download_count = 1;

            $output = executeQueryArray('widgets.rank_download.getFileList', $obj);

            // 오류가 생기면 그냥 무시
            if(!$output->toBool()) ;

            $oFileModel = &getModel('file');

            if(count($output->data)) {
                foreach($output->data as $key => $val) {
                    $file = $val;
                    $file->download_url = $oFileModel->getDownloadUrl($val->file_srl, $val->sid);

                    $file->source_filename = htmlspecialchars($val->source_filename);
                    $file_list[$key] = $file;
                }
            }

            $widget_info->title = $title;
            $widget_info->list_count = $list_count;
            $widget_info->file_list = $file_list;
            $widget_info->download = $args->download == "Y" ? true : false;
            $widget_info->subject_cut_size = $subject_cut_size;

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
