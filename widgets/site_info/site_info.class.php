<?php
    /**
     * @class site_info 
     * @author zero (zero@nzeo.com)
     * @brief 분양형 가상 사이트 현황 출력
     * @version 0.1
     **/

    class site_info extends WidgetHandler {

        /**
         * @brief 위젯의 실행 부분
         *
         * ./widgets/위젯/conf/info.xml 에 선언한 extra_vars를 args로 받는다
         * 결과를 만든후 print가 아니라 return 해주어야 한다
         **/
        function proc($args) {
            $oDocument = &getClass('document');

            $site_module_info = Context::get('site_module_info');
            Context::set('widget_info', $widget_info);

            $args->site_srl = (int)$site_module_info->site_srl;

            // 회원수 추출
            $output = executeQuery('widgets.site_info.getMemberCount', $args);
            $widget_info->member_count = $output->data->cnt;

            // 새글 추출
            $args->regdate = date("YmdHis", time()-24*60*60);
            $output = executeQuery('widgets.site_info.getNewDocument', $args);
            $widget_info->new_documents = $output->data->cnt;

            // 개설일
            $output = executeQuery('widgets.site_info.getCreatedDate', $args);
            $widget_info->created = $output->data->regdate;

            // 가입 여부
            $logged_info = Context::get('logged_info');
            if(count($logged_info->group_list)) $widget_info->joined = true;
            else $widget_info->joined = false;

            Context::set('widget_info', $widget_info);
            Context::set('colorset', $args->colorset);

            // 템플릿 컴파일
            $tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
            $tpl_file = 'site_info';


            $oTemplate = &TemplateHandler::getInstance();
            return $oTemplate->compile($tpl_path, $tpl_file);
        }
    }
?>
