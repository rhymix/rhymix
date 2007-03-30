<?php
    /**
    * @class EditorHandler
    * @author zero (zero@nzeo.com)
    * @brief edit component의 상위 클래스임
    *
    * 주로 하는 일은 컴포넌트 요청시 컴포넌트에서 필요로 하는 변수를 세팅해준다
    **/

    class EditorHandler extends Object {

        /**
         * @brief 컴포넌트의 xml및 관련 정보들을 설정
         **/
        function setInfo($info) {
            Context::set('component_info', $info);

            if(!$info->extra_vars) return;

            foreach($info->extra_vars as $key => $val) {
                $this->{$key} = trim($val->value);
            }
        }

    }

?>
