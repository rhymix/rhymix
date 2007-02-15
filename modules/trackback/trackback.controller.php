<?php
    /**
     * @file   : modules/trackback/trackback.module.php
     * @author : zero <zero@nzeo.com>
     * @desc   : 기본 모듈중의 하나인 trackback module
     *           Module class에서 상속을 받아서 사용
     *           action 의 경우 disp/proc 2가지만 존재하며 이는 action명세서에 
     *           미리 기록을 하여야 함
     **/

    class trackback extends Module {

        /**
         * 모듈의 정보
         **/
        var $cur_version = "20070130_0.01";

        /**
         * 기본 action 지정
         * $act값이 없거나 잘못된 값이 들어올 경우 $default_act 값으로 진행
         **/
        var $default_act = '';

        /**
         * 현재 모듈의 초기화를 위한 작업을 지정해 놓은 method
         * css/js파일의 load라든지 lang파일 load등을 미리 선언
         *
         * Init() => 공통 
         * dispInit() => disp시에
         * procInit() => proc시에
         *
         * $this->module_path는 현재 이 모듈파일의 위치를 나타낸다
         * (ex: $this->module_path = "./modules/install/";
         **/

        // 초기화
        function init() {
        }

        // disp 초기화
        function dispInit() {
        }

        // proc 초기화
        function procInit() {
        }

        /**
         * 여기서부터는 action의 구현
         * request parameter의 경우 각 method의 첫번째 인자로 넘어온다
         *
         * dispXXXX : 출력을 위한 method, output에 tpl file이 지정되어야 한다
         * procXXXX : 처리를 위한 method, output에는 trackback, trackback가 지정되어야 한다
         **/

        /**
         * 여기부터는 이 모듈과 관련된 라이브러리 개념의 method들
         **/

        // public boolean insertTrackback($obj)
        // 엮인글 입력
        function insertTrackback($obj) {
        // document_srl에 해당하는 글이 있는지 확인
        $document_srl = $obj->document_srl;
        if(!$document_srl) $this->dispMessage(-1, 'fail');

        $oDocument = getModule('document');
        $document = $oDocument->getDocument($document_srl);

        if(!$document_srl) $this->dispMessage(-1,'fail');
        if($document->allow_trackback=='N') $this->dispMessage(-1,'fail');

        // 엮인글 정리
        $obj = Context::convertEncoding($obj);
        if(!$obj->blog_name) $obj->blog_name = $obj->title;
        $obj->excerpt = strip_tags($obj->excerpt);

        // 엮인글를 입력
        $oDB = &DB::getInstance();
        $obj->list_order = $obj->trackback_srl = $oDB->getNextSequence();
        $obj->module_srl = $document->module_srl;
        $output = $oDB->executeQuery('trackback.insertTrackback', $obj);

        // 입력에 이상이 없으면 해당 글의 엮인글 수를 올림
        if(!$output->toBool()) $this->dispMessage(-1, 'fail');

        // 해당 글의 전체 엮인글 수를 구해옴
        $trackback_count = $this->getTrackbackCount($document_srl);

        // 해당글의 엮인글 수를 업데이트
        $output = $oDocument->updateTrackbackCount($document_srl, $trackback_count);

        if(!$output->toBool()) $this->dispMessage(-1,'fail');
        else $this->dispMessage(0,'success');
        }

        function dispMessage($error, $message) {
        // 헤더 출력
        header("Content-Type: text/xml; charset=UTF-8");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        print '<?xml version="1.0" encoding="utf-8" ?>'."\n";
        print "<response>\n<error>{$error}</error><message>{$message}</message></response>";
        exit();
        }

        // public boolean getTrackback($trackback_srl)
        // 엮인글 삭제
        function getTrackback($trackback_srl) {
        $oDB = &DB::getInstance();
        $args->trackback_srl = $trackback_srl;
        return $oDB->executeQuery('trackback.getTrackback', $args);
        }

        // public boolean deleteTrackback($trackback_srl)
        // 엮인글 삭제
        function deleteTrackback($trackback_srl) {
        // 삭제하려는 엮인글이 있는지 확인
        $trackback = $this->getTrackback($trackback_srl);
        if($trackback->data->trackback_srl != $trackback_srl) return new Output(-1, 'msg_invalid_request');
        $document_srl = $trackback->data->document_srl;

        // 권한이 있는지 확인
        $oDocument = getModule('document');
        if(!$oDocument->isGranted($document_srl)) return new Output(-1, 'msg_not_permitted');

        // 삭제
        $oDB = &DB::getInstance();
        $args->trackback_srl = $trackback_srl;
        $output = $oDB->executeQuery('trackback.deleteTrackback', $args);
        if(!$output->toBool()) return new Output(-1, 'msg_error_occured');

        // 엮인글 수를 구해서 업데이트
        $trackback_count = $this->getTrackbackCount($document_srl);

        // 해당글의 엮인글 수를 업데이트
        $oDocument = getModule('document');
        $output = $oDocument->updateTrackbackCount($document_srl, $trackback_count);
        $output->add('document_srl', $document_srl);
        return $output;
        }

        // public boolean deleteTrackbacks($document_srl)
        // 엮인글 삭제
        function deleteTrackbacks($document_srl) {
        // 삭제
        $oDB = &DB::getInstance();
        $args->document_srl = $document_srl;
        $output = $oDB->executeQuery('trackback.deleteTrackbacks', $args);
        return $output;
        }

        // public boolean deleteModuleTrackbacks($module_srl)
        // 엮인글 삭제
        function deleteModuleTrackbacks($module_srl) {
        // 삭제
        $oDB = &DB::getInstance();
        $args->module_srl = $module_srl;
        $output = $oDB->executeQuery('trackback.deleteModuleTrackbacks', $args);
        return $output;
        }

        // public number getTrackbackCount($module_srl, $search_obj = NULL)
        // document_srl 에 해당하는 엮인글의 전체 갯수를 가져옴
        function getTrackbackCount($document_srl) {
        $oDB = &DB::getInstance();
        $args->document_srl = $document_srl;
        $output = $oDB->executeQuery('trackback.getTrackbackCount', $args);
        $total_count = $output->data->count;
        return (int)$total_count;
        }

        // public boolean getTrackbackList($document_srl)
        // module_srl값을 가지는 엮인글의 목록을 가져옴
        function getTrackbackList($document_srl) {
        // 엮인글 목록을 가져옴
        $oDB = &DB::getInstance();
        $args->document_srl = $document_srl;
        $args->list_order = 'list_order';
        $output = $oDB->executeQuery('trackback.getTrackbackList', $args);
        if(!$output->toBool()) return $output;
        $trackback_list = $output->data;
        if(!is_array($trackback_list)) $trackback_list = array($trackback_list);
        return $trackback_list;
        }

        // public boolean sendTrackback($document)
        // 엮인글을 발송
        function sendTrackback($document, $trackback_url, $charset) {
        // 발송할 정보를 정리
        $http = parse_url($trackback_url);
        $obj->blog_name = Context::getBrowserTitle();
        $obj->title = $document->title;
        $obj->excerpt = cut_str($document->content, 240);
        $obj->url = sprintf("%s?document_srl=%d", Context::getRequestUri(), $document->document_srl);

        if($charset && function_exists('iconv')) {
        foreach($obj as $key=>$val) {
        $obj->{$key} = iconv('UTF-8',$charset,$val);
        }
        }

        if($http['query']) $http['query'].="&";
        if(!$http['port']) $http['port'] = 80;
        $content =
        sprintf(
        "title=%s&".
        "url=%s&".
        "blog_name=%s&".
        "excerpt=%s",
        urlencode($obj->title),
        urlencode($obj->url),
        urlencode($obj->blog_name),
        urlencode($obj->excerpt)
        );
        if($http['query']) $content .= '&'.$http['query'];

        $content_length = strlen($content);

        $header =
        sprintf(
        "POST %s HTTP/1.1\r\n".
        "Host: %s\r\n".
        "Content-Type: %s\r\n".
        "Content-Length: %s\r\n\r\n".
        "%s\r\n",
        $http['path'],
        $http['host'],
        "application/x-www-form-urlencoded",
        $content_length,
        $content
        );
        if(!$http['host']||!$http['port']) return;

        $fp = @fsockopen($http['host'], $http['port'], $errno, $errstr, 5);
        if(!$fp) return;

        fputs($fp, $header);

        while(!feof($fp)) {
        $line = trim(fgets($fp, 4096));
        if(eregi("^<error>",$line)) break;
        }

        fclose($fp);
        }
    }
?>
