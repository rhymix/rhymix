<?php
    /**
     * @class  tagModel
     * @author zero (zero@nzeo.com)
     * @brief  tag 모듈의 model class
     **/

    class tagModel extends tag {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 태그 목록을 가져옴
         * 지정된 모듈의 태그를 개수가 많은 순으로 추출
         **/
        function getTagList($obj) {
            if($obj->mid) {
                $oModuleModel = &getModel('module');
                $obj->module_srl = $oModuleModel->getModuleSrlByMid($obj->mid);
                unset($obj->mid);
            }

            // 넘어온 module_srl은 array일 수도 있기에 array인지를 체크 
            if(is_array($obj->module_srl)) $args->module_srl = implode(',', $obj->module_srl);
            else $args->module_srl = $obj->module_srl;
            $args->list_count = $obj->list_count;

            $output = executeQueryArray('tag.getTagList', $args);
            if(!$output->toBool()) return $output;

            return $output;
        }
    }
?>
