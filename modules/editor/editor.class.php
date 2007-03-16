<?php
    /**
     * @class  editor
     * @author zero (zero@nzeo.com)
     * @brief  editor 모듈의 high class
     **/

    class editor extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function moduleIsInstalled() {
            return new Object();
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            return new Object();
        }

        /**
         * @brief component의 객체 생성
         **/
        function getComponentObject($component, $upload_target_srl) {
            // 해당 컴포넌트의 객체를 생성해서 실행
            $class_file = sprintf('%scomponents/%s/%s.class.php', $this->module_path, $component, $component);
            if(!file_exists($class_file)) return new Object(-1, sprintf(Context::getLang('msg_component_is_not_founded'), $component));

            require_once($class_file);
            $eval_str = sprintf('$oComponent = new %s("%s");', $component, $upload_target_srl);
            @eval($eval_str);
            if(!$oComponent) return new Object(-1, sprintf(Context::getLang('msg_component_is_not_founded'), $component));

            return $oComponent;
        }
    }
?>
