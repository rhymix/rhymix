<?php
    /**
     * @class  counterController
     * @author zero (zero@nzeo.com)
     * @brief  counter 모듈의 controller class
     **/

    class counterController extends counter {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 카운터 기록
         **/
        function procCounterExecute() {

            $oDB = &DB::getInstance();
            $oDB->begin();

            // 로그를 검사
            $oCounterModel = &getModel('counter');

            // 오늘자 row가 있는지 체크하여 없으면 등록
            if(!$oCounterModel->isInsertedTodayStatus()) {
                $this->insertTodayStatus();

            // 기존 row가 있으면 사용자 체크 
            } else {

                // 등록되어 있지 않은 아이피일 경우
                if(!$oCounterModel->isLogged()) {
                    // 로그 등록
                    $this->insertLog();

                    // unique 및 pageview 등록
                    $this->insertUniqueVisitor();
                } else {
                    // pageview 등록
                    $this->insertPageView();
                }
            }

            $oDB->commit();
        }

        /**
         * @brief 로그 등록 
         **/
        function insertLog() {
            $args->regdate = date("YmdHis");
            $args->user_agent = $_SERVER['HTTP_USER_AGENT'];
            return executeQuery('counter.insertCounterLog', $args);
        }

        /**
         * @brief unique visitor 등록
         **/
        function insertUniqueVisitor() {
            $args->regdate = date("Ymd");
            executeQuery('counter.updateCounterUnique', $args);
            executeQuery('counter.updateTotalCounterUnique');
        }

        /**
         * @brief pageview 등록
         **/
        function insertPageView() {
            $args->regdate = date("Ymd");
            executeQuery('counter.updateCounterPageview', $args);
            executeQuery('counter.updateTotalCounterPageview');
        }

        /**
         * @brief 오늘자 카운터 status 추가
         **/
        function insertTodayStatus($regdate = 0) {
            if($regdate) $args->regdate = $regdate;
            else $args->regdate = date("Ymd");
            executeQuery('counter.insertTodayStatus', $args);

            // 로그 등록
            $this->insertLog();

            // unique 및 pageview 등록
            $this->insertUniqueVisitor();
        }
    }
?>
