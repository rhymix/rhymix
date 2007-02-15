<?php
    /**
    * @class LayoutHandler
    * @author zero (zero@nzeo.com)
    * @brief 레이아웃 관리
    * @todo 미구현
    **/

    class LayoutHandler extends Handler {

        var $layout_name; ///< 레이아웃의 이름
        var $layout_info; ///< 레이아웃의 정보

        /**
         * @brief 레이아웃 정보를 구함
         **/
        function callLayout(&$oModule, $oModuleInfo) {
            if($oModule->getActType() != 'disp') return;
            if(!$oModuleInfo->isLayoutExists()) return;

            $oLayout = new LayoutHandler();
            $oLayout->proc($oModule, $oModuleInfo);
        }

        /**
         * @brief 레이아웃 template을 실행하여 html코드로 변환
         **/
        function proc(&$oModule, $oModuleInfo) {
            $this->layout_info = $oModuleInfo->getLayout();
            $this->layout_name = $this->layout_info->layout_name;
            $this->layout_name = 'test';

            // 해당 모듈을 읽어서 객체를 만듬
            $layout_file = sprintf('./layouts/%s/%s.layout.php', $this->layout_name, $this->layout_name);

            // 모듈 파일이 없으면 에러
            if(!file_exists($layout_file)) return;

            // 모듈 파일을 include
            require_once($layout_file);

            // 선택된 모듈의 instance는 eval string으로 처리
            $eval_str = sprintf('$oLayout = new %s();', $this->layout_name);
            eval($eval_str); 

            // 애드온 실행
            $oLayout->proc($oModule, $oModuleInfo);
        }
    }
?>
