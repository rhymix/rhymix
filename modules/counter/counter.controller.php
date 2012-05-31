<?php
    /**
     * Counter module's controller class
	 *
	 * @author NHN (developers@xpressengine.com)
     **/

    class counterController extends counter {

        /**
         * Initialization
		 *
		 * @return void
         **/
        function init() {
        }

        /**
         * Counter logs.
		 * If want use below function, you can use 'counterExecute' function instead this function
		 *
		 * @return void
         **/
        function procCounterExecute() {
        }

        /**
         * Counter logs
		 *
		 * @return void
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
         * Leave logs
		 *
		 * @param integer $site_srl
		 * @return Object result of count query
         **/
        function insertLog($site_srl=0) {
            $args->regdate = date("YmdHis");
            $args->user_agent = substr ($_SERVER['HTTP_USER_AGENT'], 0, 250);
            $args->site_srl = $site_srl;
            return executeQuery('counter.insertCounterLog', $args);
        }

        /**
         * Register the unique visitor
		 *
		 * @param integer $site_srl
		 * @return void
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
         * Register pageview
		 *
		 * @param integer $site_srl
		 * @return void
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
         * Add the total counter status
		 *
		 * @param integer $site_srl
		 * @return void
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
         * Add today's counter status
		 *
		 * @param integer $regdate date(YYYYMMDD) type
		 * @param integer $site_srl
		 * @return void
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
         * Delete counter logs of the specific virtual site
		 *
		 * @param integer $site_srl
		 * @return void
         **/
        function deleteSiteCounterLogs($site_srl) {
            $args->site_srl = $site_srl;
            executeQuery('counter.deleteSiteCounter',$args);
            executeQuery('counter.deleteSiteCounterLog',$args);
        }
    }
?>
