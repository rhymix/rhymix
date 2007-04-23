<?php
    /**
     * @class  counterView
     * @author zero (zero@nzeo.com)
     * @brief  counter 모듈의 View class
     **/

    class counterView extends counter {

        /**
         * @brief 초기화
         **/
        function init() {
            // 템플릿 경로 지정 
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * @brief 관리자 페이지 초기화면
         **/
        function dispCounterAdminIndex() {
            // 정해진 일자가 없으면 오늘자로 설정
            $selected_date = Context::get('selected_date');
            if(!$selected_date) $selected_date = date("Ymd");
            Context::set('selected_date', $selected_date);

            // counter model 객체 생성
            $oCounterModel = &getModel('counter');

            // 전체 카운터 현황 가져오기
            $status = $oCounterModel->getStatus(array(0,$selected_date));
            Context::set('total_counter', $status[0]);
            Context::set('selected_day_counter', $status[$selected_date]);


            // 정해진 일자 현황 가져오기

            // 시간, 일, 월, 년도별로 데이터 가져오기
            
            // 표시
            $this->setTemplateFile('index');
        }

    }
?>
