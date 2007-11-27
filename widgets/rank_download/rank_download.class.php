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
            // 위젯 자체적으로 설정한 변수들을 체크
            $title = $args->title;
            $order_target = 'download_count';
            $args->order_type = 'desc';
            $list_count = (int)$args->list_count;
            if(!$list_count) $list_count = 5;
            $mid_list = explode(",",$args->mid_list);
            $subject_cut_size = $args->subject_cut_size;
            if(!$subject_cut_size) $subject_cut_size = 0;

            // module_srl 대신 mid가 넘어왔을 경우는 직접 module_srl을 구해줌
            if($mid_list) {
                $oModuleModel = &getModel('module');
                $module_srl = $oModuleModel->getModuleSrlByMid($mid_list);
            }

            // FileModel::getFileList()를 이용하기 위한 변수 정리
            $obj->s_module_srl = (is_array($module_srl)) ? implode(',',$module_srl) : "";
            $obj->direct_download = ($args->attach_type == "noimage") ? "N": (($args->attach_type == "image") ? "Y" : "");

            if($args->without_image == "true") $obj->direct_download = "N";
            $obj->list_count = $list_count;
            $obj->sort_index = $order_target;
            $obj->order_type = $args->order_type;

            // 다운로드 횟수 1이상만 검색
            $obj->s_download_count = 1;

            $output = executeQuery('widgets.rank_download.getFileList', $obj);

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
            
            // 템플릿 파일에서 사용할 변수들을 세팅
            if(count($mid_list)==1) $widget_info->module_name = $mid_list[0];

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
