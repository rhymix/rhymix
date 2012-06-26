<?php
	/**
	 * trackbackModel class
	 * trackback module model class
	 *
	 * @author NHN (developers@xpressengine.com)
	 * @package /modules/trackback
	 * @version 0.1
	 */
    class trackbackModel extends trackback {
		/**
		 * Initialization
		 * @return void
		 */
        function init() {
        }

		/**
		 * Wanted a trackback information
		 * @param int $trackback_srl
		 * @param array $columnList
		 * @return object
		 */
        function getTrackback($trackback_srl, $columnList = array()) {
            $args->trackback_srl = $trackback_srl;
            $output = executeQuery('trackback.getTrackback', $args, $columnList);
            return $output;
        }

		/**
		 * Trackbacks document_srl corresponding to the bringing of the total number of
		 * @param int $document_srl
		 * @return int
		 */
        function getTrackbackCount($document_srl) {
            $args->document_srl = $document_srl;
            $output = executeQuery('trackback.getTrackbackCount', $args);
            $total_count = $output->data->count;

            return (int)$total_count;
        }


		/**
		 * Trackbacks module_srl corresponding to the bringing of the total number of
		 * @param int $module_srl
		 * @return int
		 */
        function getTrackbackAllCount($module_srl) {
            $args->module_srl = $module_srl;
            $output = executeQuery('trackback.getTrackbackCount', $args);
            $total_count = $output->data->count;

            return (int)$total_count;
        }


		/**
		 * For a particular document to a specific ip number of trackbacks recorded
		 * Im spamfilter method used in
		 * @param int $document_srl
		 * @param string $ipaddress
		 * @return int
		 */
        function getTrackbackCountByIPAddress($document_srl, $ipaddress) {
            $args->document_srl = $document_srl;
            $args->ipaddress = $ipaddress;
            $output = executeQuery('trackback.getTrackbackCountByIPAddress', $args);
            $total_count = $output->data->count;

            return (int)$total_count;
        }

		/**
		 * Trackbacks certain documents belonging to the bringing of the list
		 * @param int $document_srl
		 * @return array
		 */
        function getTrackbackList($document_srl) {
            $args->document_srl = $document_srl;
            $args->list_order = 'list_order';
            $output = executeQuery('trackback.getTrackbackList', $args);

            if(!$output->toBool()) return $output;

            $trackback_list = $output->data;

            if(!is_array($trackback_list)) $trackback_list = array($trackback_list);

            return $trackback_list;
        }

		/** 
		 * Bringing a mid Trackbacks
		 * @param object $obj
		 * @return object
		 */
        function getNewestTrackbackList($obj) {
            if($obj->mid) {
                $oModuleModel = &getModel('module');
                $obj->module_srl = $oModuleModel->getModuleSrlByMid($obj->mid);
                unset($obj->mid);
            }
            // Module_srl passed the array may be a check whether the array
            if(is_array($obj->module_srl)) $args->module_srl = implode(',', $obj->module_srl);
            else $args->module_srl = $obj->module_srl;
            $args->list_count = $obj->list_count;
            if($obj->site_srl) $args->site_srl = (int)$obj->site_srl;
            $args->sort_index = 'trackbacks.list_order';
            $args->order = 'asc';

            $output = executeQueryArray('trackback.getNewestTrackbackList', $args);

            return $output;
        }
        
		/**
		 * Return to a specific set of modules trackback
		 * @param int $module_srl
		 * @return object
		 */
        function getTrackbackModuleConfig($module_srl) {
            // Bringing trackback module config
            $oModuleModel = &getModel('module');
            $module_trackback_config = $oModuleModel->getModulePartConfig('trackback', $module_srl);
            if(!$module_trackback_config) {
                $trackback_config = $oModuleModel->getModuleConfig('trackback');
                $module_trackback_config->enable_trackback = $trackback_config->enable_trackback!='N'?'Y':'N';
            }
            $module_trackback_config->module_srl = $module_srl;
            return $module_trackback_config;
        }

		/**
		 * Fixed in time for the entire yeokingeul Wanted to Register
		 * @param int $time
		 * @param string $ipaddress
		 * @param string $url
		 * @param string $blog_name
		 * @param string $title
		 * @param string excerpt
		 * @return int
		 */
        function getRegistedTrackback($time, $ipaddress, $url, $blog_name, $title, $excerpt) {
            $obj->regdate = date("YmdHis",time()-$time);
            $obj->ipaddress = $ipaddress;
            $obj->url = $url;
            $obj->blog_name = $blog_name;
            $obj->title = $title;
            $obj->excerpt = $excerpt;
            $output = executeQuery('trackback.getRegistedTrackback', $obj);
            return $output->data->count;
        }

		/**
		 * Return by creating a trackback url
		 * Adds the key value in the trackback url.
		 * @param int $document_srl
		 * @return string
		 */
        function getTrackbackUrl($document_srl) {
            $url = getFullUrl('','document_srl',$document_srl,'act','trackback','key',$this->getTrackbackKey($document_srl));
            return $url;
        }

		/**
		 * Return keys by generating
		 * db key value information, plus a 10 minute off-duty time together and hash values and deal with the results
		 * So was extended only url, 1, 10, 20-digit combination of letters only, one return
		 * @param int $document_srl
		 * @return string
		 */
        function getTrackbackKey($document_srl) {
            $time = (int) (time()/(60*10));
            $db_info = Context::getDBInfo();
            $key = md5($document_srl.$db_info->db_password.$time);
            return sprintf("%s%s%s",substr($key,1,1),substr($key,10,1),substr($key,20,1));
        }
    }
?>
