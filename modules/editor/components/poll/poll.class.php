<?php
    /**
     * @class  poll
     * @author zero (zero@nzeo.com)
     * @brief  에디터에서 url링크하는 기능 제공. 
     **/

    class poll extends EditorHandler { 

        // upload_target_srl 는 에디터에서 필수로 달고 다녀야 함....
        var $upload_target_srl = 0;
        var $component_path = '';

        /**
         * @brief upload_target_srl과 컴포넌트의 경로를 받음
         **/
        function poll($upload_target_srl, $component_path) {
            $this->upload_target_srl = $upload_target_srl;
            $this->component_path = $component_path;
        }

        /**
         * @brief 팝업창에서 설문 작성 완료후 저장을 누를때
         **/
        function insertPoll() {
            Context::loadLang($this->component_path.'lang');
            $stop_year = Context::get('stop_year');
            $stop_month = Context::get('stop_month');
            $stop_day = Context::get('stop_day');

            $stop_date = sprintf('%04d%02d%02d235959', $stop_year, $stop_month, $stop_day);

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

            // poll module을 이용해서 DB 에 입력
            $oPollController = &getController('poll');
            $output = $oPollController->insertPolls($args);
            if(!$output->toBool()) return $output;

            $this->add('poll_srl', $output->get('poll_srl'));
        }

        /**
         * @brief popup window요청시 popup window에 출력할 내용을 추가하면 된다
         **/
        function getPopupContent() {
            // 템플릿을 미리 컴파일해서 컴파일된 소스를 return
            $tpl_path = $this->component_path.'tpl';
            $tpl_file = 'popup.html';

            require_once("./classes/template/TemplateHandler.class.php");
            $oTemplate = new TemplateHandler();
            return $oTemplate->compile($tpl_path, $tpl_file);
        }

    }
?>
