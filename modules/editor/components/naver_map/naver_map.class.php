<?php
    /**
     * @class  naver_map
     * @author zero (zero@nzeo.com)
     * @brief  본문에 네이버의 지도 open api로 지도 삽입
     **/

    class naver_map extends EditorHandler { 

        // upload_target_srl 는 에디터에서 필수로 달고 다녀야 함....
        var $upload_target_srl = 0;
        var $component_path = '';

        // 네이버맵 openapi 키 값
        var $open_api_key = 'fb697bbfd01b42ab26db22162e166842';

        /**
         * @brief upload_target_srl과 컴포넌트의 경로를 받음
         **/
        function naver_map($upload_target_srl, $component_path) {
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
         * @brief naver map open api에서 주소를 찾는 함수
         **/
        function search_address() {
            $address = Context::get('address');
            if(!$address) return new Object(-1,'msg_not_exists_addr');

            Context::loadLang($this->component_path."lang");

            // 지정된 서버에 요청을 시도한다
            $query_string = iconv("UTF-8","EUC-KR",sprintf('/api/geocode.php?key=%s&query=%s', $this->open_api_key, $address));

            $fp = fsockopen('maps.naver.com', 80, $errno, $errstr);
            if(!$fp) return new Object(-1, 'msg_fail_to_socket_open');

            fputs($fp, "GET {$query_string} HTTP/1.0\r\n");
            fputs($fp, "Host: maps.naver.com\r\n\r\n");

            $buff = '';
            while(!feof($fp)) {
                $str = fgets($fp, 1024);
                if(trim($str)=='') $start = true;
                if($start) $buff .= trim($str);
            }

            fclose($fp);

            $buff = trim(iconv("EUC-KR", "UTF-8", $buff));
            $buff = str_replace('<?xml version="1.0" encoding="euc-kr" ?>', '', $buff);

            $oXmlParser = new XmlParser();
            $xml_doc = $oXmlParser->parse($buff);

            $addrs = $xml_doc->geocode->item;
            if(!is_array($addrs)) $addrs = array($addrs);
            $addrs_count = count($addrs);

            $address_list = array();
            for($i=0;$i<$addrs_count;$i++) {
                $item = $addrs[$i];

                $address_list[] = sprintf("%s,%s,%s", $item->point->x->body, $item->point->y->body, $item->address->body);

            }

            $this->add("address_list", implode("\n", $address_list));
        }

        /**
         * @brief 에디터 컴포넌트가 별도의 고유 코드를 이용한다면 그 코드를 html로 변경하여 주는 method
         *
         * 이미지나 멀티미디어, 설문등 고유 코드가 필요한 에디터 컴포넌트는 고유코드를 내용에 추가하고 나서
         * DocumentModule::transContent() 에서 해당 컴포넌트의 transHtml() method를 호출하여 고유코드를 html로 변경
         **/
        function transHTML($xml_obj) {
            $x = $xml_obj->attrs->x;
            $y = $xml_obj->attrs->y;
            $width = $xml_obj->attrs->width;
            $height = $xml_obj->attrs->height;
            $id = "navermap".rand(11111111,99999999);

            $body_code = sprintf('<div id="%s" style="width:%spx;height:%spx;">%s', $id, $width, $height,"\n");
            $footer_code = 
                sprintf(
                    '<script type="text/javascript"> '.
                    'var mapObj = new NMap(xGetElementById("%s")); '.
                    'mapObj.addControl(new NSaveBtn()); '.
                    'mapObj.setCenterAndZoom(new NPoint(%d,%d),3); '.
                    'mapObj.enableWheelZoom(); '.
                    '</script>', 
                    $id, $x, $y
                );

            Context::addJsFile("http://maps.naver.com/js/naverMap.naver?key=".$this->open_api_key);
            Context::addHtmlFooter($footer_code);
            return $body_code;
        }
    }
?>
