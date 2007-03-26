<?php
    /**
    * @class EditorHandler
    * @author zero (zero@nzeo.com)
    * @brief addon을 호출하여 실행
    **/

    class EditorHandler extends Object {

        /**
         * @brief 컴포넌트의 xml및 관련 정보들을 설정
         **/
        function setInfo($info) {
            Context::set('component_info', $info);

            if($info->extra_vars) {
                foreach($info->extra_vars as $key => $val) {
                    $this->{$key} = trim($val->value);
                }
            }
        }

    }

?>
