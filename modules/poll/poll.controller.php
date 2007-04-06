<?php
    /**
     * @class  pollController
     * @author zero (zero@nzeo.com)
     * @brief  poll모듈의 Controller class
     **/

    class pollController extends poll {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 팝업창에서 설문 작성 완료후 저장을 누를때 설문 등록
         **/
        function procInsert() {
            $stop_year = Context::get('stop_year');
            $stop_month = Context::get('stop_month');
            $stop_day = Context::get('stop_day');

            $stop_date = sprintf('%04d%02d%02d235959', $stop_year, $stop_month, $stop_day);
            if($stop_date < date("YmdHis")) $stop_date = date("YmdHis", time()+60*60*24*365);

            $vars = Context::getRequestVars();
            foreach($vars as $key => $val) {
                if(strpos($key,'tidx')) continue;
                if(!eregi("^(title|checkcount|item)_", $key)) continue;
                if(!trim($val)) continue;

                $tmp_arr = explode('_',$key);

                $poll_index = $tmp_arr[1];

                if($tmp_arr[0]=='title') $tmp_args[$poll_index]->title = $val;
                else if($tmp_arr[0]=='checkcount') $tmp_args[$poll_index]->checkcount = $val;
                else if($tmp_arr[0]=='item') $tmp_args[$poll_index]->item[] = $val;
            }

            foreach($tmp_args as $key => $val) {
                if(!$val->checkcount) $val->checkcount = 1;
                if($val->title && count($val->item)) $args->poll[] = $val;
            }

            if(!count($args->poll)) return new Object(-1, 'cmd_null_item');

            $args->stop_date = $stop_date;

            // 변수 설정
            $poll_srl = getNextSequence();

            $logged_info = Context::get('logged_info');
            $member_srl = $logged_info->member_srl?$logged_info->member_srl:0;

            $oDB = &DB::getInstance();
            $oDB->begin();

            // 설문의 등록
            unset($poll_args);
            $poll_args->poll_srl = $poll_srl;
            $poll_args->member_srl = $member_srl;
            $poll_args->list_order = $poll_srl*-1;
            $poll_args->stop_date = $args->stop_date;
            $poll_args->poll_count = 0;
            $output = executeQuery('poll.insertPoll', $poll_args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            // 개별 설문 등록
            foreach($args->poll as $key => $val) {
                unset($poll_args);
                $poll_args->poll_srl = $poll_srl;
                $poll_args->poll_index_srl = $key+1;
                $poll_args->title = $val->title;
                $poll_args->checkcount = $val->checkcount;
                $poll_args->poll_count = 0;
                $output = executeQuery('poll.insertPollTitle', $poll_args);
                if(!$output->toBool()) {
                    $oDB->rollback();
                    return $output;
                }

                // 개별 설문의 항목 추가
                foreach($val->item as $k => $v) {
                    unset($poll_args);
                    $poll_args->poll_srl = $poll_srl;
                    $poll_args->poll_index_srl = $key+1;
                    $poll_args->title = $v;
                    $poll_args->poll_count = 0;
                    $output = executeQuery('poll.insertPollItem', $poll_args);
                    if(!$output->toBool()) {
                        $oDB->rollback();
                        return $output;
                    }
                }
            }

            // 작성자의 정보를 로그로 남김
            $log_args->poll_srl = $poll_srl;
            $log_args->member_srl = $member_srl;
            $output = executeQuery('poll.insertPollLog', $log_args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }
            
            $oDB->commit();

            $this->add('poll_srl', $poll_srl);
            $this->setMessage('success_registed');
        }

    }
?>
