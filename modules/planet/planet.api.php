<?php
    /**
     * @class  planetAPI
     * @author zero (zero@zeroboard.com)
     * @brief  planet 모듈의 View Action에 대한 API 처리
     **/

    class planetAPI extends planet {

        function dispPlanetHome(&$oModule) {
            $oModule->add('contentList', $this->arrangeContentList( Context::get('content_list') ) );
            $oModule->add('pageNavigation', Context::get('page_navigation'));
        }

        function dispPlanet(&$oModule) {
            $oModule->add('contentList', $this->arrangeContentList( Context::get('content_list') ) );
            $oModule->add('pageNavigation', Context::get('page_navigation'));
        }

        function favorite(&$oModule) {
            $oModule->add('contentList', $this->arrangeContentList( Context::get('content_list') ) );
            $oModule->add('pageNavigation', Context::get('page_navigation'));
        }

        function dispPlanetContentTagSearch(&$oModule){
            $oModule->add('contentList', $this->arrangeContentList( Context::get('content_list') ) );
            $oModule->add('pageNavigation', Context::get('page_navigation'));
        }

        function dispPlanetContentSearch(&$oModule){
            $oModule->add('contentList', $this->arrangeContentList( Context::get('content_list') ) );
            $oModule->add('pageNavigation', Context::get('page_navigation'));
        }

        function dispPlanetTagSearch(&$oModule){
            $oModule->add('planetList', $this->arrangePlanetList( Context::get('planet_list') ) );
            $oModule->add('pageNavigation', Context::get('page_navigation'));
        }

        function dispReplyList(&$oModule){
            $reply_list = Context::get('reply_list');
            $output = array();
            if(count($reply_list)) {
                foreach($reply_list as $key => $val) {
                    unset($obj);
                    $obj->mid = $val->mid;
                    $obj->document_srl = $val->document_srl;
                    $obj->nick_name = $val->nick_name;
                    $obj->content = $val->content;
                    $obj->regdate = $val->regdate;
                    $output[] = $obj;
                }
            }
            $oModule->add('planetReplyList', $output );
            $oModule->add('pageNavigation', Context::get('page_navigation'));
        }

        function arrangeContentList($content_list) {
            $output = array();
            if(count($content_list)) {
                foreach($content_list as $key => $val) {
                    $item = null;
                    $item = $val->gets('mid','document_srl','nick_name','content','postscript','voted_count','regdate','tag_list','comment_count');
                    $item->photo = $val->getPlanetPhotoSrc();
                    $output[] = $item;
                }
            }
            return $output;
        }


        function arrangePlanetList($planet_list) {
            $output = array();
            if(count($planet_list)) {
                foreach($planet_list as $key => $val) {
                    $item = null;
                    $item = $val->gets('mid','document_srl','nick_name','content','postscript','voted_count','regdate','tag_list');
                    $item->photo = $val->getPhotoSrc();
                    $output[] = $item;
                }
            }
            return $output;
        }
    }
?>
