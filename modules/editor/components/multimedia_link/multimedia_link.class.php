<?php
    /**
     * @class  multimedia_link
     * @author zero (zero@nzeo.com)
     * @brief  본문에 멀티미디어 자료를 연결하는 컴포넌트
     **/

    class multimedia_link extends EditorHandler { 

        // editor_sequence 는 에디터에서 필수로 달고 다녀야 함....
        var $editor_sequence = 0;
        var $component_path = '';

        /**
         * @brief editor_sequence과 컴포넌트의 경로를 받음
         **/
        function multimedia_link($editor_sequence, $component_path) {
            $this->editor_sequence = $editor_sequence;
            $this->component_path = $component_path;
        }

        /**
         * @brief popup window요청시 popup window에 출력할 내용을 추가하면 된다
         **/
        function getPopupContent() {
            // 템플릿을 미리 컴파일해서 컴파일된 소스를 return
            $tpl_path = $this->component_path.'tpl';
            $tpl_file = 'popup.html';

            Context::set("tpl_path", $tpl_path);

            $oTemplate = &TemplateHandler::getInstance();
            return $oTemplate->compile($tpl_path, $tpl_file);
        }

        /**
         * @brief 에디터 컴포넌트가 별도의 고유 코드를 이용한다면 그 코드를 html로 변경하여 주는 method
         *
         * 이미지나 멀티미디어, 설문등 고유 코드가 필요한 에디터 컴포넌트는 고유코드를 내용에 추가하고 나서
         * DocumentModule::transContent() 에서 해당 컴포넌트의 transHtml() method를 호출하여 고유코드를 html로 변경
         **/
        function transHTML($xml_obj) {
            $src = $xml_obj->attrs->multimedia_src;
            $style = $xml_obj->attrs->style;

            preg_match_all('/(width|height)([^[:digit:]]+)([0-9]+)/i',$style,$matches);
            $width = trim($matches[3][0]);
            $height = trim($matches[3][1]);
            if(!$width) $width = 400;
            if(!$height) $height = 400;

            $auto_start = $xml_obj->attrs->auto_start;
            if($auto_start!="true") $auto_start = "false";
            else $auto_start = "true";

            $caption = $xml_obj->body;

            $src = str_replace(array('&','"'), array('&amp;','&qout;'), $src);

            return sprintf("<div><script type=\"text/javascript\">displayMultimedia(\"%s\", \"%s\",\"%s\", { autostart : %s });</script></div>", $src, $width, $height, $auto_start);
        }
    }
?>
