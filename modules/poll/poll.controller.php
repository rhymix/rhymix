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
            // 기본적으로 필요한 변수 설정
            $editor_sequence = Context::get('editor_sequence');

            // upload_target_srl 구함
            $upload_target_srl = $_SESSION['upload_info'][$editor_sequence]->upload_target_srl;
            if(!$upload_target_srl) $upload_target_srl = getNextSequence();

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
            $poll_args->upload_target_srl = $upload_target_srl;
            $output = executeQuery('poll.insertPoll', $poll_args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            // 개별 설문 등록
            foreach($args->poll as $key => $val) {
                unset($title_args);
                $title_args->poll_srl = $poll_srl;
                $title_args->poll_index_srl = getNextSequence();
                $title_args->title = $val->title;
                $title_args->checkcount = $val->checkcount;
                $title_args->poll_count = 0;
                $title_args->list_order = $title_args->poll_index_srl * -1;
                $title_args->member_srl = $member_srl;
                $title_args->upload_target_srl = $upload_target_srl;
                $output = executeQuery('poll.insertPollTitle', $title_args);
                if(!$output->toBool()) {
                    $oDB->rollback();
                    return $output;
                }

                // 개별 설문의 항목 추가
                foreach($val->item as $k => $v) {
                    unset($item_args);
                    $item_args->poll_srl = $poll_srl;
                    $item_args->poll_index_srl = $title_args->poll_index_srl;
                    $item_args->title = $v;
                    $item_args->poll_count = 0;
                    $item_args->upload_target_srl = $upload_target_srl;
                    $output = executeQuery('poll.insertPollItem', $item_args);
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

            $args->poll_srl = $poll_srl;

            // 해당 글의 모든 설문조사의 응답수 올림
            $output = executeQuery('poll.updatePoll', $args);
            $output = executeQuery('poll.updatePollTitle', $args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            // 각 설문조사의 선택된 항목을 기록
            $args->poll_item_srl = implode(',',$item_srls);
            $output = executeQuery('poll.updatePollItems', $args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            // 응답자 정보를 로그로 남김
            $log_args->poll_srl = $poll_srl;

            $logged_info = Context::get('logged_info');
            $member_srl = $logged_info->member_srl?$logged_info->member_srl:0;

            $log_args->member_srl = $member_srl;
            $log_args->ipaddress = $_SERVER['REMOTE_ADDR'];
            $output = executeQuery('poll.insertPollLog', $log_args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            $oDB->commit();

            // tpl 가져오기
            $tpl = $oPollModel->getPollHtml($poll_srl);

            $this->add('poll_srl', $poll_srl);
            $this->add('tpl',$tpl);
            $this->setMessage('success_poll');
        }

        /**
         * @brief 결과 미리 보기
         **/
        function procPollViewResult() {
            $poll_srl = Context::get('poll_srl'); 

            $oPollModel = &getModel('poll');
            $tpl = $oPollModel->getPollResultHtml($poll_srl);

            $this->add('poll_srl', $poll_srl);
            $this->add('tpl',$tpl);
        }
    }
?>
