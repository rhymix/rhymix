<?php
    /**
     * @class  springnoteModel 
     * @author zero (zero@nzeo.com)
     * @brief  springnote모듈의 model 클래스
     **/

    set_include_path("./modules/springnote/lib/PEAR");
    require_once('PEAR.php');
    require_once('HTTP/Request.php');

    class springnoteModel extends springnote {

        var $userid = '';
        var $userkey = '';
        var $appkey = '82dee99105c92c166bb8586415d47283b9a54cd2';
        var $server = 'api.springnote.com';
        var $port = 80;
        var $domain = '';

        /**
         * @brief 초기화
         **/
        function init() { 
        }

        /**
         * @brief 스프링노트 페이지를 가져오기 위한 기본 값 설정
         **/
        function setInfo($userid, $userkey, $domain = '') {
            $this->userid = $userid;
            $this->userkey = $userkey;
            $this->domain = $domain;
        }

        /**
         * @brief url 생성
         **/
        function getUrl($pageid = null) {
            return sprintf('http://%s:%s/pages%s.xml%s', $this->server, $this->port, $pageid?'/'.$pageid:'', $this->domain?'?domain='.$this->domain:'');
        }

        /**
         * @brief userid 생성
         **/
        function getUserID() {
            return htmlentities($this->userid, ENT_QUOTES, 'UTF-8');
        }

        /**
         * @brief password 생성
         **/
        function getPassword() {
            return $this->userkey.'.'.$this->appkey;
        }

        /**
         * @brief HTTP request 객체 생성
         **/
        function getRequest($url) {
            $oReqeust = new HTTP_Request($url);
            $oReqeust->addHeader('Content-Type', 'application/xml');
            $oReqeust->setMethod('GET');
            $oReqeust->setBasicAuth($this->getUserID(), $this->getPassword());
            return $oReqeust;
        }

        /**
         * @brief springnote 페이지 정보 가져오기
         **/
        function getPage($pageid) {
            $url = $this->getUrl($pageid);
            $oReqeust = $this->getRequest($url);
            $oResponse = $oReqeust->sendRequest();

            if (PEAR::isError($oResponse)) return null;

            $body = $oReqeust->getResponseBody();

            $oXmlParser = new XmlParser();
            $xmldoc = $oXmlParser->parse($body);

            $page->identifier = $xmldoc->page->identifier->body;
            $page->title = $xmldoc->page->title->body;
            $page->relation_is_part_of = $xmldoc->page->relation_is_part_of->body;
            $page->date_modified = $xmldoc->page->date_modified->body;
            $page->uri = $xmldoc->page->uri->body;
            $page->date_created = $xmldoc->page->date_created->body;
            $page->rights = $xmldoc->page->rights->body;
            $page->creator = $xmldoc->page->creator->body;
            $page->contributor_modified = $xmldoc->page->contributor_modified->body;
            $page->version = $xmldoc->page->version->body;
            $page->tags = $xmldoc->page->tags->body;
            $page->body = trim($xmldoc->page->body);
            $page->source = trim($xmldoc->page->source->body);

            // source에서 /pages/숫자로 되어 있는 url의 수정
            $page->source = preg_replace('/="\/pages\/([0-9]+)"/is','="?mid='.Context::get('mid').'&pageid=\\1"', $page->source);

            // 첨부파일의 경로를 변경
            $page->source = preg_replace('/="\/pages\/([0-9]+)\/attachments\/([0-9]+)"/is','="'.$page->uri.'/attachments/\\2"', $page->source);

            $uri = preg_replace('/pages(.*)$/i','',$page->uri);
            $page->css_files = array(
                    sprintf('%sstylesheets/xhtmlContent.css?%d', $uri, time()),
                    sprintf('%sstylesheets/template.css?%d', $uri, time()),
            );
            return $page;
        }

        /**
         * @brief springnote 페이지 목록 가져오기
         **/
        function getPages($query = null, $fulltext = true, $p_pageid = 0) {

            if($query) {
                if($this->domain) $url = sprintf('%s&q=%s&fulltext=%d', $this->getUrl(), urlencode($query), $fulltext?1:0);
                else $url = sprintf('%s?q=%s&fulltext=%d', $this->getUrl(), urlencode($query), $fulltext?1:0);
            } else {
                $url = $this->getUrl();
            }

            $oReqeust = $this->getRequest($url);
            $oResponse = $oReqeust->sendRequest();

            if (PEAR::isError($oResponse)) return array();

            $body = $oReqeust->getResponseBody();

            $oXmlParser = new XmlParser();
            $xmldoc = $oXmlParser->parse($body);

            // 페이지 목록 정리
            $output = array();
            $pages = array();
            $root = null;
            if(count($xmldoc->pages->page)) {
                // 일단 서버에서 보내주는 대로 목록을 받음
                foreach($xmldoc->pages->page as $val) {
                    $obj = null;
                    $obj->pageid = $val->identifier->body;
                    $obj->title = $val->title->body;
                    $obj->relation_is_part_of = $val->relation_is_part_of->body;
                    $obj->date_modified = $val->date_modified->body;
                    $obj->uri = $val->uri->body;
                    $obj->source = trim($val->source->body);
                    if($query && !$obj->source) continue;
                    $pages[$obj->pageid] = $obj;
                }

                // parent/chlid관계로 묶음
                foreach($pages as $pageid => $page) {
                    if($page->relation_is_part_of) $pages[$page->relation_is_part_of]->child[] = &$pages[$pageid];
                    else $root->child[] = &$pages[$pageid];
                }

                if($p_pageid) $this->getNodes($root->child, $p_pageid, $root);

                $pages = array();
                $this->arrangePages($pages, $root->child, 0);

            }

            return $pages;
        }

        /**
         * @brief 특정 노드아래만 검색을 하기 위할때 해당 노드의 page_id를 받아서 해당 노드tree만 리턴
         **/
        function getNodes($list, $p_pageid, &$root) {
            if(!count($list)) return;
            foreach($list as $key => $val) {
                if($val->pageid == $p_pageid) {
                    $root = $val;
                    return;
                }

                if($val->child) $this->getNodes($val->child, $p_pageid, $root);
            }
        }

        /**
         * @brief 스프링노트 서버에서 보내준 페이지를 정렬
         **/
        function arrangePages(&$pages, $list, $depth) {
            if(!count($list)) return;

            foreach($list as $key => $val) {

                $child = $val->child;
                unset($val->child);

                $val->depth = $depth;
                $pages[$val->pageid] = $val;

                if($child) $this->arrangePages($pages, $child,$depth+1);
            }
        }

    }
?>
