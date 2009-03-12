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

            $site_module_info = Context::get('site_module_info');
            $site_srl = (int)$site_module_info->site_srl;

            // 로그를 검사
            $oCounterModel = &getModel('counter');

            // 오늘자 row가 있는지 체크하여 없으면 등록
            if(!$oCounterModel->isInsertedTodayStatus($site_srl)) {
                $this->insertTodayStatus(0,$site_srl);

            // 기존 row가 있으면 사용자 체크 
            } else {

                // 등록되어 있지 않은 아이피일 경우
                if(!$oCounterModel->isLogged($site_srl)) {
                    // 로그 등록
                    $this->insertLog($site_srl);

                    // unique 및 pageview 등록
                    $this->insertUniqueVisitor($site_srl);
                } else {
                    // pageview 등록
                    $this->insertPageView($site_srl);
                }
            }

            $oDB->commit();
        }

        /**
         * @brief 로그 등록 
         **/
        function insertLog($site_srl=0) {
            $args->regdate = date("YmdHis");
            $args->user_agent = $_SERVER['HTTP_USER_AGENT'];
            $args->site_srl = $site_srl;
            return executeQuery('counter.insertCounterLog', $args);
        }

        /**
         * @brief unique visitor 등록
         **/
        function insertUniqueVisitor($site_srl=0) {
            $args->regdate = '0,'.date("Ymd");
            if($site_srl) {
                $args->site_srl = $site_srl;
                $output = executeQuery('counter.updateSiteCounterUnique', $args);
            } else {
                $output = executeQuery('counter.updateCounterUnique', $args);
            }
        }

        /**
         * @brief pageview 등록
         **/
        function insertPageView($site_srl=0) {
            $args->regdate = '0, '.date('Ymd');
            if($site_srl) { 
                $args->site_srl = $site_srl;
                executeQuery('counter.updateSiteCounterPageview', $args);
            } else {
                executeQuery('counter.updateCounterPageview', $args);
            }
        }

        /**
         * @brief 전체 카운터 status 추가
         **/
        function insertTotalStatus($site_srl=0) {
            $args->regdate = 0;
            if($site_srl) {
                $args->site_srl = $site_srl;
                executeQuery('counter.insertSiteTodayStatus', $args);
            } else {
                executeQuery('counter.insertTodayStatus', $args);
            }
        }

        /**
         * @brief 오늘자 카운터 status 추가
         **/
        function insertTodayStatus($regdate = 0, $site_srl=0) {
            if($regdate) $args->regdate = $regdate;
            else $args->regdate = date("Ymd");
            if($site_srl) {
                $args->site_srl = $site_srl;
                $query_id = 'counter.insertSiteTodayStatus';

                $u_args->site_srl = $site_srl; ///< 일별 row입력시 전체 row (regdate=0)도 같이 입력 시도
                executeQuery($query_id, $u_args);
            } else {
                $query_id = 'counter.insertTodayStatus';
                executeQuery($query_id); ///< 일별 row입력시 전체 row (regdate=0)도 같이 입력 시도
            }
            $output = executeQuery($query_id, $args);

            // 로그 등록
            $this->insertLog($site_srl);

            // unique 및 pageview 등록
            $this->insertUniqueVisitor($site_srl);
        }

        /**
         * @brief 특정 가상 사이트의 카운터 로그 삭제
         **/
        function deleteSiteCounterLogs($site_srl) {
            $args->site_srl = $site_srl;
            executeQuery('counter.deleteSiteCounter',$args);
            executeQuery('counter.deleteSiteCounterLog',$args);
        }
    }
?>
