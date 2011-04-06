<?php
    /**
     * @class  sessionAdminController
     * @author NHN (developers@xpressengine.com)
     * @brief The admin controller class of the session module
     **/

    class sessionAdminController extends session {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief The action to clean up the Derby session
         **/
        function procSessionAdminClear() {
            $oSessionController = &getController('session');
            $oSessionController->gc(0);

            $this->add('result',Context::getLang('session_cleared'));
        }
    }
?>
