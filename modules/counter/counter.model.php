<?php
    /**
     * @class  counterModel
     * @author zero (zero@nzeo.com)
     * @brief  counter 모듈의 Model class
     **/

    class counterModel extends counter {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 로그 검사
         **/
        function isLogged($site_srl=0) {
            $args->regdate = date("Ymd");
            $args->ipaddress = $_SERVER['REMOTE_ADDR'];
            $args->site_srl = $site_srl;
            $output = executeQuery('counter.getCounterLog', $args);
            return $output->data->count?true:false;
        }

        /**
         * @brief 오늘자 카운터 현황 row 있는지 체크
         **/
        function isInsertedTodayStatus($site_srl=0) {
            $args->regdate = date("Ymd");
            if($site_srl) {
                $args->site_srl = $site_srl;
                $output = executeQuery('counter.getSiteTodayStatus', $args);
            } else {
                $output = executeQuery('counter.getTodayStatus', $args);
            }
            return $output->data->count?true:false;
        }

        /**
         * @brief 특정 일의 접속 통계를 가져옴
         **/
        function getStatus($selected_date, $site_srl=0) {
            // 여러개의 날짜 로그를 가져올 경우
            if(is_array($selected_date)) {
                $date_count = count($selected_date);
                $args->regdate = implode(',',$selected_date);

            // 단일 날짜의 로그를 가져올 경우
            } else {
                if(strlen($selected_date)==8) $selected_date = $selected_date;
                $args->regdate = $selected_date;
            }

            if($site_srl) {
                $args->site_srl = $site_srl;
                $output = executeQuery('counter.getSiteCounterStatusDays', $args);
            } else {
                $output = executeQuery('counter.getCounterStatusDays', $args);
            }
            $status = $output->data;

            if(!is_array($selected_date)) return $status;

            if(!is_array($status)) $status = array($status);
            unset($output);

            foreach($status as $key => $val) {
                $output[substr($val->regdate,0,8)] = $val;
            }
            return $output;
        }

        /**
         * @brief 지정된 일자의 시간대별 로그 가져오기
         **/
        function getHourlyStatus($type='hour', $selected_date, $site_srl=0) {
            $max = 0;
            $sum = 0;
            switch($type) {
                case 'year' :
                        // 카운터 시작일 구함
                        if($site_srl) {
                            $args->site_srl = $site_srl;
                            $output = executeQuery('counter.getSiteStartLogDate', $args);
                        } else {
                            $output = executeQuery('counter.getStartLogDate');
                        }
                        $start_year = substr($output->data->regdate,0,4);
                        if(!$start_year) $start_year = date("Y");
                        for($i=$start_year;$i<=date("Y");$i++) {
                            unset($args);
                            $args->start_date = sprintf('%04d0000', $i);
                            $args->end_date = sprintf('%04d1231', $i);
                            if($site_srl) {
                                $args->site_srl = $site_srl;
                                $output = executeQuery('counter.getSiteCounterStatus', $args);
                            } else { 
                                $output = executeQuery('counter.getCounterStatus', $args);
                            }
                            $count = (int)$output->data->unique_visitor;
                            $status->list[$i] = $count;
                            if($count>$max) $max = $count;
                            $sum += $count;
                        }
                    break;
                    break;
                case 'month' :
                        $year = substr($selected_date, 0, 4);
                        for($i=1;$i<=12;$i++) {
                            unset($args);
                            $args->start_date = sprintf('%04d%02d00', $year, $i);
                            $args->end_date = sprintf('%04d%02d31', $year, $i);
                            if($site_srl) {
                                $args->site_srl = $site_srl;
                                $output = executeQuery('counter.getSiteCounterStatus', $args);
                            } else { 
                                $output = executeQuery('counter.getCounterStatus', $args);
                            }
                            $count = (int)$output->data->unique_visitor;
                            $status->list[$i] = (int)$count;
                            if($count>$max) $max = $count;
                            $sum += $count;
                        }
                    break;
                case 'hour' :
                        for($i=0;$i<24;$i++) {
                            unset($args);
                            $args->start_date = sprintf('%08d%02d0000', $selected_date, $i);
                            $args->end_date = sprintf('%08d%02d5959', $selected_date, $i);
                            if($site_srl) {
                                $args->site_srl = $site_srl;
                                $output = executeQuery('counter.getSiteCounterLogStatus', $args);
                            } else { 
                                $args->site_srl = 0;
                                $output = executeQuery('counter.getCounterLogStatus', $args);
                            }
                            $count = (int)$output->data->count;
                            $status->list[$i] = $count;
                            if($count>$max) $max = $count;
                            $sum += $count;
                        }
                    break;
                default : 
                        $year = substr($selected_date, 0, 4);
                        $month = substr($selected_date, 4, 2);
                        $end_day = date('t', mktime(0,0,0,$month,1,$year));
                        for($i=1;$i<=$end_day;$i++) {
                            unset($args);
                            $args->start_date = sprintf('%04d%02d%02d', $year, $month, $i);
                            $args->end_date = sprintf('%04d%02d%02d', $year, $month, $i);
                            if($site_srl) {
                                $args->site_srl = $site_srl;
                                $output = executeQuery('counter.getSiteCounterStatus', $args);
                            } else { 
                                $output = executeQuery('counter.getCounterStatus', $args);
                            }
                            $count = (int)$output->data->unique_visitor;
                            $status->list[$i] = $count;
                            if($count>$max) $max = $count;
                            $sum += $count;
                        }
                    break;
            }

            $status->max = $max;
            $status->sum = $sum;
            return $status;
        }

    }
?>
