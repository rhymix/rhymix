<?php
    /**
    * @class EditorHandler
    * @author NHN (developers@xpressengine.com)
    * @brief superclass of the edit component
    *
    * set up the component variables
    **/

    class EditorHandler extends Object {

        /**
         * set the xml and other information of the component
		 * @param object $info editor information
		 * @return void
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
