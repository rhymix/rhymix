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
         * @brief 설문 목록 구해옴 (관리자용)
         **/
        function getPollList($args) {
            $output = executeQuery('poll.getPollList', $args);
            if(!$output->toBool()) return $output;

            if($output->data && !is_array($output->data)) $output->data = array($output->data);
            return $output;
        }

        /**
         * @brief 이미 설문 조사를 하였는지 검사하는 함수
         **/
        function isPolled($poll_srl) {

            $args->poll_srl = $poll_srl;

            if(Context::get('is_logged')) {
                $logged_info = Context::get('logged_info');
                $args->member_srl = $logged_info->member_srl;
            } else {
                $args->ipaddress = $_SERVER['REMOTE_ADDR'];
            }
            $output = executeQuery('poll.getPollLog', $args);
            if($output->data->count) return true;
            return false;
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
            if(!$output->data) return;
            if(!is_array($output->data)) $output->data = array($output->data);
            foreach($output->data as $key => $val) {
                $poll->poll[$val->poll_index_srl]->title = $val->title;
                $poll->poll[$val->poll_index_srl]->checkcount = $val->checkcount;
                $poll->poll[$val->poll_index_srl]->poll_count = $val->poll_count;
            }

            $output = executeQuery('poll.getPollItem', $args);
            foreach($output->data as $key => $val) {
                $poll->poll[$val->poll_index_srl]->item[] = $val;
            }

            $poll->poll_srl = $poll_srl;

            // 종료일이 지났으면 무조건 결과만
            if($poll->stop_date > date("YmdHis")) {
                if($this->isPolled($poll_srl)) $tpl_file = "result";
                else $tpl_file = "form";
            } else {
                $tpl_file = "result";
            }

            Context::set('poll',$poll);

            // 기본 설정의 스킨, 컬러셋 설정 
            $oModuleModel = &getModel('module');
            $poll_config = $oModuleModel->getModuleConfig('poll');
            Context::set('poll_config', $poll_config);
            $tpl_path = sprintf("%sskins/%s/", $this->module_path, $poll_config->skin);

            require_once("./classes/template/TemplateHandler.class.php");
            $oTemplate = new TemplateHandler();
            return $oTemplate->compile($tpl_path, $tpl_file);
        }

        /**
         * @brief 선택된 설문조사 - 스킨의 컬러셋을 return
         **/
        function getPollGetColorsetList() {
            $skin = Context::get('skin');

            $oModuleModel = &getModel('module');
            $skin_info = $oModuleModel->loadSkinInfo($this->module_path, $skin);

            for($i=0;$i<count($skin_info->colorset);$i++) {
                $colorset = sprintf('%s|@|%s', $skin_info->colorset[$i]->name, $skin_info->colorset[$i]->title);
                $colorset_list[] = $colorset;
            }

            if(count($colorset_list)) $colorsets = implode("\n", $colorset_list);
            $this->add('colorset_list', $colorsets);
        }
    }
?>
