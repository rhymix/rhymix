<?php
    /**
     * @class rank_point
     * @author Simulz.com (k10206@naver.com)
     * @brief 회원 포인트 랭킹
     **/

    class rank_point extends WidgetHandler {
        
        /**
         * @brief 포인트 정보 표시
         **/
        function point_info($member_srl) {
                $oModuleModel = &getModel('module');
                $this->config = $oModuleModel->getModuleConfig('point');

            $point = $this->oPointModel->getPoint($member_srl);
            $level = $this->oPointModel->getLevel($point, $this->config->level_step);

            $src = sprintf("modules/point/icons/%s/%d.gif", $this->config->level_icon, $level);
                $info = getimagesize($src);
                $this->icon_width = $info[0];
                $this->icon_height = $info[1];

            if($level < $this->config->max_level) {
                $next_point = $this->config->level_step[$level+1];
                if($next_point > 0) {
                    $per = (int)($point / $next_point*100);
                }
            }

            $code = sprintf('title="%s:%s%s %s, %s:%s/%s" style="background:url(%s) no-repeat left;padding-left:%dpx; height:%dpx"', Context::getLang('point'), $point, $this->config->point_name, $per?"(".$per."%)":"", Context::getLang('level'), $level, $this->config->max_level, Context::getRequestUri().$src, $this->icon_width+2, $this->icon_height);
            return $code;
        }

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
            $mid_list = explode(",",$args->mid_list);
            $subject_cut_size = $args->subject_cut_size;
            if(!$subject_cut_size) $subject_cut_size = 0;

            //그룹 정보를 구해옴 (그룹 포함)
            $tmp_groups = explode(",",$args->with_group);
            $count = count($tmp_groups);
            for($i = 0; $i < $count; $i++) {
                $group_name = trim($tmp_groups[$i]);
                if(!$group_name) continue;
                $target_group[$i] = $group_name;
            }

            //그룹 정보를 구해옴 (그룹 제외)
            $tmp_groups = explode(",",$args->without_group);
            $count = count($tmp_groups);
            for($i = 0; $i < $count; $i++) {
                $group_name = trim($tmp_groups[$i]);
                if(!$group_name) continue;
                $target_group_without[$i] = $group_name;
            }

            $oMemberModel = &getModel('member');
            $this->oPointModel = &getModel('point');

            $obj->list_count = $list_count;
            $obj->is_admin = $args->without_admin == "true" ? "N" : "";

            if(count($target_group) || count($target_group_without)) {
                // 그룹 목록을 구해옴
                $group_list = $oMemberModel->getGroups();

                if(count($target_group)) {
                    foreach($group_list as $group_srl => $val) {
                        if(!in_array($val->title, $target_group)) continue;
                        $target_group_srl_list[] = $group_srl;
                    }
                } else {
                    foreach($group_list as $group_srl => $val) {
                        if(!in_array($val->title, $target_group_without)) continue;
                        $target_group_without_srl_list[] = $group_srl;
                    }
                }

                // 해당 그룹의 멤버를 구해옴
                if(count($target_group_srl_list) || count($target_group_without_srl_list)) {
                    if(count($target_group_srl_list)) $obj->selected_group_srl = implode(',',$target_group_srl_list);
                    else $obj->selected_group_without_srl = implode(',',$target_group_without_srl_list);
                    $output = executeQuery('widgets.rank_point.getMemberListWithinGroup', $obj);
                }
            }
            else {
              //전체 포인트 목록을 구해옴
              $output = executeQuery("widgets.rank_point.getMemberList",$obj);
            }

            // 오류가 생기면 그냥 무시
            if(!$output->toBool()) return;

            // 결과가 있으면 각 문서 객체화를 시킴
            if(count($output->data)) {
                foreach($output->data as $key => $val) {
                    $val->perlev = $this->point_info($val->member_srl);

                    $image_name = $oMemberModel->getImageName($val->member_srl);
                    $image_mark = $oMemberModel->getImageMark($val->member_srl);
                    if($image_name->width) {
                        if($image_mark->height && $image_mark->height > $image_name->height) $top_margin = ($image_mark->height - $image_name->height)/2;
                        else $top_margin = 0;
                        $val->nick_name = sprintf('<img src="%s" border="0" alt="%s" title="%s" width="%s" height="%s" align="absmiddle" style="margin-top:%dpx;" />', Context::getRequestUri().$image_name->file, $image_name->file, $image_name->file, $image_name->width, $image_name->height, $top_margin);
                    }
                    if($image_mark->width) {
                        $val->nick_name = sprintf('<img src="%s" border="0" alt="%s" title="%s" width="%s" height="%s" align="absmiddle" />', Context::getRequestUri().$image_mark->file, $image_mark->file, $image_mark->file, $image_mark->width, $image_mark->height).$val->nick_name;
                    }

                    $point_list[$key] = $val;
                }
            } else {
                $point_list = array();
            }
            
            $widget_info->title = $title;
            $widget_info->list_count = $list_count;
            $widget_info->point_list = $point_list;
            $widget_info->subject_cut_size = $subject_cut_size;
            
            $widget_info->debug = $debug;
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
