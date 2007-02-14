<?php
    /**
     * @class Output
     * @author zero (zero@nzeo.com)
     * @brief 결과 데이터를 담당하는 class
     *
     * 모듈이나 DB등의 모든 행위의 결과를 담당.\n
     * 에러 코드를 보관하고 기타 추가 정보가 있을 시에 add/get을 통해\n
     * parameter를 넘길때도 사용....\n
     *
     * @todo 설명이 영.. 안 좋음.. 수정 요망! 
     * @todo result 객체로 사용하면 되는데 왠지 구조적으로 덜 다듬어졌음. 차후 다듬어야 함
     **/

    class Output {

        var $template_path = NULL; ///< template path 지정
        var $template_file = NULL; ///< template 파일 지정

        var $error = 0; ///< 에러 코드 (0이면 에러 아님)
        var $message = 'success'; ///< 에러 메세지 (success이면 에러 아님)

        var $variables = array(); ///< 추가 변수

        /**
         * @brief constructor
         **/
        function Output($error = 0, $message = 'success') {
            $this->error = $error;
            $this->message = $message;
        }

        /**
         * @brief error 코드를 지정
         **/
        function setError($error = 0) {
            $this->error = $error;
        }

        /**
         * @brief error 코드를 return
         **/
        function getError() {
        return $this->error;
        }

        /**
         * @brief 에러 메세지 지정
         **/
        function setMessage($message = 'success') {
            if(Context::getLang($message)) $message = Context::getLang($message);
            $this->message = $message;
            return true;
        }

        /**
         * @brief 에러 메세지 return
         **/
        function getMessage() {
            return $this->message;
        }

        /**
         * @brief 추가 변수
         **/
        function add($key, $val) {
            $this->variables[$key] = $val;
        }

        /**
         * @brief 추가된 변수의 key에 해당하는 값을 return
         **/
        function get($key) {
            return $this->variables[$key];
        }

        /**
         * @brief 추가변수 전체 return
         **/
        function getVariables() {
            return $this->variables;
        }

        /**
         * @brief error값이 0이 아니면 오류
         **/
        function toBool() {
            return $this->error==0?true:false;
        }

        /**
         * @brief error값이 0이 아니면 오류 (Output::toBool()의 aliasing)
         **/
        function toBoolean() {
        return $this->toBool();
        }

        /**
         * @brief tpl 경로을 지정
         **/
        function setTemplatePath($path) {
            if(!substr($path,-1)!='/') $path .= '/';
            $this->template_path = $path;
        }

        /**
         * @brief tpl 경로를 return
         **/
        function getTemplatePath() {
            return $this->template_path;
        }

        /**
         * @brief tpl 파일을 지정
         **/
        function setTemplateFile($filename) {
            $this->template_file = $filename;
        }

        /**
         * @brief tpl 파일을 지정
         **/
        function getTemplateFile() {
            return $this->template_file;
        }
    }
?>
