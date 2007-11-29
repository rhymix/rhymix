<?php
    /**
     * @class refererModel
     * @author haneul (haneul0318@gmail.com)
     * @brief referer 모듈의 Model class
     **/

    class refererModel extends referer {
        function init() {
        }

        function isInsertedHost($host) {
            $args->host = $host;
            $output = executeQuery('referer.getHostStatus', $args);
            return $output->data->count?true:false;
        }

        function getLogList($obj) {
            $query_id = 'referer.getRefererLogList';

            $args->sort_index = 'regdate';
            $args->page = $obj->page?$obj->page:1;

            $args->list_count = $obj->list_count?$obj->list_count:20;
            $args->page_count = $obj->page_count?$obj->page_count:10;

            $output = executeQuery($query_id, $args);

            // 결과가 없거나 오류 발생시 그냥 return
            if(!$output->toBool()||!count($output->data)) return $output;

            return $output;
        }

        function getRefererStatus() {
            $args->sort_index = 'count'; 
            return executeQuery("referer.getRefererStatistics", $args);
        }

    }
?>
