<?php
    /**
     * @class  rssModel
     * @author NHN (developers@xpressengine.com)
     * @brief  rss module의 model class
     *
     * Feed 문서 출력
     *
     **/

    class rssModel extends rss {
        /**
         * @brief Feed url 생성.
         **/
        function getModuleFeedUrl($vid = null, $mid, $format) {
            if(Context::isAllowRewrite()) {
                $request_uri = Context::getRequestUri();
                // 가상 사이트 변수가 있고 이 변수가 mid와 다를때. (vid와 mid는 같을 수 없다고 함)
                if($vid && $vid != $mid) {
                    return $request_uri.$vid.'/'.$mid.'/'.$format;
                }
                else {
                    return $request_uri.$mid.'/'.$format;
                }
            }
            else {
                return getUrl('','mid',$mid,'act',$format);
            }
        }


        /**
         * @brief 특정 모듈의 rss 설정을 return
         **/
        function getRssModuleConfig($module_srl) {
            // rss 모듈의 config를 가져옴
            $oModuleModel = &getModel('module');
            $module_rss_config = $oModuleModel->getModulePartConfig('rss', $module_srl);
            if(!$module_rss_config) $module_rss_config->open_rss = 'N';
            $module_rss_config->module_srl = $module_srl;
            return $module_rss_config;
        }
    }
?>
