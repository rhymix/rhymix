<?php

    /**
     * @class  refererAdminController 
     * @author haneul (haneul0318@gmail.com) 
     * @brief  referer 모듈의 admin controller class
     **/

    class refererAdminController extends referer {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        function procRefererAdminDeleteStat() {
	    $args->host = Context::get('host');
	    $output = executeQuery('referer.deleteRefererStat', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_deleted');
	}
    }
?>
