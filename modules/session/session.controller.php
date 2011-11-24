<?php
    /**
     * @class  sessionController
     * @author NHN (developers@xpressengine.com)
     * @brief The controller class of the session module
     **/

    class sessionController extends session {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        function open() {
            return true;
        }

        function close() {
            return true;
        }

        function write($session_key, $val) {
            if(!$session_key || !$this->session_started) return;
       		$oCacheHandler = &CacheHandler::getInstance('object');
            if($oCacheHandler->isSupport()) {
            	$cache_key = 'object:'.$session_key;
            	$cache_vars = $oCacheHandler->get($cache_key);
            }
            
            $args->session_key = $session_key;
            if($cache_vars) $session_info = $cache_vars;
             else {
             	$output = executeQuery('session.getSession', $args);
            	$session_info = $output->data;
             }
            //if ip has changed delete the session from cache and db
            if($session_info->session_key == $session_key && $session_info->ipaddress != $_SERVER['REMOTE_ADDR']) {
            	if($oCacheHandler->isSupport())  $oCacheHandler->delete($cache_key);
                executeQuery('session.deleteSession', $args);
                return true;
            }

            $args->expired = date("YmdHis", time()+$this->lifetime);
            $args->val = $val;
            $args->cur_mid = Context::get('mid');
            if(!$args->cur_mid) {
                $module_info = Context::get('current_module_info');
                $args->cur_mid = $module_info->mid;
            }

            if(Context::get('is_logged')) {
                $logged_info = Context::get('logged_info');
                $args->member_srl = $logged_info->member_srl;
            } else {
                $args->member_srl = 0;
            }
            $args->ipaddress = $_SERVER['REMOTE_ADDR'];
            $args->last_update = date("YmdHis", time());
            $diff = $args->last_update - $cache_vars->last_update;
        	//verify if session values have changed
            if($val == $cache_vars->val){
            	// if more than 5 minutes passed than modify the db session also
            	if($diff > 300){
            		//put session into cache
		            if($oCacheHandler->isSupport()) {
		            	$cache_key = 'object:'.$session_key;
		            	$oCacheHandler->put($cache_key,$args);
		            }
		            //put session into db
		            if($session_info->session_key) $output = executeQuery('session.updateSession', $args);
            	}
            	else {
            		//put session into cache
		            if($oCacheHandler->isSupport()) {
		            	$cache_key = 'object:'.$session_key;
		            	$oCacheHandler->put($cache_key,$args);
		            }
            	}
            }
            else {
            		//put session into cache
		            if($oCacheHandler->isSupport()) {
		            	$cache_key = 'object:'.$session_key;
		            	$oCacheHandler->put($cache_key,$args);
		            }
		            //put session into db
		            if($session_info->session_key) $output = executeQuery('session.updateSession', $args);
		            else $output = executeQuery('session.insertSession', $args);
            }
			
            
            return true;
        }

        function destroy($session_key) {
            if(!$session_key || !$this->session_started) return;
            //remove session from cache
        	$oCacheHandler = &CacheHandler::getInstance('object');
            if($oCacheHandler->isSupport()) {
            	$cache_key = 'object:'.$session_key;
            	$oCacheHandler->delete($cache_key);
            }
            //remove session from db
            $args->session_key = $session_key;
            executeQuery('session.deleteSession', $args);
            return true;
        }

        function gc($maxlifetime) {
            if(!$this->session_started) return;
            $expired_sessions = executeQueryArray('session.getExpiredSessions');
            if($expired_session){
            	foreach ($expired_sessions as $session_key){
            	//remove session from cache
		        	$oCacheHandler = &CacheHandler::getInstance('object');
		            if($oCacheHandler->isSupport()) {
		            	$cache_key = 'object:'.$session_key;
		            	$oCacheHandler->delete($cache_key);
		            }
            	}
            }
            executeQuery('session.gcSession');
            return true;
        }
    }
?>
