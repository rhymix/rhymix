<?php
    /**
     * @class calendar
     * @author zero (zero@nzeo.com)
     * @brief 보관현황 목록 출력
     * @version 0.1
     **/

    class calendar extends WidgetHandler {

        /**
         * @brief 위젯의 실행 부분
         *
         * ./widgets/위젯/conf/info.xml 에 선언한 extra_vars를 args로 받는다
         * 결과를 만든후 print가 아니라 return 해주어야 한다
         **/
        function proc($args) {
            $oModuleModel = &getModel('module');

            // 대상 모듈 (mid_list는 기존 위젯의 호환을 위해서 처리하는 루틴을 유지. module_srl로 위젯에서 변경)
            if($args->mid_list) {
                $tmp_mid = explode(",",$args->mid_list);
                $args->mid = $tmp_mid[0];
            } 

            if($args->mid) $args->srl = $oModuleModel->getModuleSrlByMid($args->mid);

            $obj->module_srl = $args->srl;

            // 선택된 모듈이 없으면 실행 취소
            if(!$obj->module_srl) return Context::getLang('msg_not_founded');

            // 모듈의 정보를 구함
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($obj->module_srl);

            if(Context::get('search_target')=='regdate') {
                $regdate = Context::get('search_keyword');
                if($regdate) $obj->regdate = zdate($regdate, 'Ym');
            }
            if(!$obj->regdate) $obj->regdate = zdate(date('YmdHis'), 'Ym');

            // document 모듈의 model 객체를 받아서 getDailyArchivedList() method를 실행
            $oDocumentModel = &getModel('document');
            $output = $oDocumentModel->getDailyArchivedList($obj);

            // 위젯 자체적으로 설정한 변수들을 체크
            $title = $args->title;

            // 템플릿 파일에서 사용할 변수들을 세팅
            $widget_info->cur_date = $obj->regdate;
            $widget_info->today_str = sprintf('%4d%s %2d%s',zdate($obj->regdate, 'Y'), Context::getLang('unit_year'), zdate($obj->regdate,'m'), Context::getLang('unit_month'));
            $widget_info->last_day = date('t', ztime($obj->regdate));
            $widget_info->start_week= date('w', ztime($obj->regdate));

            $widget_info->prev_month = date('Ym', mktime(1,0,0,zdate($obj->regdate,'m'),1,zdate($obj->regdate,'Y'))-60*60*24);
            $widget_info->prev_year = date('Y', mktime(1,0,0,1,1,zdate($obj->regdate,'Y'))-60*60*24);
            $widget_info->next_month = date('Ym', mktime(1,0,0,zdate($obj->regdate,'m'),$widget_info->last_day,zdate($obj->regdate,'Y'))+60*60*24);
            $widget_info->next_year = date('Y', mktime(1,0,0,12,$widget_info->last_day,zdate($obj->regdate,'Y'))+60*60*24);

            $widget_info->title = $title;

            if(count($output->data)) {
                foreach($output->data as $key => $val) $widget_info->calendar[$val->month] = $val->count;
            }

            if($module_info->site_srl) {
                $site_module_info = Context::get('site_module_info');
                if($site_module_info->site_srl == $module_info->site_srl) $widget_info->domain = $site_module_info->domain;
                else {
                    $site_info = $oModuleModel->getSiteInfo($module_info->site_srl);
                    $widget_info->domain = $site_info->domain;
                }
            } else $widget_info->domain = Context::getDefaultUrl();
            $widget_info->module_info = $module_info;
            $widget_info->mid = $module_info->mid;
            Context::set('widget_info', $widget_info);

            // 템플릿의 스킨 경로를 지정 (skin, colorset에 따른 값을 설정)
            $tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
            Context::set('colorset', $args->colorset);

            // 템플릿 파일을 지정
            $tpl_file = 'calendar';

            // 템플릿 컴파일
            $oTemplate = &TemplateHandler::getInstance();
            return $oTemplate->compile($tpl_path, $tpl_file);
        }
    }
?>
