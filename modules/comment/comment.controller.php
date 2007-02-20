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

            // 댓글를 입력
            $oDB = &DB::getInstance();

            $obj->comment_srl = $oDB->getNextSequence();
            $obj->list_order = $obj->comment_srl * -1;
            if($obj->password) $obj->password = md5($obj->password);
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
        function updateComment($obj) {
            // comment model 객체 생성
            $oCommentModel = &getModel('comment');

            // 권한이 있는지 확인
            if(!$oCommentModel->isGranted($obj->comment_srl)) return new Object(-1, 'msg_not_permitted');

            // 업데이트
            $oDB = &DB::getInstance();

            if($obj->password) $obj->password = md5($obj->password);
            $output = $oDB->executeQuery('comment.updateComment', $obj);

            $output->add('comment_srl', $obj->comment_srl);
            return $output;
        }

        /**
         * @brief 댓글 삭제
         **/
        function deleteComment($comment_srl) {
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
            if(!$oCommentModel->isGranted($comment_srl)) return new Object(-1, 'msg_not_permitted');

            // 삭제
            $oDB = &DB::getInstance();

            $args->comment_srl = $comment_srl;
            $output = $oDB->executeQuery('comment.deleteComment', $args);
            if(!$output->toBool()) return new Object(-1, 'msg_error_occured');

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
