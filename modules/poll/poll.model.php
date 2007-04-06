<?php
    /**
     * @class  pollModel
     * @author zero (zero@nzeo.com)
     * @brief  poll 모듈의 model class
     **/

    class pollModel extends poll {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 설문조사의 html데이터를 return
         * 설문조사에 응하였는지에 대한 체크를 한 후 결과를 return
         **/
        function getPollHtml($poll_srl, $style = '') {

            $args->poll_srl = $poll_srl;

            // 해당 설문조사에 대한 내용을 조사
            $output = executeQuery('poll.getPoll', $args);
            if(!$output->data) return '';

            $poll->style = $style;
            $poll->poll_count = (int)$output->data->poll_count;
            $poll->stop_date = $output->data->stop_date;

            $output = executeQuery('poll.getPollTitle', $args);
            foreach($output->data as $key => $val) {
                $poll->poll[$val->poll_index_srl]->title = $val->title;
                $poll->poll[$val->poll_index_srl]->checkcount = $val->checkcount;
                $poll->poll[$val->poll_index_srl]->poll_count = $val->poll_count;
            }

            $output = executeQuery('poll.getPollItem', $args);
            foreach($output->data as $key => $val) {
                $poll->poll[$val->poll_index_srl]->item[] = $val;
            }

            // 종료일이 지났으면 무조건 결과만
            if($poll->stop_date > date("YmdHis")) {
                // 현 사용자가 설문조사에 응하였는지 검사
                $logged_info = Context::get('logged_info');
                $args->member_srl = $logged_info->member_srl;
                $output = executeQuery('poll.getPollLog', $args);
                if($output->data->count) $poll->poll_date = $output->data->regdate;
                else $poll->poll_date = '';
                Context::set('poll', $poll);

                // 응하였다면 결과 html return
                if($poll->poll_date) $template_file = "result";

                // 응하지 않았다면 설문 form html return
                else $template_file = "form";

            } else {
                $template_file = "result";
            }

            $tpl_path = $this->module_path.'tpl';
            $tpl_file = $template_file;

            require_once("./classes/template/TemplateHandler.class.php");
            $oTemplate = new TemplateHandler();
            return $oTemplate->compile($tpl_path, $tpl_file);
        }

    }
?>
