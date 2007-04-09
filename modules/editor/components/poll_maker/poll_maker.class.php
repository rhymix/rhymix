<?php
    /**
     * @class  poll_maker
     * @author zero (zero@nzeo.com)
     * @brief  에디터에서 url링크하는 기능 제공. 
     **/

    class poll_maker extends EditorHandler { 

        // upload_target_srl 는 에디터에서 필수로 달고 다녀야 함....
        var $upload_target_srl = 0;
        var $component_path = '';

        /**
         * @brief upload_target_srl과 컴포넌트의 경로를 받음
         **/
        function poll_maker($upload_target_srl, $component_path) {
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
            $poll_srl = $xml_obj->attrs->poll_srl;

            preg_match('/width([^[:digit:]]+)([0-9]+)/i',$xml_obj->attrs->style,$matches);
            $width = $matches[2];
            if(!$width) $width = 400;
            $style = sprintf('width:%dpx', $width);
            debugPrint($style);

            // poll model 객체 생성해서 html 얻어와서 return
            $oPollModel = &getModel('poll');
            return $oPollModel->getPollHtml($poll_srl, $style);
        }
    }
?>
