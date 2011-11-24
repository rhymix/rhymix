<?php
    /**
     * @class  trackbackAdminModel
     * @author NHN (developers@xpressengine.com)
     * @brief trackback module admin model class
     **/

    class trackbackAdminModel extends trackback {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief Trackbacks Bringing all the time in reverse order (administrative)
         **/
        function getTotalTrackbackList($obj) {
            // Search options
            $search_target = $obj->search_target?$obj->search_target:trim(Context::get('search_target'));
            $search_keyword = $obj->search_keyword?$obj->search_keyword:trim(Context::get('search_keyword'));

            if($search_target && $search_keyword) {
                switch($search_target) {
                    case 'url' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_url = $search_keyword;
                        break;
                    case 'title' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_title= $search_keyword;
                        break;
                    case 'blog_name' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_blog_name= $search_keyword;
                        break;
                    case 'excerpt' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_excerpt = $search_keyword;
                        break;
                    case 'regdate' :
                            $args->s_regdate = $search_keyword;
                        break;
                    case 'ipaddress' :
                            $args->s_ipaddress= $search_keyword;
                        break;
                }
            }
            // Variables
            $args->sort_index = $obj->sort_index;
            $args->page = $obj->page?$obj->page:1;
            $args->list_count = $obj->list_count?$obj->list_count:20;
            $args->page_count = $obj->page_count?$obj->page_count:10;
            $args->s_module_srl = $obj->module_srl;
            $args->exclude_module_srl = $obj->exclude_module_srl;
			$args->trackbackSrlList = $obj->trackbackSrlList;
            // trackback.getTotalTrackbackList query execution
            $output = executeQuery('trackback.getTotalTrackbackList', $args);
            // Return if no result or an error occurs
            if(!$output->toBool()||!count($output->data)) return $output;

            return $output;
        }

		function getTrackbackCountByDate($date = '', $moduleSrlList = array())
		{
			if($date) $args->regDate = date('Ymd', strtotime($date));
			if(count($moduleSrlList)>0) $args->module_srl = $moduleSrlList;

            $output = executeQuery('trackback.getTrackbackCount', $args);
			if(!$output->toBool()) return 0;

			return $output->data->count;
		}
    }
?>
