<?php
    /**
     * @class point_status
     * @author zero (zero@nzeo.com)
     * @brief 포인트 현황 출력 위젯
     * @version 0.1
     **/

    class point_status extends WidgetHandler {

        /**
         * @brief 위젯의 실행 부분
         *
         * ./widgets/위젯/conf/info.xml 에 선언한 extra_vars를 args로 받는다
         * 결과를 만든후 print가 아니라 return 해주어야 한다
         **/
        function proc($args) {
            // 비로그인 사용자의 경우 결과를 출력하지 않음
            if(!Context::get('is_logged')) return;

            // 로그인 정보를 구함
            $logged_info = Context::get('logged_info');
            $member_srl = $logged_info->member_srl;
            if(!$member_srl) return;

            // 포인트 관련 설정을 구함
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');

            // 포인트 내역을 구함
            $oPointModel = &getModel('point');
            $widget_info->point = $oPointModel->getPoint($member_srl);
            $widget_info->level = $oPointModel->getLevel($widget_info->point, $config->level_step);
            $widget_info->level_icon = sprintf("./modules/point/icons/%s/%d.gif", $config->level_icon, $widget_info->level);

            // 최고 레벨이 아니면 다음 레벨로 가기 위한 per을 구함
            if($widget_info->level < $config->max_level) {
                $next_point = $config->level_step[$widget_info->level+1];
                if($next_point > 0) $per = (int)($widget_info->point / $next_point*100);
            }
            $widget_info->per = $per;
            $widget_info->next_point = $next_point;

            // 단위 설정
            $widget_info->point_unit = $config->point_name;

            // widget_info를 context setting
            Context::set('widget_info', $widget_info);

            // 템플릿의 스킨 경로를 지정 (skin, colorset에 따른 값을 설정)
            $tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
            Context::set('colorset', $args->colorset);

            // 템플릿 파일을 지정
            $tpl_file = 'status';

            // 템플릿 컴파일
            $oTemplate = &TemplateHandler::getInstance();
            return $oTemplate->compile($tpl_path, $tpl_file);
        }
    }
?>
