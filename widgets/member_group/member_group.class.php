<?php
    /**
     * @class member_group
     * @author zero (zero@nzeo.com)
     * @brief 특정 그룹의 대상자를 출력하는 위젯.
     * @version 0.1
     **/

    class member_group extends WidgetHandler {

        /**
         * @brief 위젯의 실행 부분
         *
         * ./widgets/위젯/conf/info.xml 에 선언한 extra_vars를 args로 받는다
         * 결과를 만든후 print가 아니라 return 해주어야 한다
         **/
        function proc($args) {

            // 위젯 자체적으로 설정한 변수들을 체크
            $title = $args->title;
            $list_count = (int)$args->list_count;
            if(!$list_count) $list_count = 5;

            $tmp_groups = explode(",",$args->target_group);
            $count = count($tmp_groups);
            for($i=0;$i<$count;$i++) {
                $group_name = trim($tmp_groups[$i]);
                if(!$group_name) continue;
                $target_group[] = $group_name;

            }

            if(count($target_group)) {

                // 그룹 목록을 구해옴
                $oMemberModel = &getModel('member');
                $group_list = $oMemberModel->getGroups();

                foreach($group_list as $group_srl => $val) {
                    if(!in_array($val->title, $target_group)) continue;
                    $target_group_srl_list[] = $group_srl;
                }

                // 해당 그룹의 멤버를 구해옴
                if(count($target_group_srl_list)) {
                    $obj->selected_group_srl = implode(',',$target_group_srl_list);
                    $obj->list_count = $list_count;
                    $output = executeQuery('member.getMemberListWithinGroup', $obj);
                    $widget_info->member_list = $output->data;
                }
            }

            $widget_info->title = $title;
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
