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
            $document_srl = Context::get('srl');

            $oDocumentModel = &getModel('document');
            $oDocument = $oDocumentModel->getDocument($document_srl);
            if(!$oDocument->isExists()) return;

            $oDocument->doCart();
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

                $oMemberController = &getController('member');
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
                    $content .= sprintf("<div>%s</div><hr />%s<div style=\"font-weight:bold\">%s</div>%s",$message_content, $purl, $oDocument->getTitleText(), $oDocument->getContent());

                    $oMemberController->sendMessage($sender_member_srl, $oDocument->get('member_srl'), $title, $content, false);
                }
            }

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

                unset($document_args);
                $document_args->module_srl = $module_srl;
                $document_args->category_srl = $category_srl;
                $document_args->document_srl = $document_srl;

                // 게시물의 모듈 이동
                $output = executeQuery('document.updateDocumentModule', $document_args);
                if(!$output->toBool()) {
                    $oDB->rollback();
                    return $output;
                }

                // 카테고리가 변경되었으면 검사후 없는 카테고리면 0으로 세팅
                if($source_category_srl != $category_srl) {
                    if($source_category_srl) $oDocumentController->updateCategoryCount($source_category_srl);
                    if($category_srl) $oDocumentController->updateCategoryCount($category_srl);
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
            $oFileController = &getController('file');

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

                // 첨부파일 미리 등록
                if($oDocument->hasUploadedFiles()) {
                    $files = $oDocument->getUploadedFiles();
                    foreach($files as $key => $val) {
                        $file_info = array();
                        $file_info['tmp_name'] = $val->uploaded_filename;
                        $file_info['name'] = $val->source_filename;
                        $oFileController->insertFile($file_info, $module_srl, $obj->document_srl, 0, true);
                    }
                }
                
                // 글의 등록
                $output = $oDocumentController->insertDocument($obj, true);
                if(!$output->toBool()) {
                    $oDB->rollback();
                    return $output;
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
         * @brief 카테고리 추가
         **/
        function insertCategory($module_srl, $title, $category_srl = 0) {
            if(!$category_srl) $args->list_order = $args->category_srl = getNextSequence();
            else $args->list_order = $args->category_srl = $category_srl;

            $args->module_srl = $module_srl;
            $args->title = $title;
            $args->document_count = 0;

            $output = executeQuery('document.insertCategory', $args);
            if($output->toBool()) $output->add('category_srl', $args->category_srl);

            return $output;
        }

        /**
         * @brief 카테고리 정보 수정
         **/
        function updateCategory($args) {
            return executeQuery('document.updateCategory', $args);
        }

        /**
         * @brief 카테고리 삭제
         **/
        function deleteCategory($category_srl) {
            $args->category_srl = $category_srl;

            // 카테고리 정보를 삭제
            $output = executeQuery('document.deleteCategory', $args);
            if(!$output->toBool()) return $output;

            // 현 카테고리 값을 가지는 문서들의 category_srl을 0 으로 세팅
            unset($args);

            $args->target_category_srl = 0;
            $args->source_category_srl = $category_srl;
            $output = executeQuery('document.updateDocumentCategory', $args);
            return $output;
        }

        /**
         * @brief 특정 모듈의 카테고리를 모두 삭제
         **/
        function deleteModuleCategory($module_srl) {
            $args->module_srl = $module_srl;
            $output = executeQuery('document.deleteModuleCategory', $args);
            return $output;
        }

        /**
         * @brief 카테고리를 상단으로 이동
         **/
        function moveCategoryUp($category_srl) {
            $oDocumentModel = &getModel('document');

            // 선택된 카테고리의 정보를 구한다
            $args->category_srl = $category_srl;
            $output = executeQuery('document.getCategory', $args);

            $category = $output->data;
            $list_order = $category->list_order;
            $module_srl = $category->module_srl;

            // 전체 카테고리 목록을 구한다
            $category_list = $oDocumentModel->getCategoryList($module_srl);
            $category_srl_list = array_keys($category_list);
            if(count($category_srl_list)<2) return new Object();

            $prev_category = NULL;
            foreach($category_list as $key => $val) {
                if($key==$category_srl) break;
                $prev_category = $val;
            }

            // 이전 카테고리가 없으면 그냥 return
            if(!$prev_category) return new Object(-1,Context::getLang('msg_category_not_moved'));

            // 선택한 카테고리가 가장 위의 카테고리이면 그냥 return
            if($category_srl_list[0]==$category_srl) return new Object(-1,Context::getLang('msg_category_not_moved'));

            // 선택한 카테고리의 정보
            $cur_args->category_srl = $category_srl;
            $cur_args->list_order = $prev_category->list_order;
            $cur_args->title = $category->title;
            $this->updateCategory($cur_args);

            // 대상 카테고리의 정보
            $prev_args->category_srl = $prev_category->category_srl;
            $prev_args->list_order = $list_order;
            $prev_args->title = $prev_category->title;
            $this->updateCategory($prev_args);

            return new Object();
        }

        /** 
         * @brief 카테고리를 아래로 이동
         **/
        function moveCategoryDown($category_srl) {
            $oDocumentModel = &getModel('document');

            // 선택된 카테고리의 정보를 구한다
            $args->category_srl = $category_srl;
            $output = executeQuery('document.getCategory', $args);

            $category = $output->data;
            $list_order = $category->list_order;
            $module_srl = $category->module_srl;

            // 전체 카테고리 목록을 구한다
            $category_list = $oDocumentModel->getCategoryList($module_srl);
            $category_srl_list = array_keys($category_list);
            if(count($category_srl_list)<2) return new Object();

            for($i=0;$i<count($category_srl_list);$i++) {
                if($category_srl_list[$i]==$category_srl) break;
            }

            $next_category_srl = $category_srl_list[$i+1];
            if(!$category_list[$next_category_srl]) return new Object(-1,Context::getLang('msg_category_not_moved'));
            $next_category = $category_list[$next_category_srl];

            // 선택한 카테고리의 정보
            $cur_args->category_srl = $category_srl;
            $cur_args->list_order = $next_category->list_order;
            $cur_args->title = $category->title;
            $this->updateCategory($cur_args);

            // 대상 카테고리의 정보
            $next_args->category_srl = $next_category->category_srl;
            $next_args->list_order = $list_order;
            $next_args->title = $next_category->title;
            $this->updateCategory($next_args);

            return new Object();
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
         * @brief 모든 생성된 썸네일 삭제
         **/
        function procDocumentAdminDeleteAllThumbnail() {

            // files/attaches/images/ 디렉토리를 순환하면서 thumbnail_*.jpg 파일을 모두 삭제
            $this->deleteThumbnailFile('./files/attach/images');

            $this->setMessage('success_deleted');
        }

        function deleteThumbnailFile($path) {
            $directory = dir($path);
            while($entry = $directory->read()) {
                if ($entry != "." && $entry != "..") {
                    if (is_dir($path."/".$entry)) {
                        $this->deleteThumbnailFile($path."/".$entry);
                    } else {
                        if(!eregi('^thumbnail_([^\.]*)\.jpg$',$entry)) continue;
                        @unlink($path.'/'.$entry);
                    }
                }
            }
            $directory->close();
        }
 
    }
?>
