<?php
    /**
     * @class Object
     * @author zero (zero@nzeo.com)
     * @brief 모듈간의 데이터를 주고 받기 위한 클래스
     *
     * 모든 모듈은 Object를 상속하며 Object의 error, message, variables 를 이용하여 통신을 하게 된다
     **/

    class Object {

        var $error = 0; ///< 에러 코드 (0이면 에러 아님)
        var $message = 'success'; ///< 에러 메세지 (success이면 에러 아님)

        var $variables = array(); ///< 추가 변수

        /**
         * @brief constructor
         **/
        function Object($error = 0, $message = 'success') {
            $this->setError($error);
            $this->setMessage($message);
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
         * @brief error값이 0이 아니면 오류 (Object::toBool()의 aliasing)
         **/
        function toBoolean() {
        return $this->toBool();
        }

    }
?>
