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
            // 그룹이 선택되지 않으면 출력이 되지 않는다.
            if(!$args->with_group) return '';

            $site_module_info = Context::get('site_module_info');
            $obj->site_srl = (int)$site_module_info->site_srl;
            $obj->list_count = $args->list_count?$args->list_count:5;
            $obj->selected_group_srl = $args->with_group;

            //if($args->without_group) $obj->selected_group_without_srl = $args->without_group;

            if($args->period) {
                $before_month_month_day = $this->convertDatetoDay( date("n") == 1 ? date("Y") - 1 : date("Y"),  date("n") == 1 ? 12 :  date("n") - 1);
                $m = date("n");
                $y = date("Y");
                if(date("j") < $args->period) {
                    $day = $before_month_month_day + date("j") - $args->period + 1;
                    $m = $m - 1;
                    if($m < 1) {
                        $m = 12;
                        $y = $y - 1;
                    }
                } else {
                    $day = date("j") - $args->period + 1;
                }
                $widget_info->date_from = $y."-".sprintf("%02d", $m)."-".sprintf("%02d", $day);
                $widget_info->period = $args->period;
                $obj->regdate = $y.sprintf("%02d", $m).sprintf("%02d", $day).date("His");
            }

            //전체 목록을 구해옴
            switch($args->rankby) {
                case "read" :
                        $output = executeQueryArray('widgets.rank_count.getRankReadedCount', $obj);
                    break;
                case "vote" :
                        $output = executeQueryArray('widgets.rank_count.getRankVotedCount', $obj);
                    break;
                case "attach" :
                        $output = executeQueryArray('widgets.rank_count.getRankUploadedCount', $obj);
                    break;
                case "comment" :
                        $output = executeQueryArray('widgets.rank_count.getRankCommentCount', $obj);
                    break;
                default :
                        $output = executeQueryArray('widgets.rank_count.getRankDocumentCount', $obj);
                    break;
            }

            // 결과가 있으면 각 문서 객체화를 시킴
            if(count($output->data)) {
                foreach($output->data as $key => $val) {
                    $rank_list[$key] = $val;
                }
            } else {
                $rank_list = array();
            }
            
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
