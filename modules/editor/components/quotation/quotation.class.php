<?php
    /**
     * @class  quotation
     * @author zero (zero@nzeo.com)
     * @brief  에디터에서 인용문 기능 제공. 
     **/

    class quotation extends EditorHandler { 

        // editor_sequence 는 에디터에서 필수로 달고 다녀야 함....
        var $editor_sequence = 0;
        var $component_path = '';

        /**
         * @brief editor_sequence과 컴포넌트의 경로를 받음
         **/
        function quotation($editor_sequence, $component_path) {
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
            $use_folder = $xml_obj->attrs->use_folder;
            $folder_opener = $xml_obj->attrs->folder_opener;
            if(!$folder_opener) $folder_opener = "more...";
            $folder_closer = $xml_obj->attrs->folder_closer;
            if(!$folder_closer) $folder_closer= "close...";
            $bold = $xml_obj->attrs->bold;
            $color = $xml_obj->attrs->color;
            $margin = $xml_obj->attrs->margin;
            $padding = $xml_obj->attrs->padding;
            $border_style = $xml_obj->attrs->border_style;
            $border_thickness = $xml_obj->attrs->border_thickness;
            $border_color = $xml_obj->attrs->border_color;
            $bg_color = $xml_obj->attrs->bg_color;
            $body = $xml_obj->body;

            $output = "";
            $style = sprintf('margin:%spx;padding:%spx;background-color:#%s;', $margin, $padding, $bg_color);
            switch($border_style) {
                case "solid" :
                        $style .= "border:".$border_thickness."px solid #".$border_color.";";
                    break;
                case "dotted" :
                        $style .= "border:".$border_thickness."px dotted #".$border_color.";";
                    break;
                case "left_solid" :
                        $style .= "border-left:".$border_thickness."px solid #".$border_color.";";
                    break;
                case "left_dotted" :
                        $style .= "border-elft:".$border_thickness."px dotted #".$border_color.";";
                    break;
            }

            if($use_folder == "Y") {
                $folder_id = rand(1000000,9999999);

                if($bold == "Y") $class = "bold";
                switch($color) {
                    case "red" :
                            $class .= " editor_red_text";
                        break;
                    case "yellow" :
                            $class .= " editor_yellow_text";
                        break;
                    case "green" :
                            $class .= " editor_green_text";
                        break;
                    default :
                            $class .= " editor_blue_text";
                        break;
                }

                $style .= "display:none;";

                $folder_margin = sprintf("%spx %spx %spx %spx", $margin, $margin, 10, $margin);
                $output .= sprintf('<div id="folder_open_%s" style="margin:%s;display:block;"><a class="%s" href="#" onclick="zbxe_folder_open(\'%s\');return false;">%s</a></div>', $folder_id, $folder_margin, $class, $folder_id, $folder_opener);
                $output .= sprintf('<div id="folder_close_%s" style="margin:%s;display:none;"><a class="%s" href="#" onclick="zbxe_folder_close(\'%s\');return false;">%s</a></div>', $folder_id, $folder_margin, $class, $folder_id, $folder_closer);

                $output .= sprintf('<div style="%s" id="folder_%s">%s</div>', $style, $folder_id,$body);
            } else {
                $output .= sprintf('<div style="%s">%s</div>', $style, $body);
            }
            return $output;
        }

    }
?>
