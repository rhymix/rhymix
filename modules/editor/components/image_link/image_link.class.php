<?php
    /**
     * @class  image_link
     * @author zero (zero@nzeo.com)
     * @brief  이미지를 추가하거나 속성을 수정하는 컴포넌트
     **/

    class image_link extends EditorHandler { 

        // editor_sequence 는 에디터에서 필수로 달고 다녀야 함....
        var $editor_sequence = 0;
        var $component_path = '';

        /**
         * @brief editor_sequence과 컴포넌트의 경로를 받음
         **/
        function image_link($editor_sequence, $component_path) {
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
            $src = $xml_obj->attrs->src;
            $width = $xml_obj->attrs->width;
            $height = $xml_obj->attrs->height;
            $align = $xml_obj->attrs->align;
            $alt = $xml_obj->attrs->alt;
            $border = (int)$xml_obj->attrs->border;
            $link_url = $xml_obj->attrs->link_url;
            $open_window = $xml_obj->attrs->open_window;
            $style = $xml_obj->attrs->style;

            if(!$alt) {
                $tmp_arr = explode('/',$src);
                $alt = array_pop($tmp_arr);
            }

            $src = str_replace(array('&','"'), array('&amp;','&qout;'), $src);
            if(!$alt) $alt = $src;

            $attr_output = array();
            $attr_output = array("src=\"".$src."\"");
            if($alt) {
                $attr_output[] = "alt=\"".$alt."\"";
                $attr_output[] = "title=\"".$alt."\"";
            }
            if($align) $attr_output[] = "align=\"".$align."\"";

            if(preg_match("/\.png$/i",$src)) $attr_output[] = "class=\"iePngFix\"";

            if($width) $attr_output[] = 'width="'.$width.'"';
            if($height) $attr_output[] = 'height="'.$height.'"';
            if($border) $attr_output[] = 'border="'.$border.'"';

            $code = sprintf("<img %s style=\"%s\" />", implode(" ",$attr_output), $style);

            if($link_url) {
                if($open_window =='Y') $code = sprintf('<a href="%s" onclick="window.open(this.href);return false;">%s</a>', $link_url, $code);
                else $code = sprintf('<a href="%s" >%s</a>', $link_url, $code);
            }
            return $code;
        }

    }
?>
