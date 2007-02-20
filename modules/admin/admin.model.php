<?php
    /**
     * @class  adminModel
     * @author zero (zero@nzeo.com)
     * @brief  admin 모듈의 model class
     **/

    class adminModel extends admin {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 레이아웃 경로를 return
         **/
        function getLayoutPath() {
            return $this->template_path;
        }

        /**
         * @brief 레이아웃 파일을 return
         **/
        function getLayoutTpl() {
            return "layout.html";
        }
    }
?>
