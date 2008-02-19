<?php
    /**
     * @class forum 
     * @author zero (zero@nzeo.com)
     * @brief 최근 게시물을 출력하는 위젯
     * @version 0.1
     **/

    class forum extends WidgetHandler {

        /**
         * @brief 위젯의 실행 부분
         *
         * ./widgets/위젯/conf/info.xml 에 선언한 extra_vars를 args로 받는다
         * 결과를 만든후 print가 아니라 return 해주어야 한다
         **/
        function proc($args) {
            // 제목
            $title = $args->title;

            // 최근 글 표시 시간
            $duration_new = $args->duration_new;
            if(!$duration_new) $duration_new = 12;

            // 제목 길이 자르기
            $subject_cut_size = $args->subject_cut_size;
            if(!$subject_cut_size) $subject_cut_size = 0;

            // 대상 모듈
            if($args->mid_list) $mid_list = explode(",",$args->mid_list);
            else return;

            // module_srl 대신 mid가 넘어왔을 경우는 직접 module_srl을 구해줌
            $oModuleModel = &getModel('module');
            $module_srls = $oModuleModel->getModuleSrlByMid($mid_list);

            // 대상 모듈의 정보를 구함
            $module_list = $oModuleModel->getModulesInfo($module_srls);

            // 각 모듈별로 먼저 정리 시작
            foreach($module_list as $module) {
                $modules[$module->module_srl]->title = $module->browser_title;
                $modules[$module->module_srl]->mid = $module->mid;
                $modules[$module->module_srl]->description = $module->description;
                $modules[$module->module_srl]->document_count = 0;
                $modules[$module->module_srl]->comment_count = 0;

                // 최근 등록된 댓글의 정보
                $last_comment = null;
                $last_args = null;
                $last_args->module_srl = $module->module_srl;
                $output = executeQuery('widgets.forum.getLatestComments', $last_args);
                if($output->data && is_array($output->data)) $last_comment = array_pop($output->data);

                // 최근 등록된 글의 정보
                $last_document = null;
                $last_args = null;
                $last_args->module_srl = $module->module_srl;
                $output = executeQuery('widgets.forum.getLatestDocuments', $last_args);
                if($output->data && is_array($output->data)) $last_document = array_pop($output->data);

                $last_item = null;
                if($last_comment && $last_document) {
                    if($last_document->regdate > $last_comment->regdate) $last_item = $last_document;
                    else $last_item = $last_comment;
                } elseif($last_document) {
                    $last_item = $last_document;
                } elseif($last_comment) {
                    $last_item = $last_comment;
                }
                $modules[$module->module_srl]->last_item = $last_item;

                if($last_item && $last_item->regdate > date("YmdHis",time()-$duration_new*60*60)) $modules[$module->module_srl]->is_new = true;
            }

            // 각 모듈별 전체글을 구함
            $total_documents_args->module_srl = $module->module_srl;
            $total_documents_args->module_srls = implode(',',$module_srls);

            $total_documents_output = executeQueryArray('widgets.forum.getTotalDocuments',$total_documents_args);
            if($total_documents_output->data) {
                foreach($total_documents_output->data as $val) {
                    $modules[$val->module_srl]->document_count = $val->count;
                }
            }

            // 각 모듈별 댓글 수를 구함
            $total_comments_args->module_srl = $module->module_srl;
            $total_comments_args->module_srls = implode(',',$module_srls);

            $total_comments_output = executeQueryArray('widgets.forum.getTotalComments',$total_comments_args);
            if($total_comments_output->data) {
                foreach($total_comments_output->data as $val) {
                    $modules[$val->module_srl]->comment_count = $val->count;
                }
            }

            $widget_info->title = $title;
            $widget_info->modules = $modules;
            $widget_info->subject_cut_size = $subject_cut_size;
            $widget_info->duration_new = $duration_new * 60*60;

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
