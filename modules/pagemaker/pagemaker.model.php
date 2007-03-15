<?php
    /**
     * @class  pagemakerModel
     * @author zero (zero@nzeo.com)
     * @brief  pagemaker 모듈의 model class
     **/

    class pagemakerModel extends pagemaker {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief pagemaker로 등록된 module_srl을 찾아서 return
         * pagemaker의 내용은 게시판처럼 document에 저장이 된다.
         * 설치시에 pagemaker 모듈을 modules에 등록을 하며 이 module_srl은 sequence값으로 입력되기에 찾아야한다.
         **/
        function getModuleSrl() {
            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByMid('pagemaker');
            $module_srl = $module_info->module_srl;
            return $module_srl;
        }

    }
?>
