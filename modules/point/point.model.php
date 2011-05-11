<?php
    /**
     * @class  pointModel
     * @author NHN (developers@xpressengine.com)
     * @brief The model class fo the point module
     **/

    class pointModel extends point {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief Check if there is points information
         **/
        function isExistsPoint($member_srl) {
            $member_srl = abs($member_srl);
            $args->member_srl = $member_srl;
            $output = executeQuery('point.getPoint', $args);
            if($output->data->member_srl == $member_srl) return true;
            return false;
        }

        /**
         * @brief Get the points
         **/
        function getPoint($member_srl, $from_db = false) {
            $member_srl = abs($member_srl);
            $path = sprintf('./files/member_extra_info/point/%s',getNumberingPath($member_srl));
            if(!is_dir($path)) FileHandler::makeDir($path);
            $cache_filename = sprintf('%s%d.cache.txt', $path, $member_srl);

            if(!$from_db && file_exists($cache_filename)) return trim(FileHandler::readFile($cache_filename));
            // Get from the DB
            $args->member_srl = $member_srl;
            $output = executeQuery('point.getPoint', $args);
            $point = (int)$output->data->point;

            FileHandler::writeFile($cache_filename, $point);

            return $point;
        }

        /**
         * @brief Get the level
         **/
        function getLevel($point, $level_step) {
            $level_count = count($level_step);
            for($level=0;$level<=$level_count;$level++) if($point < $level_step[$level]) break;
            $level --;
            return $level;
        }

		function getMembersPointInfo()
		{
			$member_srls = Context::get('member_srls');
			$member_srls = explode(',',$member_srls);
			if(count($member_srls)==0) return;
			array_unique($member_srls);

			$oModuleModel = &getModel('module');
			$config = $oModuleModel->getModuleConfig('point');

			$info = array();
			foreach($member_srls as $v)
			{
				$obj = new stdClass;
				$obj->point = $this->getPoint($v);
				$obj->level = $this->getLevel($obj->point, $config->level_step);
				$obj->member_srl = $v;
				$info[] = $obj;
			}

			$this->add('point_info',$info);
		}


        /**
         * @brief Get a list of points members list
         **/
        function getMemberList($args = null, $columnList = array()) {
            // Arrange the search options
            $args->is_admin = Context::get('is_admin')=='Y'?'Y':'';
            $args->is_denied = Context::get('is_denied')=='Y'?'Y':'';
            $args->selected_group_srl = Context::get('selected_group_srl');

            $search_target = trim(Context::get('search_target'));
            $search_keyword = trim(Context::get('search_keyword'));

            if($search_target && $search_keyword) {
                switch($search_target) {
                    case 'user_id' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_user_id = $search_keyword;
                        break;
                    case 'user_name' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_user_name = $search_keyword;
                        break;
                    case 'nick_name' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_nick_name = $search_keyword;
                        break;
                    case 'email_address' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_email_address = $search_keyword;
                        break;
                    case 'regdate' :
                            $args->s_regdate = $search_keyword;
                        break;
                    case 'last_login' :
                            $args->s_last_login = $search_keyword;
                        break;
                    case 'extra_vars' :
                            $args->s_extra_vars = $search_keyword;
                        break;
                }
            }
            // If there is a selected_group_srl, change the "query id" (for table join)
            if($args->selected_group_srl) {
                $query_id = 'point.getMemberListWithinGroup';
            } else {
                $query_id = 'point.getMemberList';
            }

            $output = executeQuery($query_id, $args, $columnList);

            if($output->total_count) {
                $oModuleModel = &getModel('module');
                $config = $oModuleModel->getModuleConfig('point');

                foreach($output->data as $key => $val) {
                    $output->data[$key]->level = $this->getLevel($val->point, $config->level_step);
                }
            }

            return $output;
        }
    }
?>
