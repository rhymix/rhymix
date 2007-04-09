<?php
    /**
     * @class  image_gallery
     * @author zero (zero@nzeo.com)
     * @brief  업로드된 이미지로 이미지갤러리를 만듬
     **/

    class image_gallery extends EditorHandler { 

        // upload_target_srl 는 에디터에서 필수로 달고 다녀야 함....
        var $upload_target_srl = 0;
        var $component_path = '';

        /**
         * @brief upload_target_srl과 컴포넌트의 경로를 받음
         **/
        function image_gallery($upload_target_srl, $component_path) {
            $this->upload_target_srl = $upload_target_srl;
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
            $gallery_info->srl = rand(111111,999999);
            $gallery_info->border_thickness = $xml_obj->attrs->border_thickness;
            $gallery_info->gallery_style = $xml_obj->attrs->gallery_style;
            $gallery_info->border_color = $xml_obj->attrs->border_color;
            $gallery_info->bg_color = $xml_obj->attrs->bg_color;
            $gallery_info->gallery_align = $xml_obj->attrs->gallery_align;

            $images_list = $xml_obj->attrs->images_list;
            $images_list = preg_replace('/\.(gif|jpg|jpeg|png) /i',".\\1\n",$images_list);
            $gallery_info->images_list = explode("\n",trim($images_list));

            preg_match_all('/(width|height)([^[:digit:]]+)([0-9]+)/i',$xml_obj->attrs->style,$matches);
            $gallery_info->width = trim($matches[3][0]);
            if(!$gallery_info->width) $gallery_info->width = 400;

            Context::set('gallery_info', $gallery_info);

            $tpl_path = $this->component_path.'tpl';
            Context::set("tpl_path", $tpl_path);

            if($gallery_info->gallery_style == "list") $tpl_file = 'list_gallery.html';
            else $tpl_file = 'slide_gallery.html';

            require_once("./classes/template/TemplateHandler.class.php");
            $oTemplate = new TemplateHandler();
            return $oTemplate->compile($tpl_path, $tpl_file);
        }

    }
?>
