<?php
    /**
     * @class  documentAdminController
     * @author zero (zero@nzeo.com)
     * @brief  document 모듈의 admin controller 클래스
     **/

    class documentAdminController extends document {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 관리자 페이지에서 선택된 문서들 삭제
         **/
        function procDocumentAdminDeleteChecked() {
            // 선택된 글이 없으면 오류 표시
            $cart = Context::get('cart');
            if(!$cart) return $this->stop('msg_cart_is_null');
            $document_srl_list= explode('|@|', $cart);
            $document_count = count($document_srl_list);
            if(!$document_count) return $this->stop('msg_cart_is_null');

            // 글삭제
            $oDocumentController = &getController('document');
            for($i=0;$i<$document_count;$i++) {
                $document_srl = trim($document_srl_list[$i]);
                if(!$document_srl) continue;

                $oDocumentController->deleteDocument($document_srl, true);
            }

            $this->setMessage( sprintf(Context::getLang('msg_checked_document_is_deleted'), $document_count) );
        }

        /**
         * @brief 관리자가 글 선택시 세션에 담음
         **/
        function procDocumentAdminAddCart() {
            $document_srls = explode(',',Context::get('srls'));
            $cnt = count($document_srls);
            for($i=0;$i<$cnt;$i++) {
                $document_srl = (int)trim($document_srls[$i]);
                if(!$document_srls) continue;
                if($_SESSION['document_management'][$document_srl]) unset($_SESSION['document_management'][$document_srl]);
                else $_SESSION['document_management'][$document_srl] = true;
            }
        }

        /**
         * @brief 세션에 담긴 선택글의 이동/ 삭제
         **/
        function procDocumentAdminManageCheckedDocument() {
            $type = Context::get('type');
            $module_srl = Context::get('target_module');
            $category_srl = Context::get('target_category');
            $message_content = Context::get('message_content');
            if($message_content) $message_content = nl2br($message_content);

            $cart = Context::get('cart');
            if($cart) $document_srl_list = explode('|@|', $cart);
            else $document_srl_list = array();

            $document_srl_count = count($document_srl_list);

            // 쪽지 발송
            if($message_content) {

                $oCommunicationController = &getController('communication');
                $oDocumentModel = &getModel('document');

                $logged_info = Context::get('logged_info');

                $title = cut_str($message_content,10,'...');
                $sender_member_srl = $logged_info->member_srl;

                for($i=0;$i<$document_srl_count;$i++) {
                    $document_srl = $document_srl_list[$i];
                    $oDocument = $oDocumentModel->getDocument($document_srl);
                    if(!$oDocument->get('member_srl') || $oDocument->get('member_srl')==$sender_member_srl) continue;

                    if($type=='move') $purl = sprintf("<a href=\"%s\" onclick=\"window.open(this.href);return false;\">%s</a>", $oDocument->getPermanentUrl(), $oDocument->getPermanentUrl());
                    else $purl = "";
                    $content .= sprintf("<div>%s</div><hr />%s<div style=\"font-weight:bold\">%s</div>%s",$message_content, $purl, $oDocument->getTitleText(), $oDocument->getContent(false, false, false));

                    $oCommunicationController->sendMessage($sender_member_srl, $oDocument->get('member_srl'), $title, $content, false);
                }
            }

            // 스팸 처리가 되지 않도록 스팸필터 설정
            $oSpamController = &getController('spamfilter');
            $oSpamController->setAvoidLog();

            if($type == 'move') {
                if(!$module_srl) return new Object(-1, 'fail_to_move');

                $output = $this->moveDocumentModule($document_srl_list, $module_srl, $category_srl);
                if(!$output->toBool()) return new Object(-1, 'fail_to_move');

                $msg_code = 'success_moved';

            } elseif($type == 'copy') {
                if(!$module_srl) return new Object(-1, 'fail_to_move');

                $output = $this->copyDocumentModule($document_srl_list, $module_srl, $category_srl);
                if(!$output->toBool()) return new Object(-1, 'fail_to_move');

                $msg_code = 'success_registed';

            } elseif($type =='delete') {
                $oDB = &DB::getInstance();
                $oDB->begin();
                $oDocumentController = &getController('document');
                for($i=0;$i<$document_srl_count;$i++) {
                    $document_srl = $document_srl_list[$i];
                    $output = $oDocumentController->deleteDocument($document_srl, true);
                    if(!$output->toBool()) return new Object(-1, 'fail_to_delete');
                }
                $oDB->commit();
                $msg_code = 'success_deleted';
            }

            $_SESSION['document_management'] = array();

            $this->setMessage($msg_code);
        }

        /** 
         * @brief 특정 게시물들의 소속 모듈 변경 (게시글 이동시에 사용)
         **/
        function moveDocumentModule($document_srl_list, $module_srl, $category_srl) {
            if(!count($document_srl_list)) return;

            $oDocumentModel = &getModel('document');
            $oDocumentController = &getController('document');

            $oDB = &DB::getInstance();
            $oDB->begin();

            for($i=count($document_srl_list)-1;$i>=0;$i--) {
                $document_srl = $document_srl_list[$i];
                $oDocument = $oDocumentModel->getDocument($document_srl);
                if(!$oDocument->isExists()) continue;

                $source_category_srl = $oDocument->get('category_srl');

                unset($obj);
                $obj = $oDocument->getObjectVars();

                // 대상 모듈이 다를 경우 첨부파일 이동
                if($module_srl != $obj->module_srl && $oDocument->hasUploadedFiles()) {
                    $oFileController = &getController('file');

                    $files = $oDocument->getUploadedFiles();
                    foreach($files as $key => $val) {
                        $file_info = array();
                        $file_info['tmp_name'] = $val->uploaded_filename;
                        $file_info['name'] = $val->source_filename;
                        $inserted_file = $oFileController->insertFile($file_info, $module_srl, $obj->document_srl, $val->download_count, true);
                        if($inserted_file && $inserted_file->toBool()) {
                            // 이미지/동영상등일 경우
                            if($val->direct_download == 'Y') {
                                $source_filename = substr($val->uploaded_filename,2);
                                $target_filename = substr($inserted_file->get('uploaded_filename'),2);
                                $obj->content = str_replace($source_filename, $target_filename, $obj->content);

                            // binary 파일일 경우
                            } else {
                                $obj->content = str_replace('file_srl='.$val->file_srl, 'file_srl='.$inserted_file->get('file_srl'), $obj->content);
                                $obj->content = str_replace('sid='.$val->sid, 'sid='.$inserted_file->get('sid'), $obj->content);
                            }
                        }

                        // 기존 파일 삭제
                        $oFileController->deleteFile($val->file_srl);
                    }

                    // 등록된 모든 파일을 유효로 변경
                    $oFileController->setFilesValid($obj->document_srl);
                }

                // 게시물의 모듈 이동
                $obj->module_srl = $module_srl;
                $obj->category_srl = $category_srl;
                $output = executeQuery('document.updateDocumentModule', $obj);
                if(!$output->toBool()) {
                    $oDB->rollback();
                    return $output;
                }

                // 카테고리가 변경되었으면 검사후 없는 카테고리면 0으로 세팅
                if($source_category_srl != $category_srl) {
                    if($source_category_srl) $oDocumentController->updateCategoryCount($oDocument->get('module_srl'), $source_category_srl);
                    if($category_srl) $oDocumentController->updateCategoryCount($module_srl, $category_srl);
                }

            }

            $args->document_srls = implode(',',$document_srl_list);
            $args->module_srl = $module_srl;

            // 댓글의 이동
            $output = executeQuery('comment.updateCommentModule', $args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            $output = executeQuery('comment.updateCommentListModule', $args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            // 엮인글의 이동
            $output = executeQuery('trackback.updateTrackbackModule', $args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            // 태그
            $output = executeQuery('tag.updateTagModule', $args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }
            
            $oDB->commit();
            return new Object();
        }

        /** 
         * @brief 게시글의 복사
         **/
        function copyDocumentModule($document_srl_list, $module_srl, $category_srl) {
            if(!count($document_srl_list)) return;

            $oDocumentModel = &getModel('document');
            $oDocumentController = &getController('document');

            $oFileModel = &getModel('file');

            $oDB = &DB::getInstance();
            $oDB->begin();

            for($i=count($document_srl_list)-1;$i>=0;$i--) {
                $document_srl = $document_srl_list[$i];
                $oDocument = $oDocumentModel->getDocument($document_srl);
                if(!$oDocument->isExists()) continue;

                $obj = null;
                $obj = $oDocument->getObjectVars();
                $obj->module_srl = $module_srl;
                $obj->document_srl = getNextSequence();
                $obj->category_srl = $category_srl;
                $obj->password_is_hashed = true;
                $obj->comment_count = 0;
                $obj->trackback_count = 0;

                // 첨부파일 미리 등록
                if($oDocument->hasUploadedFiles()) {
                    $files = $oDocument->getUploadedFiles();
                    foreach($files as $key => $val) {
                        $file_info = array();
                        $file_info['tmp_name'] = $val->uploaded_filename;
                        $file_info['name'] = $val->source_filename;
                        $oFileController = &getController('file');
                        $inserted_file = $oFileController->insertFile($file_info, $module_srl, $obj->document_srl, 0, true);

                        // 이미지/동영상등일 경우
                        if($val->direct_download == 'Y') {
                            $source_filename = substr($val->uploaded_filename,2);
                            $target_filename = substr($inserted_file->get('uploaded_filename'),2);
                            $obj->content = str_replace($source_filename, $target_filename, $obj->content);

                        // binary 파일일 경우
                        } else {
                            $obj->content = str_replace('file_srl='.$val->file_srl, 'file_srl='.$inserted_file->get('file_srl'), $obj->content);
                            $obj->content = str_replace('sid='.$val->sid, 'sid='.$inserted_file->get('sid'), $obj->content);
                        }
                    }
                }
                
                // 글의 등록
                $output = $oDocumentController->insertDocument($obj, true);
                if(!$output->toBool()) {
                    $oDB->rollback();
                    return $output;
                }

                // 댓글 이전
                if($oDocument->getCommentCount()) {
                    $oCommentModel = &getModel('comment');
                    $comment_output = $oCommentModel->getCommentList($document_srl, true);
                    $comments = $comment_output->data;
                    if(count($comments)) {
                        $oCommentController = &getController('comment');
                        $success_count = 0;
                        $p_comment_srl = array();
                        foreach($comments as $comment_obj) {
                            $comment_srl = getNextSequence();
                            $p_comment_srl[$comment_obj->comment_srl] = $comment_srl;

                            $comment_obj->module_srl = $obj->module_srl;
                            $comment_obj->document_srl = $obj->document_srl;
                            $comment_obj->comment_srl = $comment_srl;

                            if($comment_obj->parent_srl) $comment_obj->parent_srl = $p_comment_srl[$comment_obj->parent_srl];

                            $output = $oCommentController->insertComment($comment_obj, true);
                            if($output->toBool()) $success_count ++;
                        }
                        $oDocumentController->updateCommentCount($obj->document_srl, $success_count, $comment_obj->nick_name, true);

                    }

                }

                // 엮인글 이전
                if($oDocument->getTrackbackCount()) {
                    $oTrackbackModel = &getModel('trackback');
                    $trackbacks = $oTrackbackModel->getTrackbackList($oDocument->document_srl);
                    if(count($trackbacks)) {
                        $success_count = 0;
                        foreach($trackbacks as $trackback_obj) {
                            $trackback_obj->trackback_srl = getNextSequence();
                            $trackback_obj->module_srl = $obj->module_srl;
                            $trackback_obj->document_srl = $obj->document_srl;
                            $output = executeQuery('trackback.insertTrackback', $trackback_obj);
                            if($output->toBool()) $success_count++;
                        }

                        // 엮인글 수 업데이트
                        $oDocumentController->updateTrackbackCount($obj->document_srl, $success_count);
                    }
                }
            }
            
            $oDB->commit();
            return new Object();
        }

        /**
         * @brief 특정 모듈의 전체 문서 삭제
         **/
        function deleteModuleDocument($module_srl) {
            $args->module_srl = $module_srl;
            $output = executeQuery('document.deleteModuleDocument', $args);
            return $output;
        }

        /**
         * @brief 문서 모듈의 기본설정 저장
         **/
        function procDocumentAdminInsertConfig() {
            // 기본 정보를 받음
            $args = Context::gets('thumbnail_type');

            // module Controller 객체 생성하여 입력
            $oModuleController = &getController('module');
            $output = $oModuleController->insertModuleConfig('document',$args);
            return $output;
        }

        /**
         * @brief 선택된 글들에 대해 신고 취소
         **/
        function procDocumentAdminCancelDeclare() {
            $document_srl = trim(Context::get('document_srl'));

            if($document_srl) {
                $args->document_srl = $document_srl;
                $output = executeQuery('document.deleteDeclaredDocuments', $args);
                if(!$output->toBool()) return $output;
            }
        }

        /**
         * @brief 모든 생성된 썸네일 삭제
         **/
        function procDocumentAdminDeleteAllThumbnail() {

            // files/attaches/images/ 디렉토리를 순환하면서 thumbnail_*.jpg 파일을 모두 삭제 (1.0.4 이전까지)
            $this->deleteThumbnailFile('./files/attach/images');

            // files/cache/thumbnails 디렉토리 자체를 삭제 (1.0.5 이후 변경된 썸네일 정책)
            FileHandler::removeFilesInDir('./files/cache/thumbnails');

            $this->setMessage('success_deleted');
        }

        function deleteThumbnailFile($path) {
            $directory = dir($path);
            while($entry = $directory->read()) {
                if ($entry != "." && $entry != "..") {
                    if (is_dir($path."/".$entry)) {
                        $this->deleteThumbnailFile($path."/".$entry);
                    } else {
                        if(!preg_match('/^thumbnail_([^\.]*)\.jpg$/i',$entry)) continue;
                        FileHandler::removeFile($path.'/'.$entry);
                    }
                }
            }
            $directory->close();
        }
 
    }
?>
