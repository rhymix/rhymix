<?php
    /**
     * @class rank_count
     * @author Simulz.com (simulz@simulz.com)
     * @brief 글, 댓글, 첨부 랭킹
     **/

    class rank_count extends WidgetHandler {

        /**
         * @brief 위젯의 실행 부분
         *
         * ./widgets/위젯/conf/info.xml 에 선언한 extra_vars를 args로 받는다
         * 결과를 만든후 print가 아니라 return 해주어야 한다
         **/
        function proc($args) {
            // 위젯 자체적으로 설정한 변수들을 체크
            $title = $args->title;
            $rankby = $args->rankby;
            $period = (int)$args->regdate;
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

            if($period) {
                $before_month_month_day = $this->convertDatetoDay( date("n") == 1 ? date("Y") - 1 : date("Y"),  date("n") == 1 ? 12 :  date("n") - 1);

                $m = date("n");
                $y = date("Y");

                if(date("j") < $period) {
                    $day = $before_month_month_day + date("j") - $period + 1;
                    $m = $m - 1;
                    if($m < 1) {
                        $m = 12;
                        $y = $y - 1;
                    }
                } else {
                    $day = date("j") - $period + 1;
                }

                $widget_info->date_from = $y."-".sprintf("%02d", $m)."-".sprintf("%02d", $day);
                $widget_info->period = $period;
                $obj->regdate = $y.sprintf("%02d", $m).sprintf("%02d", $day).date("His");
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
                    
                    if($rankby == "document") $output = executeQueryArray('widgets.rank_count.getRankDocumentCountWithinGroup', $obj);
                    elseif($rankby == "comment") $output = executeQueryArray('widgets.rank_count.getRankCommentCountWithinGroup', $obj);
                    elseif($rankby == "attach") $output = executeQueryArray('widgets.rank_count.getRankUploadedCountWithinGroup', $obj);
                    elseif($rankby == "vote") $output = executeQueryArray('widgets.rank_count.getRankVotedCountWithinGroup', $obj);
                    elseif($rankby == "read") $output = executeQueryArray('widgets.rank_count.getRankReadedCountWithinGroup', $obj);
                }
            }
            else {
                //전체 목록을 구해옴
                if($rankby == "document") $output = executeQueryArray('widgets.rank_count.getRankDocumentCount', $obj);
                elseif($rankby == "comment") $output = executeQueryArray('widgets.rank_count.getRankCommentCount', $obj);
                elseif($rankby == "attach") $output = executeQueryArray('widgets.rank_count.getRankUploadedCount', $obj);
                elseif($rankby == "vote") $output = executeQueryArray('widgets.rank_count.getRankVotedCount', $obj);
                elseif($rankby == "read") $output = executeQueryArray('widgets.rank_count.getRankReadedCount', $obj);
            }

            // 오류가 생기면 그냥 무시
            //if(!$output->toBool()) return;

            // 결과가 있으면 각 문서 객체화를 시킴
            if(count($output->data)) {
                foreach($output->data as $key => $val) {
                    $rank_list[$key] = $val;
                }
            } else {
                $rank_list = array();
            }
            
            // 템플릿 파일에서 사용할 변수들을 세팅
            if(count($mid_list)==1) $widget_info->module_name = $mid_list[0];

            $widget_info->title = $title;
            $widget_info->list_count = $list_count;
            $widget_info->data = $rank_list;
            $widget_info->rankby = $rankby;

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
         * @brief 날짜 수 계산
         **/
        function convertDatetoDay($year, $month) { 
            $numOfLeapYear = 0; // 윤년의 수 

            // 전년도까지의 윤년의 수를 구한다. 
            for($i = 0; $i < $year; $i++) { 
                if($this->isLeapYear($i)) $numOfLeapYear++; 
            } 

            // 전년도까지의 일 수를 구한다. 
            $toLastYearDaySum = ($year-1) * 365 + $numOfLeapYear; 

            // 올해의 현재 월까지의 일수 계산 
            $thisYearDaySum = 0; 
            //                        1,  2,  3,  4,  5,  6,  7,  8,  9, 10, 11, 12 
            $endOfMonth = array(1 => 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31); 

            for($i = 1; $i < $month; $i++) { 
                $thisYearDaySum += $endOfMonth[$i]; 
            } 

            // 윤년이고, 2월이 포함되어 있으면 1일을 증가시킨다. 
            if ($month > 2 && $this->isLeapYear($year)) $thisYearDaySum++; 

            if($this->isLeapYear($year)) $endOfMonth[2] = 29;

            return $endOfMonth[$month];
        }

        /**
         * @brief 윤년 검사
         **/
        function isLeapYear($year) { 
            if ($year % 4 == 0 && $year % 100 != 0 || $year % 400 == 0) return true; 
            else return false;
        } 
    }
?>
