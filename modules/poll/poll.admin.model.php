<?php
    /**
     * @class  pollAdminModel
     * @author zero (zero@nzeo.com)
     * @brief  poll 모듈의 admin model class
     **/

    class pollAdminModel extends poll {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 설문 목록 구해옴
         **/
        function getPollList($args) {
            $output = executeQuery('poll.getPollList', $args);
            if(!$output->toBool()) return $output;

            if($output->data && !is_array($output->data)) $output->data = array($output->data);
            return $output;
        }

        /**
         * @brief 설문조사의 원본을 구함
         **/
        function getPollAdminTarget() {
            $poll_srl = Context::get('poll_srl');
            $upload_target_srl = Context::get('upload_target_srl');

            $oDocumentModel = &getModel('document');
            $oCommentModel = &getModel('comment');

            $oDocument = $oDocumentModel->getDocument($upload_target_srl);

            if(!$oDocument->isExists()) $oComment = $oCommentModel->getComment($upload_target_srl);

            if($oComment && $oComment->isExists()) {
                $this->add('document_srl', $oComment->get('document_srl'));
                $this->add('comment_srl', $oComment->get('comment_srl'));
            } elseif($oDocument->isExists()) {
                $this->add('document_srl', $oDocument->get('document_srl'));
            } else return new Object(-1, 'msg_not_founded');
        }

    }
?>
