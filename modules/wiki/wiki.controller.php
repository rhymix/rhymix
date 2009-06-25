<?php
    
    class wikiController extends wiki {

        function init() {
        }

        function procWikiInsertDocument() {
            // 권한 체크
            if(!$this->grant->write_document) return new Object(-1, 'msg_not_permitted');
            $entry = Context::get('entry');

            // 글작성시 필요한 변수를 세팅
            $obj = Context::getRequestVars();
            $obj->module_srl = $this->module_srl;
            if($this->module_info->use_comment != 'N')
            {
                $obj->allow_comment = 'Y';
            }
            else
            {
                $obj->allow_comment = 'N';
            }

            if(!$obj->nick_name) $obj->nick_name = "anonymous";
            if($obj->is_notice!='Y'||!$this->grant->manager) $obj->is_notice = 'N';

            settype($obj->title, "string");
            if($obj->title == '') $obj->title = cut_str(strip_tags($obj->content),20,'...');
            //그래도 없으면 Untitled
            if($obj->title == '') $obj->title = 'Untitled';

            // document module의 model 객체 생성
            $oDocumentModel = &getModel('document');

            // document module의 controller 객체 생성
            $oDocumentController = &getController('document');

            // 이미 존재하는 글인지 체크
            $oDocument = $oDocumentModel->getDocument($obj->document_srl, $this->grant->manager);

            // 이미 존재하는 경우 수정
            if($oDocument->isExists() && $oDocument->document_srl == $obj->document_srl) {
                $output = $oDocumentController->updateDocument($oDocument, $obj);
                $msg_code = 'success_updated';

            // 그렇지 않으면 신규 등록
            } else {
                $output = $oDocumentController->insertDocument($obj);
                $msg_code = 'success_registed';
                $obj->document_srl = $output->get('document_srl');
                $oDocumentController->insertAlias($obj->module_srl, $obj->document_srl, $obj->title);
            }

            // 오류 발생시 멈춤
            if(!$output->toBool()) return $output;

            // 결과를 리턴
            $this->add('mid', Context::get('mid'));
            $this->add('document_srl', $output->get('document_srl'));

            // 성공 메세지 등록
            $this->setMessage($msg_code);
        }

        function procWikiInsertComment() {
            // 권한 체크
            if(!$this->grant->write_comment) return new Object(-1, 'msg_not_permitted');

            // 댓글 입력에 필요한 데이터 추출
            $obj = Context::gets('document_srl','comment_srl','parent_srl','content','password','nick_name','nick_name','member_srl','email_address','homepage','is_secret','notify_message');
            $obj->module_srl = $this->module_srl;

            // 원글이 존재하는지 체크
            $oDocumentModel = &getModel('document');
            $oDocument = $oDocumentModel->getDocument($obj->document_srl);
            if(!$oDocument->isExists()) return new Object(-1,'msg_not_permitted');

            // comment 모듈의 model 객체 생성
            $oCommentModel = &getModel('comment');

            // comment 모듈의 controller 객체 생성
            $oCommentController = &getController('comment');

            // comment_srl이 존재하는지 체크
			      // 만일 comment_srl이 n/a라면 getNextSequence()로 값을 얻어온다.
			      if(!$obj->comment_srl) {
                $obj->comment_srl = getNextSequence();
            } else {
                $comment = $oCommentModel->getComment($obj->comment_srl, $this->grant->manager);
            }

            // comment_srl이 없을 경우 신규 입력
            if($comment->comment_srl != $obj->comment_srl) {

                // parent_srl이 있으면 답변으로
                if($obj->parent_srl) {
                    $parent_comment = $oCommentModel->getComment($obj->parent_srl);
                    if(!$parent_comment->comment_srl) return new Object(-1, 'msg_invalid_request');

                    $output = $oCommentController->insertComment($obj);

                // 없으면 신규
                } else {
                    $output = $oCommentController->insertComment($obj);
                }

                // 문제가 없고 모듈 설정에 관리자 메일이 등록되어 있으면 메일 발송
                if($output->toBool() && $this->module_info->admin_mail) {
                    $oMail = new Mail();
                    $oMail->setTitle($oDocument->getTitleText());
                    $oMail->setContent( sprintf("From : <a href=\"%s#comment_%d\">%s#comment_%d</a><br/>\r\n%s", $oDocument->getPermanentUrl(), $obj->comment_srl, $oDocument->getPermanentUrl(), $obj->comment_srl, $obj->content));
                    $oMail->setSender($obj->user_name, $obj->email_address);

                    $target_mail = explode(',',$this->module_info->admin_mail);
                    for($i=0;$i<count($target_mail);$i++) {
                        $email_address = trim($target_mail[$i]);
                        if(!$email_address) continue;
                        $oMail->setReceiptor($email_address, $email_address);
                        $oMail->send();
                    }
                }

            // comment_srl이 있으면 수정으로
            } else {
                $obj->parent_srl = $comment->parent_srl;
                $output = $oCommentController->updateComment($obj, $this->grant->manager);
                $comment_srl = $obj->comment_srl;
            }

            if(!$output->toBool()) return $output;

            $this->setMessage('success_registed');
            $this->add('mid', Context::get('mid'));
            $this->add('document_srl', $obj->document_srl);
            $this->add('comment_srl', $obj->comment_srl);
        }

        function procWikiDeleteComment() {
            // check the comment's sequence number 
            $comment_srl = Context::get('comment_srl');
            if(!$comment_srl) return $this->doError('msg_invalid_request');

            // create controller object of comment module 
            $oCommentController = &getController('comment');

            $output = $oCommentController->deleteComment($comment_srl, $this->grant->manager);
            if(!$output->toBool()) return $output;

            $this->add('mid', Context::get('mid'));
            $this->add('page', Context::get('page'));
            $this->add('document_srl', $output->get('document_srl'));
            $this->setMessage('success_deleted');
        }

        function procWikiMoveTree() {
            // 권한 체크
            if(!$this->grant->write_document) return new Object(-1, 'msg_not_permitted');

            // request argument 추출
            $args = Context::gets('parent_srl','target_srl','source_srl');

            // 노드 정보 구함
            $output = executeQuery('wiki.getTreeNode', $args);
            $node = $output->data;
            if(!$node->document_srl) return new Object('msg_invalid_request');

            $args->module_srl = $node->module_srl;
            $args->title = $node->title;

            // parent_srl 이 있으면 자식으로 추가
            if($args->parent_srl) {
                // target이 없으면 부모의 list_order중 최소 list_order를 구함
                if(!$args->target_srl) {
                    $list_order->parent_srl = $args->parent_srl;
                    $output = executeQuery('wiki.getTreeMinListorder',$list_order);
                    if($output->data->list_order) $args->list_order = $output->data->list_order-1;
                // target이 있으면 그 target의 list_order + 1
                } else {
                    $t_args->source_srl = $args->target_srl;
                    $output = executeQuery('wiki.getTreeNode', $t_args);
                    $target = $output->data;

                    // target보다 list_order가 크고 부모가 같은 node에 대해서 list_order+2를 해주고 선택된 node에 list_order+1을 해줌
                    $update_args->module_srl = $target->module_srl;
                    $update_args->parent_srl = $target->parent_srl;
                    $update_args->list_order = $target->list_order;
                    $output = executeQuery('wiki.updateTreeListOrder', $update_args);
                    if(!$output->toBool()) return $output;

                    // target을 원위치 (list_order중복 문제로 인하여 1번 더 업데이트를 시도함)
                    $restore_args->module_srl = $target->module_srl;
                    $restore_args->source_srl = $target->document_srl;
                    $restore_args->list_order = $target->list_order;
                    $output = executeQuery('wiki.updateTreeNode', $restore_args);
                    if(!$output->toBool()) return $output;

                    $args->list_order = $target->list_order+1;
                }
                if(!$node->is_exists) $output = executeQuery('wiki.insertTreeNode',$args);
                else $output = executeQuery('wiki.updateTreeNode',$args);
                if(!$output->toBool()) return $output;

                if($args->list_order) {
                    $doc->document_srl = $args->source_srl;
                    $doc->list_order = $args->list_order;
                    $output = executeQuery('wiki.updateDocumentListOrder', $doc);
                    if(!$output->toBool()) return $output;
                }

            // parent_srl이 없고 target_srl 이 있으면 형제로 node 업데이트
            } elseif($args->target_srl) {
                $t_args->source_srl = $args->target_srl;
                $output = executeQuery('wiki.getTreeNode', $t_args);
                $target = $output->data;

                // target보다 list_order가 크고 부모가 같은 node에 대해서 list_order+2를 해주고 선택된 node에 list_order+1을 해줌
                $update_args->module_srl = $target->module_srl;
                $update_args->parent_srl = $target->parent_srl;
                $update_args->list_order = $target->list_order;
                $output = executeQuery('wiki.updateTreeListOrder', $update_args);
                if(!$output->toBool()) return $output;

                // target을 원위치 (list_order중복 문제로 인하여 1번 더 업데이트를 시도함)
                $restore_args->module_srl = $target->module_srl;
                $restore_args->source_srl = $target->document_srl;
                $restore_args->list_order = $target->list_order;
                $output = executeQuery('wiki.updateTreeNode', $restore_args);
                if(!$output->toBool()) return $output;

                $args->list_order = $target->list_order+1;

                // 선택된 노드의 부모 값 맞춤
                $args->parent_srl = $target->parent_srl;
                if(!$node->is_exists) $output = executeQuery('wiki.insertTreeNode',$args);
                else $output = executeQuery('wiki.updateTreeNode',$args);
                if(!$output->toBool()) return $output;

                if($args->list_order) {
                    $doc->document_srl = $args->source_srl;
                    $doc->list_order = $args->list_order;
                    $output = executeQuery('wiki.updateDocumentListOrder', $doc);
                    if(!$output->toBool()) return $output;
                }
            }

            // 캐시파일 재생성
            FileHandler::removeFile(sprintf('%sfiles/cache/wiki/%d.xml', _XE_PATH_,$this->module_srl));
        }

    }

?>
