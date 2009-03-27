<?php
    /**
     * @class  wikiModel
     * @author haneul (haneul0318@gmail.com) 
     * @brief  wiki 모듈의 Model class
     **/

    class wikiModel extends module {
        /**
         * @brief 초기화
         **/
        function init() {
        }

        function getContributors($document_srl) {
            $args->document_srl = $document_srl;
            $output = executeQueryArray("wiki.getContributors", $args);
            if(!$output->data) return array();
            return $output->data;
        }
    }
?>
