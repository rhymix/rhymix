<?php
    /**
     * @class  counterController
     * @author NHN (developers@xpressengine.com)
     * @brief counter module's controller class
     **/

    class counterController extends counter {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief Counter logs
		 * @deprecated, if want use below function, you can use 'counterExecute' function instead this function
         **/
        function procCounterExecute() {
        }

        /**
         * @brief Counter logs
         **/
        function counterExecute() {
            $oDB = &DB::getInstance();
            $oDB->begin();

            $site_module_info = Context::get('site_module_info');
            $site_srl = (int)$site_module_info->site_srl;
            // Check the logs
            $oCounterModel = &getModel('counter');
            // Register today's row if not exist
            if(!$oCounterModel->isInsertedTodayStatus($site_srl)) {
                $this->insertTodayStatus(0,$site_srl);
            // check user if the previous row exists
            } else {
                // If unregistered IP
                if(!$oCounterModel->isLogged($site_srl)) {
                    // Leave logs
                    $this->insertLog($site_srl);
                    // Register unique and pageview
                    $this->insertUniqueVisitor($site_srl);
                } else {
                    //  Register pageview
                    $this->insertPageView($site_srl);
                }
            }

            $oDB->commit();
        }

        /**
         * @brief Leave logs
         **/
        function insertLog($site_srl=0) {
            $args->regdate = date("YmdHis");
            $args->user_agent = substr ($_SERVER['HTTP_USER_AGENT'], 0, 250);
            $args->site_srl = $site_srl;
            return executeQuery('counter.insertCounterLog', $args);
        }

        /**
         * @brief Register the unique visitor
         **/
        function insertUniqueVisitor($site_srl=0) {
            if($site_srl) {
				$args->regdate = '0';
                $args->site_srl = $site_srl;
                $output = executeQuery('counter.updateSiteCounterUnique', $args);
				$args->regdate = date('Ymd');
                $output = executeQuery('counter.updateSiteCounterUnique', $args);
            } else {
				$args->regdate = '0';
                $output = executeQuery('counter.updateCounterUnique', $args);
				$args->regdate = date('Ymd');
                $output = executeQuery('counter.updateCounterUnique', $args);
            }
        }

        /**
         * @brief Register pageview
         **/
        function insertPageView($site_srl=0) {
            if($site_srl) { 
				$args->regdate = '0';
                $args->site_srl = $site_srl;
                executeQuery('counter.updateSiteCounterPageview', $args);
				$args->regdate = date('Ymd');
                executeQuery('counter.updateSiteCounterPageview', $args);
            } else {
				$args->regdate = '0';
                executeQuery('counter.updateCounterPageview', $args);
				$args->regdate = date('Ymd');
                executeQuery('counter.updateCounterPageview', $args);
            }
        }

        /**
         * @brief Add the total counter status
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
         * @brief Add today's counter status
         **/
        function insertTodayStatus($regdate = 0, $site_srl=0) {
            if($regdate) $args->regdate = $regdate;
            else $args->regdate = date("Ymd");
            if($site_srl) {
                $args->site_srl = $site_srl;
                $query_id = 'counter.insertSiteTodayStatus';

                $u_args->site_srl = $site_srl; // /< when inserting a daily row, attempt to inser total rows(where regdate=0) together
                executeQuery($query_id, $u_args);
            } else {
                $query_id = 'counter.insertTodayStatus';
                executeQuery($query_id); // /< when inserting a daily row, attempt to inser total rows(where regdate=0) together
            }
            $output = executeQuery($query_id, $args);
            // Leave logs
            $this->insertLog($site_srl);
            // Register unique and pageview
            $this->insertUniqueVisitor($site_srl);
        }

        /**
         * @brief Delete counter logs of the specific virtual site
         **/
        function deleteSiteCounterLogs($site_srl) {
            $args->site_srl = $site_srl;
            executeQuery('counter.deleteSiteCounter',$args);
            executeQuery('counter.deleteSiteCounterLog',$args);
        }
    }
?>
