<?php
    /**
     * @class  editorView
     * @author zero (zero@nzeo.com)
     * @brief  editor 모듈의 view 클래스
     **/

    class editorView extends editor {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 에디터를 return
         **/
        function getEditor($upload_target_srl, $allow_fileupload = false) {
            Context::set('upload_target_srl', $upload_target_srl);
            Context::set('allow_fileupload', $allow_fileupload);

            $tpl_path = $this->module_path.'tpl';
            $tpl_file = 'editor.html';

            require_once("./classes/template/TemplateHandler.class.php");
            $oTemplate = new TemplateHandler();
            return $oTemplate->compile($tpl_path, $tpl_file);
        }

        /**
         * @brief 팝업 출력 출력
         **/
        function dispPopup() {
            $this->setTemplatePath($this->module_path.'tpl/popup');
            $this->setTemplateFile('emoticon.html');
        }

    }
?>
