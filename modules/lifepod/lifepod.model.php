<?php
    /**
     * @class  lifepodModel 
     * @author haneul (haneul0318@gmail.com)
     * @brief  lifepod모듈의 model 클래스
     **/

    set_include_path("./libs/PEAR");
    require_once('PEAR.php');
    require_once('HTTP/Request.php');
    require_once('./classes/xml/GeneralXmlParser.class.php');

    class lifepodModel extends lifepod {

        var $userid = '';
        var $userkey = '';

        /**
         * @brief 초기화
         **/
        function init() { 
        }

        /**
         * @brief HTTP request 객체 생성
         **/
        function getRequest($url) {
            $oReqeust = new HTTP_Request($url);
            $oReqeust->addHeader('Content-Type', 'application/xml');
            $oReqeust->setMethod('GET');
            return $oReqeust;
        }

	function getURL($address, $start, $end, $pageNumber) {
	    return sprintf("%s&start=%s&end=%s&page=%d", $address, $start, $end, $pageNumber);
	}

        /**
         * @brief lifepod 페이지 정보 가져오기
	 * @remarks 한해씩 끊어서 페이지를 가져옵니다. 아직 50개 이상의 calendar info가 있는 경우 앞에 것만 가져오는 문제가 있습니다.
         **/
        function getPage($address, $year, $pageNumber) {
	    if($year == null)
	    {
		$year = date("Y");		
	    }

	    $start = sprintf("%s-01-01",$year);
	    $end = sprintf("%s-01-01",$year+1);

            $url = $this->getURL($address, $start, $end, $pageNumber);
            $oReqeust = $this->getRequest($url);
            $oResponse = $oReqeust->sendRequest();

            if (PEAR::isError($oResponse)) return null;

            $body = $oReqeust->getResponseBody();

            $oXmlParser = new GeneralXmlParser();
            $xmldoc = $oXmlParser->parse($body);
	    if(!$xmldoc->childNodes["feed"]->childNodes["entry"])
	    {
		$data = array();
	    }
	    else
	    { 
		$data = &$xmldoc->childNodes["feed"]->childNodes["entry"]->childNodes["data"];
	    }
	    $page->title = $xmldoc->childNodes["feed"]->childNodes["title"]->body;
	    if(is_array($data))
	    {
		$page->data = $data;
	    }
	    else
	    {
		$page->data = array();
		$page->data[] = $data;
	    }
	    $page->color = $xmldoc->childNodes["feed"]->childNodes["color"]->body;
	    $page->total = intval($xmldoc->childNodes["feed"]->childNodes["opensearch:totalresults"]->body);
	    $page->start = intval($xmldoc->childNodes["feed"]->childNodes["opensearch:startindex"]->body);
	    $page->perpage = intval($xmldoc->childNodes["feed"]->childNodes["opensearch:itemsperpage"]->body);

            return $page;
        }

    }
?>
