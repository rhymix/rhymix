<?php
    /**
     * @class  quotation
     * @author zero (zero@nzeo.com)
     * @brief  에디터에서 인용문 기능 제공. 단순 팝업.
     **/

    class quotation extends EditorHandler { 

        // upload_target_srl 는 에디터에서 필수로 달고 다녀야 함....
        var $upload_target_srl = 0;
        var $component_path = '';

        /**
         * @brief upload_target_srl과 컴포넌트의 경로를 받음
         **/
        function quotation($upload_target_srl, $component_path) {
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

            $url = sprintf('./?module=editor&act=dispPopup&target_srl=%s&component=quotation', $this->upload_target_srl);
            
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
                $output .= sprintf('<div id="folder_open_%s" style="margin:%spx;display:block;"><a class="%s" href="#" onclick="zbxe_folder_open(\'%s\');return false;">%s</a></div>', $folder_id, $margin, $class, $folder_id, $folder_opener);
                $output .= sprintf('<div id="folder_close_%s" style="margin:%spx;display:none;"><a class="%s" href="#" onclick="zbxe_folder_close(\'%s\');return false;">%s</a></div>', $folder_id, $margin, $class, $folder_id, $folder_closer);

                $output .= sprintf('<div style="%s" id="folder_%s">', $style, $folder_id);
            } else {
                $output .= sprintf('<div style="%s">', $style);
            }
            return $output;
        }

    }
?>
