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

            $output = executeQuery('trackback.getNewestTrackbackList', $args);

            return $output;
        }
        
        /**
         * @brief 특정 모듈의 trackback 설정을 return
         **/
        function getTrackbackModuleConfig($module_srl) {
            // trackback 모듈의 config를 가져옴
            $oModuleModel = &getModel('module');
            $trackback_config = $oModuleModel->getModuleConfig('trackback');

            $module_trackback_config = $trackback_config->module_config[$module_srl];
            if(!$module_trackback_config->module_srl) {
                $module_trackback_config->module_srl = $module_srl;
                $module_trackback_config->enable_trackback = $trackback_config->enable_trackback=='Y'?'Y':'N';
            }
            return $module_trackback_config;
        }
    }
?>
