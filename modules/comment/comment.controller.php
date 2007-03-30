<?php
    /**
     * @class  commentController
     * @author zero (zero@nzeo.com)
     * @brief  comment 모듈의 controller class
     **/

    class commentController extends comment {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 관리자 페이지에서 선택된 댓글들을 삭제
         **/
        function procCommentAdminDeleteChecked() {

            // 선택된 글이 없으면 오류 표시
            $cart = Context::get('cart');
            if(!$cart) return $this->stop('msg_cart_is_null');
            $comment_srl_list= explode('|@|', $cart);
            $comment_count = count($comment_srl_list);
            if(!$comment_count) return $this->stop('msg_cart_is_null');

            // 글삭제
            for($i=0;$i<$comment_count;$i++) {
                $comment_srl = trim($comment_srl_list[$i]);
                if(!$comment_srl) continue;

                $this->deleteComment($comment_srl, true);
            }

            $this->setMessage( sprintf(Context::getLang('msg_checked_comment_is_deleted'), $comment_count) );
        }

        /**
         * @brief 코멘트의 권한 부여 
         * 세션값으로 현 접속상태에서만 사용 가능
         **/
        function addGrant($comment_srl) {
            $_SESSION['own_comment'][$comment_srl] = true;
        }

        /**
         * @brief 댓글 입력
         **/
        function insertComment($obj) {
            // document_srl에 해당하는 글이 있는지 확인
            $document_srl = $obj->document_srl;
            if(!$document_srl) return new Object(-1,'msg_invalid_document');

            // document model 객체 생성
            $oDocumentModel = &getModel('document');

            // 원본글을 가져옴
            $document = $oDocumentModel->getDocument($document_srl);

            if($document_srl != $document->document_srl) return new Object(-1,'msg_invalid_document');
            if($document->lock_comment=='Y') return new Object(-1,'msg_invalid_request');

            $oDB = &DB::getInstance();

            $obj->list_order = $obj->comment_srl * -1;
            if($obj->password) $obj->password = md5($obj->password);

            // 로그인 된 회원일 경우 회원의 정보를 입력
            if(Context::get('is_logged')) {
                $logged_info = Context::get('logged_info');
                $obj->member_srl = $logged_info->member_srl;
                $obj->user_name = $logged_info->user_name;
                $obj->nick_name = $logged_info->nick_name;
                $obj->email_address = $logged_info->email_address;
                $obj->homepage = $logged_info->homepage;
            }

            // file의 Model객체 생성
            $oFileModel = &getModel('file');

            // 첨부 파일의 갯수를 구함
            $obj->uploaded_count = $oFileModel->getFilesCount($obj->comment_srl);

            // 댓글을 입력
            $output = $oDB->executeQuery('comment.insertComment', $obj);

            // 입력에 이상이 없으면 해당 글의 댓글 수를 올림
            if(!$output->toBool()) return $output;

            // comment model객체 생성
            $oCommentModel = &getModel('comment');

            // 해당 글의 전체 댓글 수를 구해옴
            $comment_count = $oCommentModel->getCommentCount($document_srl);

            // document의 controller 객체 생성
            $oDocumentController = &getController('document');

            // 해당글의 댓글 수를 업데이트
            $output = $oDocumentController->updateCommentCount($document_srl, $comment_count);

            // 댓글의 권한을 부여
            $this->addGrant($obj->comment_srl);

            $output->add('comment_srl', $obj->comment_srl);
            return $output;
        }

        /**
         * @brief 댓글 수정
         **/
        function updateComment($obj, $is_admin = false) {
            // comment model 객체 생성
            $oCommentModel = &getModel('comment');

            // 원본 데이터를 가져옴
            $source_obj = $oCommentModel->getComment($obj->comment_srl);

            // 권한이 있는지 확인
            if(!$is_admin && !$source_obj->is_granted) return new Object(-1, 'msg_not_permitted');

            $oDB = &DB::getInstance();

            if($obj->password) $obj->password = md5($obj->password);

            // 로그인 되어 있고 작성자와 수정자가 동일하면 수정자의 정보를 세팅
            if(Context::get('is_logged')) {
                $logged_info = Context::get('logged_info');
                if($source_obj->member_srl == $logged_info->member_srl) {
                    $obj->member_srl = $logged_info->member_srl;
                    $obj->user_name = $logged_info->user_name;
                    $obj->nick_name = $logged_info->nick_name;
                    $obj->email_address = $logged_info->email_address;
                    $obj->homepage = $logged_info->homepage;
                }
            }

            // 로그인한 유저가 작성한 글인데 nick_name이 없을 경우
            if($source_obj->member_srl && !$obj->nick_name) {
                $obj->member_srl = $source_obj->member_srl;
                $obj->user_name = $source_obj->user_name;
                $obj->nick_name = $source_obj->nick_name;
                $obj->email_address = $source_obj->email_address;
                $obj->homepage = $source_obj->homepage;
            }

            // file의 Model객체 생성
            $oFileModel = &getModel('file');

            // 첨부 파일의 갯수를 구함
            $obj->uploaded_count = $oFileModel->getFilesCount($obj->document_srl);

            // 업데이트
            $output = $oDB->executeQuery('comment.updateComment', $obj);

            $output->add('comment_srl', $obj->comment_srl);
            return $output;
        }

        /**
         * @brief 댓글 삭제
         **/
        function deleteComment($comment_srl, $is_admin = false) {
            // comment model 객체 생성
            $oCommentModel = &getModel('comment');

            // 기존 댓글이 있는지 확인
            $comment = $oCommentModel->getComment($comment_srl);
            if($comment->comment_srl != $comment_srl) return new Object(-1, 'msg_invalid_request');
            $document_srl = $comment->document_srl;

            // 해당 댓글에 child가 있는지 확인
            $child_count = $oCommentModel->getChildCommentCount($comment_srl);
            if($child_count>0) return new Object(-1, 'fail_to_delete_have_children');

            // 권한이 있는지 확인
            if(!$is_admin && !$comment->is_granted) return new Object(-1, 'msg_not_permitted');

            // 삭제
            $oDB = &DB::getInstance();

            $args->comment_srl = $comment_srl;
            $output = $oDB->executeQuery('comment.deleteComment', $args);
            if(!$output->toBool()) return new Object(-1, 'msg_error_occured');

            // 첨부 파일 삭제
            if($comment->uploaded_count) {
                $oFileController = &getController('file');
                $oFileController->deleteFiles($comment->module_srl, $comment_srl);
            }

            // 댓글 수를 구해서 업데이트
            $comment_count = $oCommentModel->getCommentCount($document_srl);

            // document의 controller 객체 생성
            $oDocumentController = &getController('document');

            // 해당글의 댓글 수를 업데이트
            $output = $oDocumentController->updateCommentCount($document_srl, $comment_count);

            $output->add('document_srl', $document_srl);
            return $output;
        }

        /**
         * @brief 특정 글의 모든 댓글 삭제
         **/
        function deleteComments($document_srl) {
            // document model객체 생성
            $oDocumentModel = &getModel('document');

            // 권한이 있는지 확인
            if(!$oDocumentModel->isGranted($document_srl)) return new Object(-1, 'msg_not_permitted');

            // 삭제
            $oDB = &DB::getInstance();
            $args->document_srl = $document_srl;
            $output = $oDB->executeQuery('comment.deleteComments', $args);
            return $output;
        }

        /**
         * @brief 특정 모듈의 모든 댓글 삭제
         **/
        function deleteModuleComments($module_srl) {
            $oDB = &DB::getInstance();
            $args->module_srl = $module_srl;
            $output = $oDB->executeQuery('comment.deleteModuleComments', $args);
            return $output;
        }
    }
?>
