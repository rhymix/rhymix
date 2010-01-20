<?php
    /**
     * @class  pageAPI
     * @author sol(ngleader@gmail.com)
     * @brief  page 모듈의 View Action에 대한 API 처리
     **/

    class pageAPI extends page {

        /**
         * @brief 페이지 내용
         **/
        function dispPageIndex(&$oModule) {
			$page_content = Context::get('page_content');
			$oWidgetController = &getController('widget');

			$requestMethod = Context::getRequestMethod();
			Context::setResponseMethod('HTML');
			$oWidgetController->triggerWidgetCompile(&$page_content);
			Context::setResponseMethod($requestMethod);

            $oModule->add('page_content',$page_content);
        }
    }
?>
