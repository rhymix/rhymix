<?php
	/**
	 * adminloggingController class
	 * controller class of adminlogging module
	 *
	 * @author NHN (developers@xpressengine.com)
	 * @package /modules/adminlogging
	 * @version 0.1
	 */
    class adminloggingController extends adminlogging {
		/**
		 * Initialization
		 * @return void
		 */
        function init() {
            // forbit access if the user is not an administrator
            $oMemberModel = &getModel('member');
            $logged_info = $oMemberModel->getLoggedInfo();
            if($logged_info->is_admin!='Y') return $this->stop("msg_is_not_administrator");
        }

		/**
		 * Insert log
		 * @return void
		 */
		function insertLog($module, $act)
		{
			if(!$module || !$act) return;

			$args->module = $module;
			$args->act = $act;
			$args->ipaddress = $_SERVER['REMOTE_ADDR'];
			$args->regdate = date('YmdHis');
			$args->requestVars = print_r(Context::getRequestVars(), true);

			$output = executeQuery('adminlogging.insertLog', $args);
		}
    }
?>
