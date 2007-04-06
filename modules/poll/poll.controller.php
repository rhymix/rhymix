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

        /**
         * @brief 설문 조사에 응함
         **/
        function procPoll() {
            $poll_srl = Context::get('poll_srl'); 
            $poll_srl_indexes = Context::get('poll_srl_indexes'); 
            $tmp_item_srls = explode(',',$poll_srl_indexes);
            for($i=0;$i<count($tmp_item_srls);$i++) {
                $srl = (int)trim($tmp_item_srls[$i]);
                if(!$srl) continue;
                $item_srls[] = $srl;
            }

            // 응답항목이 없으면 오류
            if(!count($item_srls)) return new Object(-1, 'msg_check_poll_item');
            // 이미 설문하였는지 조사
            $oPollModel = &getModel('poll');
            if($oPollModel->isPolled($poll_srl)) return new Object(-1, 'msg_already_poll');

            $oDB = &DB::getInstance();
            $oDB->begin();

            // 설문 항목 추가
            $args->poll_srl = $poll_srl;

            $output = executeQuery('poll.updatePoll', $args);
            $output = executeQuery('poll.updatePollTitle', $args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            $args->poll_item_srl = implode(',',$item_srls);
            $output = executeQuery('poll.updatePollItems', $args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            // 응답자 정보를 로그로 남김
            $log_args->poll_srl = $poll_srl;
            $log_args->member_srl = $member_srl;
            $log_args->ipaddress = $_SERVER['REMOTE_ADDR'];
            $output = executeQuery('poll.insertPollLog', $log_args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            $oDB->commit();

            $this->add('poll_srl', $poll_srl);
            $this->add('tpl','haha');
            $this->setMessage('success_poll');
        }

    }
?>
