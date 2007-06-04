<?php
    /**
     * @class  pollAdminModel
     * @author zero (zero@nzeo.com)
     * @brief  poll 모듈의 admin model class
     **/

    class pollAdminModel extends poll {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 설문 목록 구해옴
         **/
        function getPollList($args) {
            $output = executeQuery('poll.getPollList', $args);
            if(!$output->toBool()) return $output;

            if($output->data && !is_array($output->data)) $output->data = array($output->data);
            return $output;
        }

    }
?>
