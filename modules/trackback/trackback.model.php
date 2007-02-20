<?php
    /**
     * @class  trackbackModel
     * @author zero (zero@nzeo.com)
     * @brief  trackback 모듈의 model class
     **/

    class trackbackModel extends trackback {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 하나의 트랙백 정보를 구함
         **/
        function getTrackback($trackback_srl) {
            $oDB = &DB::getInstance();

            $args->trackback_srl = $trackback_srl;
            return $oDB->executeQuery('trackback.getTrackback', $args);
        }

        /**
         * @brief document_srl 에 해당하는 엮인글의 전체 갯수를 가져옴
         **/
        function getTrackbackCount($document_srl) {
            $oDB = &DB::getInstance();

            $args->document_srl = $document_srl;
            $output = $oDB->executeQuery('trackback.getTrackbackCount', $args);
            $total_count = $output->data->count;

            return (int)$total_count;
        }

        /**
         * @brief 특정 문서에 속한 엮인글의 목록을 가져옴
         **/
        function getTrackbackList($document_srl) {
            $oDB = &DB::getInstance();

            $args->document_srl = $document_srl;
            $args->list_order = 'list_order';
            $output = $oDB->executeQuery('trackback.getTrackbackList', $args);

            if(!$output->toBool()) return $output;

            $trackback_list = $output->data;

            if(!is_array($trackback_list)) $trackback_list = array($trackback_list);

            return $trackback_list;
        }

    }
?>
