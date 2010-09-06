<?php
    /**
     * @class mcontent
     * @author NHN (developers@xpressengine.com)
     * @brief mcontent를 출력하는 위젯
     * @version 0.1
     **/

    class mcontent extends WidgetHandler {

        /**
         * @brief 위젯의 실행 부분
         *
         * ./widgets/위젯/conf/info.xml 에 선언한 extra_vars를 args로 받는다
         * 결과를 만든후 print가 아니라 return 해주어야 한다
         **/

        function proc($args) {
            // 정렬 대상
            if(!in_array($args->order_target, array('list_order','update_order'))) $args->order_target = 'list_order';

            // 정렬 순서
            if(!in_array($args->order_type, array('asc','desc'))) $args->order_type = 'asc';

            // 출력된 목록 수
            $args->list_count = (int)$args->list_count;
            if(!$args->list_count) $args->list_count = 5;

            // 제목 길이 자르기
            if(!$args->subject_cut_size) $args->subject_cut_size = 0;

            // 내용 길이 자르기
            if(!$args->content_cut_size) $args->content_cut_size = 100;

            // 보기 옵션
            $args->option_view_arr = explode(',',$args->option_view);

            // markup 옵션
            if(!$args->markup_type) $args->markup_type = 'list';

            // 내부적으로 쓰이는 변수 설정
            $oModuleModel = &getModel('module');
            $module_srls = $args->modules_info = $args->module_srls_info = $args->mid_lists = array();
            $site_module_info = Context::get('site_module_info');

            // rss 인 경우 URL정리
            if($args->content_type == 'rss'){
                $args->rss_urls = array();
                $rss_urls = array_unique(array($args->rss_url0,$args->rss_url1,$args->rss_url2,$args->rss_url3,$args->rss_url4));
                for($i=0,$c=count($rss_urls);$i<$c;$i++) {
                    if($rss_urls[$i]) $args->rss_urls[] = $rss_urls[$i];
                }

            // rss가 아닌 XE모듈일 경우 모듈 번호 정리 후 모듈 정보 구함
            } else {

                // 대상 모듈이 선택되어 있지 않으면 해당 사이트의 전체 모듈을 대상으로 함
                if(!$args->module_srls){
                    unset($obj);
                    $obj->site_srl = (int)$site_module_info->site_srl;
                    $output = executeQueryArray('widgets.content.getMids', $obj);
                    if($output->data) {
                        foreach($output->data as $key => $val) {
                            $args->modules_info[$val->mid] = $val;
                            $args->module_srls_info[$val->module_srl] = $val;
                            $args->mid_lists[$val->module_srl] = $val->mid;
                            $module_srls[] = $val->module_srl;
                        }
                    }

                    $args->modules_info = $oModuleModel->getMidList($obj);
                // 대상 모듈이 선택되어 있으면 해당 모듈만 추출
                } else {
                    $obj->module_srls = $args->module_srls;
                    $output = executeQueryArray('widgets.content.getMids', $obj);
                    if($output->data) {
                        foreach($output->data as $key => $val) {
                            $args->modules_info[$val->mid] = $val;
                            $args->module_srls_info[$val->module_srl] = $val;
                            $module_srls[] = $val->module_srl;
                        }
                        $idx = explode(',',$args->module_srls);
                        for($i=0,$c=count($idx);$i<$c;$i++) {
                            $srl = $idx[$i];
                            if(!$args->module_srls_info[$srl]) continue;
                            $args->mid_lists[$srl] = $args->module_srls_info[$srl]->mid;
                        }
                    }
                }

                // 아무런 모듈도 검색되지 않았다면 종료
                if(!count($args->modules_info)) return Context::get('msg_not_founded');
                $args->module_srl = implode(',',$module_srls);
            }

            /**
             * 컨텐츠 추출, 게시글/댓글/엮인글/RSS등 다양한 요소가 있어서 각 method를 따로 만듬
             **/
            // tab 형태
            if($args->tab_type == 'none' || $args->tab_type == '') {
                switch($args->content_type){
                    case 'comment':
                            $content_items = $this->_getCommentItems($args);
                        break;
                    case 'image':
                            $content_items = $this->_getImageItems($args);
                        break;
                    case 'rss':
                            $content_items = $this->getRssItems($args);
                        break;
                    case 'trackback':
                            $content_items = $this->_getTrackbackItems($args);
                        break;
                    default:
                            $content_items = $this->_getDocumentItems($args);
                        break;
                }
            // tab 형태가 아닐 경우
            } else {
                $content_items = array();

                switch($args->content_type){
                    case 'comment':
                            foreach($args->mid_lists as $module_srl => $mid){
                                $args->module_srl = $module_srl;
                                $content_items[$module_srl] = $this->_getCommentItems($args);
                            }
                        break;
                    case 'image':
                            foreach($args->mid_lists as $module_srl => $mid){
                                $args->module_srl = $module_srl;
                                $content_items[$module_srl] = $this->_getImageItems($args);
                            }
                        break;
                    case 'rss':
                            $content_items = $this->getRssItems($args);
                        break;
                    case 'trackback':
                            foreach($args->mid_lists as $module_srl => $mid){
                                $args->module_srl = $module_srl;
                                $content_items[$module_srl] = $this->_getTrackbackItems($args);
                            }
                        break;
                    default:
                            foreach($args->mid_lists as $module_srl => $mid){
                                $args->module_srl = $module_srl;
                                $content_items[$module_srl] = $this->_getDocumentItems($args);
                            }
                        break;
                }
            }

            $output = $this->_compile($args,$content_items);
            return $output;
        }

        /**
         * @brief 댓글 목록을 추출하여 mcontentItem으로 return
         **/
        function _getCommentItems($args) {
            // CommentModel::getCommentList()를 이용하기 위한 변수 정리
            $obj->module_srl = $args->module_srl;
            $obj->sort_index = $args->order_target;
            $obj->list_count = $args->list_count;

            // comment 모듈의 model 객체를 받아서 getCommentList() method를 실행
            $oCommentModel = &getModel('comment');
            $output = $oCommentModel->getNewestCommentList($obj);

            $content_items = array();

            if(!count($output)) return;

            foreach($output as $key => $oComment) {
                $attribute = $oComment->getObjectVars();
                $title = $oComment->getSummary($args->content_cut_size);
                $thumbnail = $oComment->getThumbnail();
                $url = sprintf("%s#comment_%s",getUrl('','document_srl',$oComment->get('document_srl')),$oComment->get('comment_srl'));

                $attribute->mid = $args->mid_lists[$attribute->module_srl];
                $browser_title = $args->module_srls_info[$attribute->module_srl]->browser_title;
                $domain = $args->module_srls_info[$attribute->module_srl]->domain;

                $content_item = new mcontentItem($browser_title);
                $content_item->adds($attribute);
                $content_item->setTitle($title);
                $content_item->setThumbnail($thumbnail);
                $content_item->setLink($url);
                $content_item->setDomain($domain);
                $content_item->add('mid', $args->mid_lists[$attribute->module_srl]);
                $content_items[] = $content_item;
            }
            return $content_items;
        }

        function _getDocumentItems($args){
            // document 모듈의 model 객체를 받아서 결과를 객체화 시킴
            $oDocumentModel = &getModel('document');

            // 분류 구함
            $obj->module_srl = $args->module_srl;
            $output = executeQueryArray('widgets.content.getCategories',$obj);
            if($output->toBool() && $output->data) {
                foreach($output->data as $key => $val) {
                    $category_lists[$val->module_srl][$val->category_srl] = $val;
                }
            }

            // 글 목록을 구함
            $obj->module_srl = $args->module_srl;
            $obj->sort_index = $args->order_target;
            $obj->order_type = $args->order_type=="desc"?"asc":"desc";
            $obj->list_count = $args->list_count;
            $output = executeQueryArray('widgets.content.getNewestDocuments', $obj);
            if(!$output->toBool() || !$output->data) return;

            // 결과가 있으면 각 문서 객체화를 시킴
            $content_items = array();
            $first_thumbnail_idx = -1;
            if(count($output->data)) {
                foreach($output->data as $key => $attribute) {
                    $oDocument = new documentItem();
                    $oDocument->setAttribute($attribute, false);
                    $GLOBALS['XE_DOCUMENT_LIST'][$oDocument->document_srl] = $oDocument;
                    $document_srls[] = $oDocument->document_srl;
                }
                $oDocumentModel->setToAllDocumentExtraVars();

                for($i=0,$c=count($document_srls);$i<$c;$i++) {
                    $oDocument = $GLOBALS['XE_DOCUMENT_LIST'][$document_srls[$i]];
                    $document_srl = $oDocument->document_srl;
                    $module_srl = $oDocument->get('module_srl');
                    $category_srl = $oDocument->get('category_srl');
                    $thumbnail = $oDocument->getThumbnail();
                    $content_item = new mcontentItem( $args->module_srls_info[$module_srl]->browser_title );
                    $content_item->adds($oDocument->getObjectVars());
                    $content_item->setTitle($oDocument->getTitle());
                    $content_item->setCategory( $category_lists[$module_srl][$category_srl]->title );
                    $content_item->setDomain( $args->module_srls_info[$module_srl]->domain );
                    $content_item->setContent($oDocument->getSummary($args->content_cut_size));
                    $content_item->setLink( getSiteUrl($domain,'','document_srl',$document_srl) );
                    $content_item->setThumbnail($thumbnail);
                    $content_item->add('mid', $args->mid_lists[$module_srl]);
                    if($first_thumbnail_idx==-1 && $thumbnail) $first_thumbnail_idx = $i;
                    $content_items[] = $content_item;
                }

                $content_items[0]->setFirstThumbnailIdx($first_thumbnail_idx);
            }
            return $content_items;
        }

        function _getImageItems($args) {
            $oDocumentModel = &getModel('document');

            $obj->module_srls = $obj->module_srl = $args->module_srl;
            $obj->direct_download = 'Y';
            $obj->isvalid = 'Y';

            // 분류 구함
            $output = executeQueryArray('widgets.content.getCategories',$obj);
            if($output->toBool() && $output->data) {
                foreach($output->data as $key => $val) {
                    $category_lists[$val->module_srl][$val->category_srl] = $val;
                }
            }

            // 정해진 모듈에서 문서별 파일 목록을 구함
            $obj->list_count = $args->list_count;
            $files_output = executeQueryArray("file.getOneFileInDocument", $obj);
            $files_count = count($files_output->data);
            if(!$files_count) return;

            $content_items = array();

            for($i=0;$i<$files_count;$i++) $document_srl_list[] = $files_output->data[$i]->document_srl;

            $tmp_document_list = $oDocumentModel->getDocuments($document_srl_list);

            if(!count($tmp_document_list)) return;

            foreach($tmp_document_list as $oDocument){
                $attribute = $oDocument->getObjectVars();
                $browser_title = $args->module_srls_info[$attribute->module_srl]->browser_title;
                $domain = $args->module_srls_info[$attribute->module_srl]->domain;
                $category = $category_lists[$attribute->module_srl]->text;
                $content = $oDocument->getSummary($args->content_cut_size);
                $url = sprintf("%s#%s",$oDocument->getPermanentUrl() ,$oDocument->getCommentCount());
                $thumbnail = $oDocument->getThumbnail();
                $content_item = new mcontentItem($browser_title);
                $content_item->adds($attribute);
                $content_item->setCategory($category);
                $content_item->setContent($content);
                $content_item->setLink($url);
                $content_item->setThumbnail($thumbnail);
                $content_item->setExtraImages($extra_images);
                $content_item->setDomain($domain);
                $content_item->add('mid', $args->mid_lists[$attribute->module_srl]);
                $content_items[] = $content_item;
            }

            return $content_items;
        }

        function getRssItems($args){

            $content_items = array();
            $args->mid_lists = array();

            foreach($args->rss_urls as $key => $rss){
                $args->rss_url = $rss;
                $content_item = $this->_getRssItems($args);
                if(count($content_item) > 0){
                    $browser_title = $content_item[0]->getBrowserTitle();
                    $args->mid_lists[] = $browser_title;
                    $content_items[] = $content_item;
                }
            }

            if($args->tab_type == 'none' || $args->tab_type == ''){
                $items = array();
                foreach($content_items as $key => $val){
                    foreach($val as $k => $v){
                        $date = $v->get('regdate');
                        $i=0;
                        while(array_key_exists(sprintf('%s%02d',$date,$i), $items)) $i++;
                        $items[sprintf('%s%02d',$date,$i)] = $v;
                    }
                }
                if($args->order_type =='asc') ksort($items);
                else krsort($items);
                $content_items = array_slice(array_values($items),0,$args->list_count);
            } return $content_items;
        }

        function _getRssBody($value) {
            if(!$value || is_string($value)) return $value;
            if(is_object($value)) $value = get_object_vars($value);
            $body = null;
            if(!count($value)) return;
            foreach($value as $key => $val) {
                if($key == 'body') {
                    $body = $val;
                    continue;
                }
                if(is_object($val)||is_array($val)) $body = $this->_getRssBody($val);
                if($body !== null) return $body;
            }
            return $body;
        }

        function _getSummary($content, $str_size = 50)
        {
            $content = preg_replace('!(<br[\s]*/{0,1}>[\s]*)+!is', ' ', $content);

            // </p>, </div>, </li> 등의 태그를 공백 문자로 치환
            $content = str_replace(array('</p>', '</div>', '</li>'), ' ', $content);

            // 태그 제거
            $content = preg_replace('!<([^>]*?)>!is','', $content);

            // < , > , " 를 치환
            $content = str_replace(array('&lt;','&gt;','&quot;','&nbsp;'), array('<','>','"',' '), $content);

            // 연속된 공백문자 삭제
            $content = preg_replace('/ ( +)/is', ' ', $content);

            // 문자열을 자름
            $content = trim(cut_str($content, $str_size, $tail));

            // >, <, "를 다시 복구
            $content = str_replace(array('<','>','"'),array('&lt;','&gt;','&quot;'), $content);

            // 영문이 연결될 경우 개행이 안 되는 문제를 해결
            $content = preg_replace('/([a-z0-9\+:\/\.\~,\|\!\@\#\$\%\^\&\*\(\)\_]){20}/is',"$0-",$content);
            return $content; 
        }


       /**
         * @brief rss 주소로 부터 내용을 받아오는 함수
         * tistory 의 경우 원본 주소가 location 헤더를 뿜는다. (내용은 없음)이를 해결하기 위한 수정 - rss_reader 위젯과 방식 동일
         **/
        function requestFeedContents($rss_url) {
            $rss_url = str_replace('&amp;','&',Context::convertEncodingStr($rss_url));
            return FileHandler::getRemoteResource($rss_url, null, 3, 'GET', 'application/xml');
        }

        function _getRssItems($args){
            // 날짜 형태
            $DATE_FORMAT = $args->date_format ? $args->date_format : "Y-m-d H:i:s";

            $buff = $this->requestFeedContents($args->rss_url);

            $encoding = preg_match("/<\?xml.*encoding=\"(.+)\".*\?>/i", $buff, $matches);
            if($encoding && !preg_match("/UTF-8/i", $matches[1])) $buff = Context::convertEncodingStr($buff);

            $buff = preg_replace("/<\?xml.*\?>/i", "", $buff);

            $oXmlParser = new XmlParser();
            $xml_doc = $oXmlParser->parse($buff);
            if($xml_doc->rss) {
                $rss->title = $xml_doc->rss->channel->title->body;
                $rss->link = $xml_doc->rss->channel->link->body;

                $items = $xml_doc->rss->channel->item;

                if(!$items) return;
                if($items && !is_array($items)) $items = array($items);

                $content_items = array();

                foreach ($items as $key => $value) {
                    if($key >= $args->list_count) break;
                    unset($item);

                    foreach($value as $key2 => $value2) {
                        if(is_array($value2)) $value2 = array_shift($value2);
                        $item->{$key2} = $this->_getRssBody($value2);
                    }

                    $content_item = new mcontentItem($rss->title);
                    $content_item->setContentsLink($rss->link);
                    $content_item->setTitle($item->title);
                    $content_item->setNickName(max($item->author,$item->{'dc:creator'}));
                    //$content_item->setCategory($item->category);
                    $item->description = preg_replace('!<a href=!is','<a onclick="window.open(this.href);return false" href=', $item->description);
                    $content_item->setContent($this->_getSummary($item->description, $args->content_cut_size));
                    $content_item->setLink($item->link);
                    $date = date('YmdHis', strtotime(max($item->pubdate,$item->pubDate,$item->{'dc:date'})));
                    $content_item->setRegdate($date);

                    $content_items[] = $content_item;
                }
            } elseif($xml_doc->{'rdf:rdf'}) {
                // rss1.0 지원 (Xml이 대소문자를 구분해야 하는데 XE의 XML파서가 전부 소문자로 바꾸는 바람에 생긴 case) by misol
                $rss->title = $xml_doc->{'rdf:rdf'}->channel->title->body;
                $rss->link = $xml_doc->{'rdf:rdf'}->channel->link->body;

                $items = $xml_doc->{'rdf:rdf'}->item;

                if(!$items) return;
                if($items && !is_array($items)) $items = array($items);

                $content_items = array();

                foreach ($items as $key => $value) {
                    if($key >= $args->list_count) break;
                    unset($item);

                    foreach($value as $key2 => $value2) {
                        if(is_array($value2)) $value2 = array_shift($value2);
                        $item->{$key2} = $this->_getRssBody($value2);
                    }

                    $content_item = new mcontentItem($rss->title);
                    $content_item->setContentsLink($rss->link);
                    $content_item->setTitle($item->title);
                    $content_item->setNickName(max($item->author,$item->{'dc:creator'}));
                    //$content_item->setCategory($item->category);
                    $item->description = preg_replace('!<a href=!is','<a onclick="window.open(this.href);return false" href=', $item->description);
                    $content_item->setContent($this->_getSummary($item->description, $args->content_cut_size));
                    $content_item->setLink($item->link);
                    $date = date('YmdHis', strtotime(max($item->pubdate,$item->pubDate,$item->{'dc:date'})));
                    $content_item->setRegdate($date);

                    $content_items[] = $content_item;
                }
            } elseif($xml_doc->feed && $xml_doc->feed->attrs->xmlns == 'http://www.w3.org/2005/Atom') {
                // Atom 1.0 spec 지원 by misol
                $rss->title = $xml_doc->feed->title->body;
                $links = $xml_doc->feed->link;
                if(is_array($links)) {
                    foreach ($links as $value) {
                        if($value->attrs->rel == 'alternate') {
                            $rss->link = $value->attrs->href;
                            break;
                        }
                    }
                }
                elseif($links->attrs->rel == 'alternate') $rss->link = $links->attrs->href;

                $items = $xml_doc->feed->entry;

                if(!$items) return;
                if($items && !is_array($items)) $items = array($items);

                $content_items = array();

                foreach ($items as $key => $value) {
                    if($key >= $args->list_count) break;
                    unset($item);

                    foreach($value as $key2 => $value2) {
                        if(is_array($value2)) $value2 = array_shift($value2);
                        $item->{$key2} = $this->_getRssBody($value2);
                    }

                    $content_item = new mcontentItem($rss->title);
                    $links = $value->link;
                    if(is_array($links)) {
                        foreach ($links as $val) {
                            if($val->attrs->rel == 'alternate') {
                                $item->link = $val->attrs->href;
                                break;
                            }
                        }
                    }
                    elseif($links->attrs->rel == 'alternate') $item->link = $links->attrs->href;

                    $content_item->setContentsLink($rss->link);
                    if($item->title) {
                        if(!preg_match("/html/i", $value->title->attrs->type)) $item->title = $value->title->body;
                    }
                    $content_item->setTitle($item->title);
                    $content_item->setNickName(max($item->author,$item->{'dc:creator'}));
                    $content_item->setAuthorSite($value->author->uri->body);
                    //$content_item->setCategory($item->category);
                    $item->description = preg_replace('!<a href=!is','<a onclick="window.open(this.href);return false" href=', $item->content);
                    if($item->description) {
                        if(!preg_match("/html/i", $value->content->attrs->type)) $item->description = htmlspecialchars($item->description);
                    }
                    if(!$item->description) {
                        $item->description = $item->summary;
                        if($item->description) {
                            if(!preg_match("/html/i", $value->summary->attrs->type)) $item->description = htmlspecialchars($item->description);
                        }
                    }
                    $content_item->setContent($this->_getSummary($item->description, $args->content_cut_size));
                    $content_item->setLink($item->link);
                    $date = date('YmdHis', strtotime(max($item->published,$item->updated,$item->{'dc:date'})));
                    $content_item->setRegdate($date);

                    $content_items[] = $content_item;
                }
            }
            return $content_items;
        }

        function _getTrackbackItems($args){
            // 분류 구함
            $output = executeQueryArray('widgets.content.getCategories',$obj);
            if($output->toBool() && $output->data) {
                foreach($output->data as $key => $val) {
                    $category_lists[$val->module_srl][$val->category_srl] = $val;
                }
            }

            $obj->module_srl = $args->module_srl;
            $obj->sort_index = $args->order_target;
            $obj->list_count = $args->list_count;

            // trackback 모듈의 model 객체를 받아서 getTrackbackList() method를 실행
            $oTrackbackModel = &getModel('trackback');
            $output = $oTrackbackModel->getNewestTrackbackList($obj);

            // 오류가 생기면 그냥 무시
            if(!$output->toBool() || !$output->data) return;

            // 결과가 있으면 각 문서 객체화를 시킴
            $content_items = array();
            foreach($output->data as $key => $item) {
                $domain = $args->module_srls_info[$item->module_srl]->domain;
                $category = $category_lists[$item->module_srl]->text;
                $url = getSiteUrl($domain,'','document_srl',$item->document_srl);
                $browser_title = $args->module_srls_info[$item->module_srl]->browser_title;

                $content_item = new mcontentItem($browser_title);
                $content_item->adds($item);
                $content_item->setTitle($item->title);
                $content_item->setCategory($category);
                $content_item->setNickName($item->blog_name);
                $content_item->setContent($item->excerpt);  ///<<
                $content_item->setDomain($domain);  ///<<
                $content_item->setLink($url);
                $content_item->add('mid', $args->mid_lists[$item->module_srl]);
                $content_item->setRegdate($item->regdate);
                $content_items[] = $content_item;
            }
            return $content_items;
        }

        function _compile($args,$content_items){
            $oTemplate = &TemplateHandler::getInstance();

            // 위젯에 넘기기 위한 변수 설정
            $widget_info->modules_info = $args->modules_info;
            $widget_info->option_view_arr = $args->option_view_arr;
            $widget_info->list_count = $args->list_count;
            $widget_info->subject_cut_size = $args->subject_cut_size;
            $widget_info->content_cut_size = $args->content_cut_size;

            $widget_info->thumbnail_type = $args->thumbnail_type;
            $widget_info->thumbnail_width = $args->thumbnail_width;
            $widget_info->thumbnail_height = $args->thumbnail_height;
            $widget_info->mid_lists = $args->mid_lists;

            $widget_info->show_browser_title = $args->show_browser_title;
            $widget_info->show_category = $args->show_category;
            $widget_info->show_comment_count = $args->show_comment_count;
            $widget_info->show_trackback_count = $args->show_trackback_count;
            $widget_info->show_icon = $args->show_icon;

            $widget_info->list_type = $args->list_type;
            $widget_info->tab_type = $args->tab_type;

            $widget_info->markup_type = $args->markup_type;
            $widget_info->content_items = $content_items;
            
            unset($args->option_view_arr);
            unset($args->modules_info);

            Context::set('colorset', $args->colorset);
            Context::set('widget_info', $widget_info);

            $tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
            return $oTemplate->compile($tpl_path, "content");
        }
    }

    class mcontentItem extends Object {

        var $browser_title = null;
        var $has_first_thumbnail_idx = false;
        var $first_thumbnail_idx = null;
        var $contents_link = null;
        var $domain = null;

        function mcontentItem($browser_title=''){
            $this->browser_title = $browser_title;
        }
        function setContentsLink($link){
            $this->contents_link = $link;
        }
        function setFirstThumbnailIdx($first_thumbnail_idx){
            if(is_null($this->first_thumbnail) && $first_thumbnail_idx>-1) {
                $this->has_first_thumbnail_idx = true;
                $this->first_thumbnail_idx= $first_thumbnail_idx;
            }
        }
        function setExtraImages($extra_images){
            $this->add('extra_images',$extra_images);
        }
        function setDomain($domain) {
            static $default_domain = null;
            if(!$domain) {
                if(is_null($default_domain)) $default_domain = Context::getDefaultUrl();
                $domain = $default_domain;
            }
            $this->domain = $domain;
        }
        function setLink($url){
            $this->add('url',$url);
        }
        function setTitle($title){
            $this->add('title',$title);
        }

        function setThumbnail($thumbnail){
            $this->add('thumbnail',$thumbnail);
        }
        function setContent($content){
            $this->add('content',$content);
        }
        function setRegdate($regdate){
            $this->add('regdate',$regdate);
        }
        function setNickName($nick_name){
            $this->add('nick_name',$nick_name);
        }

        // 글 작성자의 홈페이지 주소를 저장 by misol
        function setAuthorSite($site_url){
            $this->add('author_site',$site_url);
        }
        function setCategory($category){
            $this->add('category',$category);
        }
        function getBrowserTitle(){
            return $this->browser_title;
        }
        function getDomain() {
            return $this->domain;
        }
        function getContentsLink(){
            return $this->contents_link;
        }

        function getFirstThumbnailIdx(){
            return $this->first_thumbnail_idx;
        }

        function getLink(){
            return $this->get('url');
        }
        function getModuleSrl(){
            return $this->get('module_srl');
        }
        function getTitle($cut_size = 0, $tail='...'){
            $title = strip_tags($this->get('title'));

            if($cut_size) $title = cut_str($title, $cut_size, $tail);

            $attrs = array();
            if($this->get('title_bold') == 'Y') $attrs[] = 'font-weight:bold';
            if($this->get('title_color') && $this->get('title_color') != 'N') $attrs[] = 'color:#'.$this->get('title_color');

            if(count($attrs)) $title = sprintf("<span style=\"%s\">%s</span>", implode(';', $attrs), htmlspecialchars($title));

            return $title;
        }
        function getContent(){
            return $this->get('content');
        }
        function getCategory(){
            return $this->get('category');
        }
        function getNickName(){
            return $this->get('nick_name');
        }
        function getAuthorSite(){
            return $this->get('author_site');
        }
        function getCommentCount(){
            $comment_count = $this->get('comment_count');
            return $comment_count>0 ? $comment_count : '';
        }
        function getTrackbackCount(){
            $trackback_count = $this->get('trackback_count');
            return $trackback_count>0 ? $trackback_count : '';
        }
        function getRegdate($format = 'Y.m.d H:i:s') {
            return zdate($this->get('regdate'), $format);
        }
        function printExtraImages() {
            return $this->get('extra_images');
        }
        function haveFirstThumbnail() {
            return $this->has_first_thumbnail_idx;
        }
        function getThumbnail(){
            return $this->get('thumbnail');
        }
        function getMemberSrl() {
            return $this->get('member_srl');
        }
    }
?>
