<?php
    /**
     * @class  boardWAP
     * @author zero (zero@nzeo.com)
     * @brief  board 모듈의 WAP class
     **/

    class boardWAP extends board {

        /**
         * @brief wap procedure method
         **/
        function procWAP(&$oMobile) {
            // 권한 체크
            if(!$this->grant->list || $this->module_info->consultation == 'Y') return $oMobile->setContent(Context::getLang('msg_not_permitted'));

            // document model 객체 생성
            $oDocumentModel = &getModel('document');

            // 선택된 게시글이 있을 경우
            $document_srl = Context::get('document_srl');
            if($document_srl) {
                $oDocument = $oDocumentModel->getDocument($document_srl);
                if($oDocument->isExists()) {
                    // 권한 확인
                    if(!$this->grant->view) return $oMobile->setContent(Context::getLang('msg_not_permitted'));

                    // 글 제목 설정
                    Context::setBrowserTitle($oDocument->getTitleText());

                    // 댓글 보기 일 경우
                    if($this->act=='dispBoardContentCommentList') {

                        $oCommentModel = &getModel('comment');
                        $output = $oCommentModel->getCommentList($oDocument->document_srl, 0, false, $oDocument->getCommentCount());

                        $content = '';
                        if(count($output->data)) {
                            foreach($output->data as $key => $val){
                                $oComment = new commentItem();
                                $oComment->setAttribute($val);
                                if(!$oComment->isAccessible()) continue;
                                $content .= "<b>".$oComment->getNickName()."</b> (".$oComment->getRegdate("Y-m-d").")<br>\r\n".$oComment->getContent(false,false)."<br>\r\n";
                            } 
                        }

                        // 내용 설정
                        $oMobile->setContent( $content );

                        // 상위 페이지를 목록으로 돌아가기로 지정
                        $oMobile->setUpperUrl( getUrl('act',''), Context::getLang('cmd_go_upper') );

                    // 댓글 보기가 아니면 글 보여줌
                    } else {

                        // 내용 지정 (태그를 모두 제거한 내용을 설정)
                        $content = strip_tags($oDocument->getContent(false,false,false));


                        // 내용 상단에 정보 출력 (댓글 보기 링크 포함)
                        $content = Context::getLang('replies').' : <a href="'.getUrl('act','dispBoardContentCommentList').'">'.$oDocument->getCommentCount().'</a><br>'."\r\n".$content;
                        $content = '<b>'.$oDocument->getNickName()."</b> (".$oDocument->getRegdate("Y-m-d").")<br>\r\n".$content;
                        
                        // 내용 설정
                        $oMobile->setContent( $content );

                        // 상위 페이지를 목록으로 돌아가기로 지정
                        $oMobile->setUpperUrl( getUrl('document_srl',''), Context::getLang('cmd_list') );

                    }

                    return;
                }
            }

            // 게시글 목록
            $args->module_srl = $this->module_srl; 
            $args->page = Context::get('page');; 
            $args->list_count = 9;
            $args->sort_index = $this->module_info->order_target?$this->module_info->order_target:'list_order';
            $args->order_type = $this->module_info->order_type?$this->module_info->order_type:'asc';
            $output = $oDocumentModel->getDocumentList($args, $this->except_notice);
            $document_list = $output->data;
            $page_navigation = $output->page_navigation;

            $childs = array();
            if($document_list && count($document_list)) {
                foreach($document_list as $key => $val) {
                    $href = getUrl('mid',$_GET['mid'],'document_srl',$val->document_srl);
                    $obj = null;
                    $obj['href'] = $val->getPermanentUrl();

                    $title = $val->getTitleText();
                    if($val->getCommentCount()) $title .= ' ['.$val->getCommentCount().']';
                    $obj['link'] = $obj['text'] = '['.$val->getNickName().'] '.$title;
                    $childs[] = $obj;
                } 
                $oMobile->setChilds($childs); 
            }

            $totalPage = $page_navigation->last_page;
            $page = (int)Context::get('page');
            if(!$page) $page = 1;

            // next/prevUrl 지정
            if($page>1) $oMobile->setPrevUrl(getUrl('mid',$_GET['mid'],'page',$page-1), sprintf('%s (%d/%d)', Context::getLang('cmd_prev'), $page-1, $totalPage));

            if($page<$totalPage) $oMobile->setNextUrl(getUrl('mid',$_GET['mid'],'page',$page+1), sprintf('%s (%d/%d)', Context::getLang('cmd_next'), $page+1, $totalPage));

            $oMobile->mobilePage = $page;
            $oMobile->totalPage = $totalPage;
        }
    }

?>
