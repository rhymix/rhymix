<?php
    /**
     * @class tag_list
     * @author zero (zero@nzeo.com)
     * @brief 꼬리표 목록 출력
     * @version 0.1
     **/

    class tag_list extends WidgetHandler {

        /**
         * @brief 위젯의 실행 부분
         *
         * ./widgets/위젯/conf/info.xml 에 선언한 extra_vars를 args로 받는다
         * 결과를 만든후 print가 아니라 return 해주어야 한다
         **/
        function proc($args) {
            // 제목
            $title = $args->title;

            // 출력된 목록 수
            $list_count = (int)$args->list_count;
            if(!$list_count) $list_count = 20;
            $list_count ++;

            // 대상 모듈 (mid_list는 기존 위젯의 호환을 위해서 처리하는 루틴을 유지. module_srl로 위젯에서 변경)
            $oModuleModel = &getModel('module');
            if($args->mid_list) {
                $mid_list = explode(",",$args->mid_list);
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

            // TagModel::getTagList()를 이용하기 위한 변수 정리
            $obj->module_srl = $module_srl;
            $obj->list_count = $list_count;

            // tag 모듈의 model 객체를 받아서 getTagList() method를 실행
            $oTagModel = &getModel('tag');
            $output = $oTagModel->getTagList($obj);

            // 템플릿 파일에서 사용할 변수들을 세팅
            if(count($module_srl)==1) {
                $srl = $module_srl[0];
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($srl);
                $widget_info->mid = $widget_info->module_name = $module_info->mid;
            }
            $widget_info->title = $title;

            if(count($output->data)) {
                $tags = array();
                $max = 0;
                $min = 99999999;
                foreach($output->data as $key => $val) {
                    $tag = trim($val->tag);
                    if(!$tag) continue;
                    $count = $val->count;
                    if($max < $count) $max = $count;
                    if($min > $count) $min = $count;
                    $tags[] = $val;
                    if(count($tags)>=20) continue;
                }

                $mid2 = $min+(int)(($max-$min)/2);
                $mid1 = $mid2+(int)(($max-$mid2)/2);
                $mid3 = $min+(int)(($mid2-$min)/2);

                foreach($tags as $key => $item) {
                    if($item->count > $mid1) $rank = 1;
                    elseif($item->count > $mid2) $rank = 2;
                    elseif($item->count > $mid3) $rank = 3;
                    else $rank= 4;
                    $tags[$key]->rank = $rank;
                }
                shuffle($tags);
            }
            $widget_info->tag_list = $tags;

            Context::set('widget_info', $widget_info);

            // 템플릿의 스킨 경로를 지정 (skin, colorset에 따른 값을 설정)
            $tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
            Context::set('colorset', $args->colorset);

            // 템플릿 파일을 지정
            $tpl_file = 'tags';

            // 템플릿 컴파일
            $oTemplate = &TemplateHandler::getInstance();
            return $oTemplate->compile($tpl_path, $tpl_file);
        }
    }
?>
