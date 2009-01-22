<?php
    /**
     * @class newest_comment
     * @author zero (zero@nzeo.com)
     * @brief 최근 댓글을 출력하는 위젯
     * @version 0.1
     **/

    class newest_comment extends WidgetHandler {

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

            // 출력된 목록 수
            $list_count = (int)$args->list_count;
            if(!$list_count) $list_count = 5;

            // 제목 길이 자르기
            $subject_cut_size = $args->subject_cut_size;
            if(!$subject_cut_size) $subject_cut_size = 0;

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

            // CommentModel::getCommentList()를 이용하기 위한 변수 정리
            if(is_array($module_srl)) $obj->module_srl = implode(',',$module_srl);
            else $obj->module_srl = $module_srl;
            $obj->sort_index = $order_target;
            $obj->list_count = $list_count;

            // comment 모듈의 model 객체를 받아서 getCommentList() method를 실행
            $oCommentModel = &getModel('comment');
            $output = $oCommentModel->getNewestCommentList($obj);

            // 템플릿 파일에서 사용할 변수들을 세팅
            if(count($mid_list)==1) $widget_info->module_name = $mid_list[0];
            
            $widget_info->title = $title;
            $widget_info->comment_list = $output;
            $widget_info->subject_cut_size = $subject_cut_size;
            $widget_info->display_regdate = $args->display_regdate=='N'?'N':'Y';

            Context::set('widget_info', $widget_info);

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
