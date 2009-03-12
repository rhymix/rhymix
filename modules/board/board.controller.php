<?php
    /**
     * @class  boardController
     * @author zero (zero@nzeo.com)
     * @brief  board 모듈의 Controller class
     **/

    class boardController extends board {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 문서 입력
         **/
        function procBoardInsertDocument() {
            // 권한 체크
            if(!$this->grant->write_document) return new Object(-1, 'msg_not_permitted');

            // 글작성시 필요한 변수를 세팅
            $obj = Context::getRequestVars();
            $obj->module_srl = $this->module_srl;
            if($obj->is_notice!='Y'||!$this->grant->manager) $obj->is_notice = 'N';

            settype($obj->title, "string");
            if($obj->title == '') $obj->title = cut_str(strip_tags($obj->content),20,'...');
            //그래도 없으면 Untitled
            if($obj->title == '') $obj->title = 'Untitled';

            // 관리자가 아니라면 게시글 색상/굵기 제거
            if(!$this->grant->manager) {
                unset($obj->title_color);
                unset($obj->title_bold);
            }

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

                // 문제가 없고 모듈 설정에 관리자 메일이 등록되어 있으면 메일 발송
                if($output->toBool() && $this->module_info->admin_mail) {
                    $oMail = new Mail();
                    $oMail->setTitle($obj->title);
                    $oMail->setContent( sprintf("From : <a href=\"%s\">%s</a><br/>\r\n%s", getUrl('','document_srl',$obj->document_srl), getUrl('','document_srl',$obj->document_srl), $obj->content));
                    $oMail->setSender($obj->user_name, $obj->email_address);

                    $target_mail = explode(',',$this->module_info->admin_mail);
                    for($i=0;$i<count($target_mail);$i++) {
                        $email_address = trim($target_mail[$i]);
                        if(!$email_address) continue;
                        $oMail->setReceiptor($email_address, $email_address);
                        $oMail->send();
                    }
                }
            }

            // 오류 발생시 멈춤
            if(!$output->toBool()) return $output;

            // 결과를 리턴
            $this->add('mid', Context::get('mid'));
            $this->add('document_srl', $output->get('document_srl'));

            // 성공 메세지 등록
            $this->setMessage($msg_code);
        }

        /**
         * @brief 문서 삭제
         **/
        function procBoardDeleteDocument() {
            // 문서 번호 확인
            $document_srl = Context::get('document_srl');

            // 문서 번호가 없다면 오류 발생
            if(!$document_srl) return $this->doError('msg_invalid_document');

            // document module model 객체 생성
            $oDocumentController = &getController('document');

            // 삭제 시도
            $output = $oDocumentController->deleteDocument($document_srl, $this->grant->manager);
            if(!$output->toBool()) return $output;

            // 성공 메세지 등록
            $this->add('mid', Context::get('mid'));
            $this->add('page', $output->get('page'));
            $this->setMessage('success_deleted');
        }

        /**
         * @brief 추천
         **/
        function procBoardVoteDocument() {
            // document module controller 객체 생성
            $oDocumentController = &getController('document');

            $document_srl = Context::get('document_srl');
            return $oDocumentController->updateVotedCount($document_srl);
        }

        /**
         * @brief 코멘트 추가
         **/
        function procBoardInsertComment() {
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

        /**
         * @brief 코멘트 삭제
         **/
        function procBoardDeleteComment() {
            // 댓글 번호 확인
            $comment_srl = Context::get('comment_srl');
            if(!$comment_srl) return $this->doError('msg_invalid_request');

            // comment 모듈의 controller 객체 생성
            $oCommentController = &getController('comment');

            $output = $oCommentController->deleteComment($comment_srl, $this->grant->manager);
            if(!$output->toBool()) return $output;

            $this->add('mid', Context::get('mid'));
            $this->add('page', Context::get('page'));
            $this->add('document_srl', $output->get('document_srl'));
            $this->setMessage('success_deleted');
        }

        /**
         * @brief 엮인글 삭제
         **/
        function procBoardDeleteTrackback() {
            $trackback_srl = Context::get('trackback_srl');

            // trackback module의 controller 객체 생성
            $oTrackbackController = &getController('trackback');
            $output = $oTrackbackController->deleteTrackback($trackback_srl, $this->grant->manager);
            if(!$output->toBool()) return $output;

            $this->add('mid', Context::get('mid'));
            $this->add('page', Context::get('page'));
            $this->add('document_srl', $output->get('document_srl'));
            $this->setMessage('success_deleted');
        }

        /**
         * @brief 문서와 댓글의 비밀번호를 확인
         **/
        function procBoardVerificationPassword() {
            // 비밀번호와 문서 번호를 받음
            $password = Context::get('password');
            $document_srl = Context::get('document_srl');
            $comment_srl = Context::get('comment_srl');

            $oMemberModel = &getModel('member');

            // comment_srl이 있을 경우 댓글이 대상
            if($comment_srl) {
                // 문서번호에 해당하는 글이 있는지 확인
                $oCommentModel = &getModel('comment');
                $oComment = $oCommentModel->getComment($comment_srl);
                if(!$oComment->isExists()) return new Object(-1, 'msg_invalid_request');

                // 문서의 비밀번호와 입력한 비밀번호의 비교
                if(!$oMemberModel->isValidPassword($oComment->get('password'),$password)) return new Object(-1, 'msg_invalid_password');

                $oComment->setGrant();
            } else {
                // 문서번호에 해당하는 글이 있는지 확인
                $oDocumentModel = &getModel('document');
                $oDocument = $oDocumentModel->getDocument($document_srl);
                if(!$oDocument->isExists()) return new Object(-1, 'msg_invalid_request');

                // 문서의 비밀번호와 입력한 비밀번호의 비교
                if(!$oMemberModel->isValidPassword($oDocument->get('password'),$password)) return new Object(-1, 'msg_invalid_password');

                $oDocument->setGrant();
            }
        }

        /**
         * @brief 아이디 클릭시 나타나는 팝업메뉴에 "작성글 보기" 메뉴를 추가하는 trigger
         **/
        function triggerMemberMenu(&$obj) {
            $member_srl = Context::get('target_srl');
            $mid = Context::get('cur_mid');

            if(!$member_srl || !$mid) return new Object();

            $logged_info = Context::get('logged_info');

            // 호출된 모듈의 정보 구함
            $oModuleModel = &getModel('module');
            $cur_module_info = $oModuleModel->getModuleInfoByMid($mid);

            if($cur_module_info->module != 'board') return new Object();

            // 자신의 아이디를 클릭한 경우
            if($member_srl == $logged_info->member_srl) {
                $member_info = $logged_info;
            } else {
                $oMemberModel = &getModel('member');
                $member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
            }

            if(!$member_info->user_id) return new Object();

            // 아이디로 검색기능 추가
            $url = getUrl('','mid',$mid,'search_target','user_id','search_keyword',$member_info->user_id);
            $oMemberController = &getController('member');
            $oMemberController->addMemberPopupMenu($url, 'cmd_view_own_document', './modules/member/tpl/images/icon_view_written.gif');

            return new Object();
        }

    }
?>
