<?php
    /**
     * @class  spamfilterView
     * @author zero (zero@nzeo.com)
     * @brief  spamfilter 모듈의 View class
     **/

    class spamfilterView extends spamfilter {

        /**
         * @brief 초기화
         **/
        function init() {
            $this->setTemplatePath($this->module_path.'tpl.admin');
        }

        /**
         * @brief 관리자 모드에서 보여줄 화면
         **/
        function dispContent() {
            print 11;
        }


    }
?>
