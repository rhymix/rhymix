<?php
    /**
     * @class  guestbookController
     * @author zero (zero@nzeo.com)
     * @brief  guestbook 모듈의 Controller class
     * guestbook의 controller 클래스는 사용자가 방명록에 글을 쓰거나 댓글을 쓰는등의 동작을 제어한다.
     **/

    class guestbookController extends guestbook {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 문서 입력
         **/
        function procGuestbookInsertDocument() {
            // 권한 체크 (글쓰기 권한이 없으면 오류 출력)
            if(!$this->grant->write_document) return new Object(-1, 'msg_not_permitted');

            /**
             * 글작성시 필요한 변수를 세팅한다.
             * 일단 Context::getReuqestVars()를 통해 모든 입력된 변수값을 가져온다.
             * 글 작성은 document controller를 이용하여 정리된 변수를 넘겨줌으로서 동작이 된다.
             **/
            $obj = Context::getRequestVars();

            // 현재 방명록의 module_srl값을 구해와서 세팅한다.
            $obj->module_srl = $this->module_srl;

            // 공지사항 지정 변수값인 is_notice가 Y가 아니거나 관리자가 아니라면 공지사항은 무조건 N로 세팅한다.
            if($obj->is_notice!='Y'||!$this->grant->manager) $obj->is_notice = 'N';

            /**
             * 문서의 신규 입력인지 수정인지에 대한 체크를 하기 위해서 document model을 통해 원본 문서가 있는지 확인하는 절차를 거쳐야 한다.
             **/
            $oDocumentModel = &getModel('document');

            // document module의 controller 객체 생성
            $oDocumentController = &getController('document');

            // 문서객체를 구해온다.
            $oDocument = $oDocumentModel->getDocument($obj->document_srl, $this->grant->manager);

            /**
             * 제목은 document모델에서는 필수 요건이다.
             * 방명록에서는 제목이 필요 없어서 본문의 내용중 앞 10자리의 글자를 잘라서 제목으로 강제 적용한다.
             **/
            $obj->title = cut_str($obj->content,10,'...');

            /**
             * 이미 존재하는 글일 경우 수정을 한다.
             * 글 수정은 document controller의 updateDocument() method를 이용한다.
             * 결과메세지를 일단 강제로 정의 해 놓는다.
             **/
            if($oDocument->isExists() && $oDocument->document_srl == $obj->document_srl) {
                $output = $oDocumentController->updateDocument($oDocument, $obj);
                $msg_code = 'success_updated';

            /**
             * 존재하지 않는다고 판단이 되면 신규글 입력을 한다.
             * 신규글 입력은 document controller의 inesrtDocument() method를 이용한다.
             * 결과메세지를 일단 강제로 정의 해 놓는다.
             **/
            } else {
                $obj->document_srl = getNextSequence();
                $output = $oDocumentController->insertDocument($obj);
                $msg_code = 'success_registed';
            }

            /**
             * updateDocument(), insertDocument()에서 오류가 발생하였으면 리턴받은 객체 자체를 바로 돌려준다.
             * 이 object객체는 error, message등의 내부 변수를 이용하여 에러 발생 유무와 에러 메세지를 가지고 있다.
             **/
            if(!$output->toBool()) return $output;

            /**
             * 결과를 리턴하기 위해서 mid, document_srl값을 세팅을 한다.
             * controller의 경우 대부분 xml로 요청을 받고 xml로 return을 하게 된다.
             * $this->add(key, value)로 세팅된 값들은 결과 xml에서 사용이 된다.
             * 이 값들은 javascript에서 xml handler를 통해서 사용이 가능하게 되고 보통 url조합을 할때 사용이 된다.
             **/
            $this->add('mid', Context::get('mid'));
            $this->add('document_srl', $output->get('document_srl'));

            /**
             * 성공 메세지 등록
             * setMessage($message)는 xml에 지정이 되고 이 message는 javascript에서 alert()를 시키게 된다.
             **/
            $this->setMessage($msg_code);
        }

        /**
         * @brief 문서 삭제
         **/
        function procGuestbookDeleteDocument() {
            // 문서 번호 확인
            $document_srl = Context::get('document_srl');

            // 문서 번호가 없다면 오류 발생
            if(!$document_srl) return $this->doError('msg_invalid_document');

            // document module model 객체 생성
            $oDocumentController = &getController('document');

            // 삭제 시도
            $output = $oDocumentController->deleteDocument($document_srl, $this->grant->manager);

            // 삭제시 실패하였을 경우 리턴받은 객체를 그대로 리턴.
            if(!$output->toBool()) return $output;

            // 성공 메세지 등록
            $this->add('mid', Context::get('mid'));
            $this->add('page', $output->get('page'));
            $this->setMessage('success_deleted');
        }

        /**
         * @brief 댓글 추가
         **/
        function procGuestbookInsertComment() {
            // 권한 체크
            if(!$this->grant->write_comment) return new Object(-1, 'msg_not_permitted');

            // 댓글 입력에 필요한 데이터 추출
            $obj = Context::gets('document_srl','comment_srl','parent_srl','content','password','nick_name','nick_name','member_srl','email_address','homepage');
            $obj->module_srl = $this->module_srl;

            // comment 모듈의 model 객체 생성
            $oCommentModel = &getModel('comment');

            // comment 모듈의 controller 객체 생성
            $oCommentController = &getController('comment');

            /**
             * 게시판이나 블로그와 달리 방명록의 댓글은 textarea를 그대로 사용한다.
             * 따라서 줄바꾸임나 태그제거등의 작업을 해주어야 함
             **/
            $obj->content = nl2br(strip_tags($obj->content));

            /**
             * 존재하는 댓글인지를 확인하여 존재 하지 않는 댓글이라면 신규로 등록하기 위해서 comment_srl의 sequence값을 받는다
             **/
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

            // comment_srl이 있으면 수정으로
            } else {
                $obj->parent_srl = $comment->parent_srl;
                $output = $oCommentController->updateComment($obj, $this->grant->manager);
                $comment_srl = $obj->comment_srl;
            }

            // 오류 발생시 객체 그대로 리턴.
            if(!$output->toBool()) return $output;

            // 댓글 입력후 페이지 이동을 위한 변수 및 메세지를 설정한다.
            $this->add('mid', Context::get('mid'));
            $this->add('document_srl', $obj->document_srl);
            $this->add('comment_srl', $obj->comment_srl);

            $this->setMessage('success_registed');
        }

        /**
         * @brief 댓글 삭제
         **/
        function procGuestbookDeleteComment() {
            // 댓글 번호 확인
            $comment_srl = Context::get('comment_srl');
            if(!$comment_srl) return $this->doError('msg_invalid_request');

            // comment 모듈의 controller 객체 생성
            $oCommentController = &getController('comment');
            $output = $oCommentController->deleteComment($comment_srl, $this->grant->manager);

            // 오류 발생시 객체 그대로 리턴.
            if(!$output->toBool()) return $output;

            // 댓글 입력후 페이지 이동을 위한 변수 및 메세지를 설정한다.
            $this->setMessage('success_deleted');
            $this->add('mid', Context::get('mid'));
            $this->add('page', Context::get('page'));
            $this->add('document_srl', $output->get('document_srl'));
        }

        /**
         * @brief 문서와 댓글의 비밀번호를 확인
         * 비밀번호와 문서 혹은 댓글의 비밀번호를 비교하여 이상이 없다면 해당 문서 또는 댓글에 권한을 부여한다.
         * 이 권한은 세션에 저장이 되어 차후 다시 수정등을 할 경우 비밀번호 검사를 하지 않게 된다.
         **/
        function procGuestbookVerificationPassword() {
            // 비밀번호와 문서 번호를 받음
            $password = md5(Context::get('password'));

            $document_srl = Context::get('document_srl');
            $comment_srl = Context::get('comment_srl');

            // comment_srl이 있을 경우 댓글이 대상
            if($comment_srl) {
                // 문서번호에 해당하는 글이 있는지 확인
                $oCommentModel = &getModel('comment');
                $data = $oCommentModel->getComment($comment_srl);
                if(!$data) return new Object(-1, 'msg_invalid_request');

                // 문서의 비밀번호와 입력한 비밀번호의 비교
                if($data->password != $password) return new Object(-1, 'msg_invalid_password');

                $oCommentController = &getController('comment');
                $oCommentController->addGrant($comment_srl);
            } else {
                // 문서번호에 해당하는 글이 있는지 확인
                $oDocumentModel = &getModel('document');
                $oDocument = $oDocumentModel->getDocument($document_srl);
                if(!$oDocument->isExists()) return new Object(-1, 'msg_invalid_request');

                // 문서의 비밀번호와 입력한 비밀번호의 비교
                if($oDocument->get('password') != $password) return new Object(-1, 'msg_invalid_password');

                $oDocument->setGrant();
            }
        }

    }
?>
