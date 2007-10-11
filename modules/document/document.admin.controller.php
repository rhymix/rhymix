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
         * @brief 특정 게시물들의 소속 모듈 변경 (게시글 이동시에 사용)
         **/
        function moveDocumentModule($document_srl_list, $module_srl, $source_module_srl) {
            if(!count($document_srl_list)) return;

            $args->document_srls = implode(',',$document_srl_list);
            $args->module_srl = $module_srl;

            $oDB = &DB::getInstance();
            $oDB->begin();

            // 게시물의 이동
            $output = executeQuery('document.updateDocumentModule', $args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

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

            // 첨부파일의 이동 (다운로드나 본문 첨부의 문제로 인하여 첨부파일은 이동하지 않기로 결정. 차후에 다시 고민)
            /*
            $image_dir = sprintf('./files/attach/images/%s/%s/', $source_module_srl, $document_srl);
            $binary_dir = sprintf('./files/attach/binaries/%s/%s/', $source_module_srl, $document_srl);

            $target_image_dir = sprintf('./files/attach/images/%s/%s/', $module_srl, $document_srl);
            $target_binary_dir = sprintf('./files/attach/binaries/%s/%s/', $module_srl, $document_srl);

            if(is_dir($image_dir)) {
                FileHandler::moveDir($image_dir, $target_image_dir);
                if(!is_dir($target_image_dir)) {
                    $oDB->rollback();
                    return new Object(-1,'fail');
                }
            }

            if(is_dir($binary_dir)) {
                FileHandler::moveDir($binary_dir, $target_binary_dir);
                if(!is_dir($target_binary_dir)) {
                    $oDB->rollback();
                    return new Object(-1,'fail');
                }
            }
            */
            
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
