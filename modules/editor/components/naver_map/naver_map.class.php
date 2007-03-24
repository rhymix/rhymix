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
        var $open_api_key = '22b1f5391a6970e03935444897334066';

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
         *
         * 네이버 지도 open api 는 doctype에 대한 오류 및 기타 등등등등의 문제 때문에 iframe 을 만들고 컴포넌트를 다시 호출해서 html을 출력하게 한다.
         * 네이버 지도 open api 가 xhtml1-transitional.dtd 를 지원하게 되면 다시 깔끔하게 고쳐야 함..
         * 2006년 3월 12일 하루 다 날렸다~~~ ㅡ.ㅜ
         **/
        function transHTML($xml_obj) {
            $x = $xml_obj->attrs->x;
            $y = $xml_obj->attrs->y;
            $width = $xml_obj->attrs->width;
            $height = $xml_obj->attrs->height;

            $body_code = sprintf('<div style="width:%dpx;height:%dpx;"><iframe src="%s?module=editor&amp;act=procCall&amp;method=displayMap&amp;component=naver_map&amp;width=%s&amp;height=%s&amp;x=%s&amp;y=%s" frameBorder="0" style="padding:1px; border:1px solid #AAAAAA;width:%dpx;height:%dpx;margin:0px;"></iframe></div>', $width, $height, Context::getRequestUri(), $width, $height, $x, $y, $width, $height);
            return $body_code;
        }

        function displayMap() {
            $id = "navermap".rand(11111111,99999999);

            $width = Context::get('width');
            if(!$width) $width = 640;

            $height = Context::get('height');
            if(!$height) $height = 480;

            $x = Context::get('x');
            if(!$x) $x = 321198;

            $y = Context::get('y');
            if(!$y) $y = 529730;

            $html = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">'.
                    '<html>'.
                    '<head>'.
                    '<title></title>'.
                    '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'.
                    '<script type="text/javascript" src="./common/js/x.js"></script>'.
                    '<script type="text/javascript" src="http://maps.naver.com/js/naverMap.naver?key='.$this->open_api_key.'"></script>'.
                    '<script type="text/javascript">'.
                    'function moveMap(x,y,scale) {mapObj.setCenterAndZoom(new NPoint(x,y),scale);}'.
                    '</script>'.
                    '</head>'.
                    '<body style="margin:0px;">'.
                    '<div id="'.$id.'" style="width:'.$width.'px;height:'.$height.'px;"></div>'.
                    '<script type="text/javascript">'.
                    'var mapObj = new NMap(document.getElementById("'.$id.'"));'.
                    'mapObj.addControl(new NSaveBtn());';

            if($x&&$y) $html .= 'mapObj.setCenterAndZoom(new NPoint('.$x.','.$y.'),3);';

            $html .= ''.
                     //'mapObj.enableWheelZoom();'.
                     'NEvent.addListener(mapObj, "click", function(pos) { if(typeof(top.mapClicked)!="undefined") top.mapClicked(pos); });'.
                     'NEvent.addListener(mapObj, "mouseup", function(pos) { if(typeof(top.mapClicked)!="undefined") top.mapClicked(pos); });'.
                     '</script>'.
                     '</body>'.
                     '</html>';

            print $html;
            exit();
        }
    }
?>
