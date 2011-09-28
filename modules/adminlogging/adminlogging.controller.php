<?php
    /**
     * @class  adminAdminController
     * @author NHN (developers@xpressengine.com)
     * @brief  admin controller class of admin module
     **/

    class adminloggingController extends admin {
        /**
         * @brief initialization
         * @return none
         **/
        function init() {
            // forbit access if the user is not an administrator
            $oMemberModel = &getModel('member');
            $logged_info = $oMemberModel->getLoggedInfo();
            if($logged_info->is_admin!='Y') return $this->stop("msg_is_not_administrator");
        }

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
