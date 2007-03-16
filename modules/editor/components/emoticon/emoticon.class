<?php
    /**
     * @class  colorpicker_bg
     * @author zero (zero@nzeo.com)
     * @brief  에디터에서 url링크하는 기능 제공. 단순 팝업.
     **/

    class colorpicker_bg extends EditorHandler { 

        // upload_target_srl 는 에디터에서 필수로 달고 다녀야 함....
        var $upload_target_srl = 0;
        var $component_path = '';

        /**
         * @brief upload_target_srl과 컴포넌트의 경로를 받음
         **/
        function colorpicker_bg($upload_target_srl, $component_path) {
            $this->upload_target_srl = $upload_target_srl;
            $this->component_path = $component_path;
        }

        /**
         * @brief 에디터에서 처음 요청을 받을 경우 실행이 되는 부분이다.
         * execute의 경우 2가지 경우가 생긴다.
         * 직접 에디터 아래의 component area로 삽입할 html 코드를 만드는 것과 popup 윈도우를 띄우는 것인데
         * popup윈도우를 띄울 경우는 getPopupContent() 이라는 method가 실행이 되니 구현하여 놓아야 한다
         **/
        function execute() {

            $url = sprintf('./?module=editor&act=dispPopup&target_srl=%s&component=colorpicker_bg', $this->upload_target_srl);
            
            $this->add('tpl', '');
            $this->add('open_window', 'Y');
            $this->add('popup_url', $url);
        }

        /**
         * @brief popup window요청시 다시 call이 될 method. popup window에 출력할 내용을 추가하면 된다
         **/
        function getPopupContent() {
            // 템플릿을 미리 컴파일해서 컴파일된 소스를 return
            $tpl_path = $this->component_path.'tpl';
            $tpl_file = 'popup.html';

            Context::set("tpl_path", $tpl_path);

            require_once("./classes/template/TemplateHandler.class.php");
            $oTemplate = new TemplateHandler();
            return $oTemplate->compile($tpl_path, $tpl_file);
        }

    }
?>
