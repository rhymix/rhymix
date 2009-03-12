<?php
    /**
     * @class  trackbackModel
     * @author zero (zero@nzeo.com)
     * @brief  trackback 모듈의 model class
     **/

    class trackbackModel extends trackback {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 하나의 트랙백 정보를 구함
         **/
        function getTrackback($trackback_srl) {
            $args->trackback_srl = $trackback_srl;
            return executeQuery('trackback.getTrackback', $args);
        }

        /**
         * @brief document_srl 에 해당하는 엮인글의 전체 갯수를 가져옴
         **/
        function getTrackbackCount($document_srl) {
            $args->document_srl = $document_srl;
            $output = executeQuery('trackback.getTrackbackCount', $args);
            $total_count = $output->data->count;

            return (int)$total_count;
        }

        /**
         * @brief 특정 document에 특정 ip로 기록된 트랙백의 갯수
         * spamfilter 에서 사용할 method임
         **/
        function getTrackbackCountByIPAddress($document_srl, $ipaddress) {
            $args->document_srl = $document_srl;
            $args->ipaddress = $ipaddress;
            $output = executeQuery('trackback.getTrackbackCountByIPAddress', $args);
            $total_count = $output->data->count;

            return (int)$total_count;
        }

        /**
         * @brief 특정 문서에 속한 엮인글의 목록을 가져옴
         **/
        function getTrackbackList($document_srl) {
            $args->document_srl = $document_srl;
            $args->list_order = 'list_order';
            $output = executeQuery('trackback.getTrackbackList', $args);

            if(!$output->toBool()) return $output;

            $trackback_list = $output->data;

            if(!is_array($trackback_list)) $trackback_list = array($trackback_list);

            return $trackback_list;
        }

        /** 
         * @brief mid 에 해당하는 엮인글을 가져옴
         **/
        function getNewestTrackbackList($obj) {
            if($obj->mid) {
                $oModuleModel = &getModel('module');
                $obj->module_srl = $oModuleModel->getModuleSrlByMid($obj->mid);
                unset($obj->mid);
            }

            // 넘어온 module_srl은 array일 수도 있기에 array인지를 체크 
            if(is_array($obj->module_srl)) $args->module_srl = implode(',', $obj->module_srl);
            else $args->module_srl = $obj->module_srl;
            $args->list_count = $obj->list_count;
            $args->site_srl = (int)$obj->site_srl;
            $args->sort_index = 'trackbacks.list_order';
            $args->order = 'asc';

            $output = executeQueryArray('trackback.getNewestTrackbackList', $args);

            return $output;
        }
        
        /**
         * @brief 특정 모듈의 trackback 설정을 return
         **/
        function getTrackbackModuleConfig($module_srl) {
            // trackback 모듈의 config를 가져옴
            $oModuleModel = &getModel('module');
            $module_trackback_config = $oModuleModel->getModulePartConfig('trackback', $module_srl);
            if(!$module_trackback_config) {
                $trackback_config = $oModuleModel->getModuleConfig('trackback');
                $module_trackback_config->enable_trackback = $trackback_config->enable_trackback!='N'?'Y':'N';
            }
            $module_trackback_config->module_srl = $module_srl;
            return $module_trackback_config;
        }

        /**
         * @brief 정해진 시간내에 전체 엮인글 등록수를 구함
         **/
        function getRegistedTrackback($time, $ipaddress, $url, $blog_name, $title, $excerpt) {
            $obj->regdate = date("YmdHis",time()-$time);
            $obj->ipaddress = $ipaddress;
            $obj->url = $url;
            $obj->blog_name = $blog_name;
            $obj->title = $title;
            $obj->excerpt = $excerpt;
            $output = executeQuery('trackback.getRegistedTrackback', $obj);
            return $output->data->count;
        }

        /**
         * @brief trackback url을 생성하여 return
         * trackback url에 key값을 추가함.
         **/
        function getTrackbackUrl($document_srl) {
            return getUrl('','document_srl',$document_srl,'act','trackback','key',$this->getTrackbackKey($document_srl));
        }

        /**
         * @brief 키값을 생성하여 return
         * key값은 db 비번 정보 + 10분 단위의 시간값을 합쳐서 hash결과를 이용함
         * 단 url이 너무 길어져서 1, 10, 20 자리수의 글자 하나씩만을 조합해서 return
         **/
        function getTrackbackKey($document_srl) {
            $time = (int) (time()/(60*10));
            $db_info = Context::getDBInfo();
            $key = md5($document_srl.$db_info->db_password.$time);
            return sprintf("%s%s%s",substr($key,1,1),substr($key,10,1),substr($key,20,1));
        }
    }
?>
