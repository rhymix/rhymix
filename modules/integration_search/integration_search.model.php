<?php
    /**
     * @class  integrationModel
     * @author zero (zero@nzeo.com)
     * @brief  integration 모듈의 Model class
     **/

    class integration_searchModel extends module {
        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 게시글 검색
         **/
        function getDocuments($module_srls_list, $search_target, $search_keyword, $page=1, $list_count = 20) {
            if(is_array($module_srls_list)) $args->module_srl = implode(',',$module_srls_list);
            else $args->module_srl = $module_srls_list;
            $args->page = $page;
            $args->list_count = $list_count;
            $args->page_count = 10;
            $args->search_target = $search_target;
            $args->search_keyword = $search_keyword;
            $args->sort_index = 'list_order'; 
            $args->order_type = 'asc';

            // 대상 문서들을 가져옴
            $oDocumentModel = &getModel('document');
            return $oDocumentModel->getDocumentList($args);
        }

        /**
         * @brief 댓글 검색
         **/
        function getComments($module_srls_list, $search_keyword, $page=1, $list_count = 20) {
            if(is_array($module_srls_list)) $args->module_srl = implode(',',$module_srls_list);
            else $args->module_srl = $module_srls_list;
            $args->page = $page;
            $args->list_count = $list_count;
            $args->page_count = 10;
            $args->search_target = 'content';
            $args->search_keyword = $search_keyword;
            $args->sort_index = 'list_order'; 
            $args->order_type = 'asc';

            // 대상 문서들을 가져옴
            $oCommentModel = &getModel('comment');
            $output = $oCommentModel->getTotalCommentList($args);
            if(!$output->toBool()|| !$output->data) return $output;

            $list = array();
            foreach($output->data as $key => $val) {
                $oComment = new commentItem(0);
                $oComment->setAttribute($val);
                $list[$key] = $oComment;
            }
            $output->data = $list;
            return $output;
        }

        /**
         * @brief 엮인글 검색
         **/
        function getTrackbacks($module_srls_list, $search_target = "title", $search_keyword, $page=1, $list_count = 20) {
            if(is_array($module_srls_list)) $args->module_srl = implode(',',$module_srls_list);
            else $args->module_srl = $module_srls_list;
            $args->page = $page;
            $args->list_count = $list_count;
            $args->page_count = 10;
            $args->search_target = $search_target;
            $args->search_keyword = $search_keyword;
            $args->sort_index = 'list_order'; 
            $args->order_type = 'asc';

            // 대상 문서들을 가져옴
            $oTrackbackModel = &getAdminModel('trackback');
            $output = $oTrackbackModel->getTotalTrackbackList($args);
            if(!$output->toBool()|| !$output->data) return $output;
            return $output;
        }

        /**
         * @brief 파일 검색
         **/
        function _getFiles($module_srls_list, $search_keyword, $page, $list_count, $direct_download = 'Y') {
            if(is_array($module_srls_list)) $args->module_srl = implode(',',$module_srls_list);
            else $args->module_srl = $module_srls_list;
            $args->page = $page;
            $args->list_count = $list_count;
            $args->page_count = 10;
            $args->search_target = 'filename';
            $args->search_keyword = $search_keyword;
            $args->sort_index = 'files.file_srl'; 
            $args->order_type = 'desc';
            $args->isvalid = 'Y';
            $args->direct_download = $direct_download=='Y'?'Y':'N';

            // 대상 문서들을 가져옴
            $oFileAdminModel = &getAdminModel('file');
            $output = $oFileAdminModel->getFileList($args);
            if(!$output->toBool() || !$output->data) return $output;

            $list = array();
            foreach($output->data as $key => $val) {
                $obj = null;
                $obj->filename = $val->source_filename;
                $obj->download_count = $val->download_count;
                if(substr($val->download_url,0,2)=='./') $val->download_url = substr($val->download_url,2);
                $obj->download_url = Context::getRequestUri().$val->download_url;
                $obj->target_srl = $val->upload_target_srl;
                $obj->file_size = $val->file_size;

                // 이미지 
                if(preg_match('/\.(jpg|jpeg|gif|png)$/i', $val->source_filename)) {
                    $obj->type = 'image';

                    $thumbnail_path = sprintf('files/cache/thumbnails/%s',getNumberingPath($val->file_srl, 3));
                    if(!is_dir($thumbnail_path)) FileHandler::makeDir($thumbnail_path);
                    $thumbnail_file = sprintf('%s%dx%d.%s.jpg', $thumbnail_path, 120, 120, 'crop');
                    $thumbnail_url  = Context::getRequestUri().$thumbnail_file;
                    if(!file_exists($thumbnail_file)) FileHandler::createImageFile($val->uploaded_filename, $thumbnail_file, 120, 120, 'jpg', 'crop');
                    $obj->src = sprintf('<img src="%s" alt="%s" width="%d" height="%d" />', $thumbnail_url, htmlspecialchars($obj->filename), 120, 120);

                // 동영상
                } elseif(preg_match('/\.(swf|flv|wmv|avi|mpg|mpeg|asx|asf|mp3)$/i', $val->source_filename)) {
                    $obj->type = 'multimedia';
                    $obj->src = sprintf('<script type="text/javascript">displayMultimedia("%s",120,120);</script>', $obj->download_url);

                // 기타
                } else {
                    $obj->type = 'binary';
                    $obj->src = '';
                }

                $list[] = $obj;
                $target_list[] = $val->upload_target_srl;
            }
            $output->data = $list;

            $oDocumentModel = &getModel('document');
            $document_list = $oDocumentModel->getDocuments($target_list);
            if($document_list) foreach($document_list as $key => $val) {
                foreach($output->data as $k => $v) {
                    if($v->target_srl== $val->document_srl) {
                        $output->data[$k]->url = $val->getPermanentUrl();
                        $output->data[$k]->regdate = $val->getRegdate("Y-m-d H:i");
                        $output->data[$k]->nick_name = $val->getNickName();
                    }
                }
            }

            $oCommentModel = &getModel('comment');
            $comment_list = $oCommentModel->getComments($target_list);
            if($comment_list) foreach($comment_list as $key => $val) {
                foreach($output->data as $k => $v) {
                    if($v->target_srl== $val->comment_srl) {
                        $output->data[$k]->url = $val->getPermanentUrl();
                        $output->data[$k]->regdate = $val->getRegdate("Y-m-d H:i");
                        $output->data[$k]->nick_name = $val->getNickName();
                    }
                }
            }

            return $output;
        }

        /**
         * @brief 멀티미디어 검색
         **/
        function getImages($module_srls_list, $search_keyword, $page=1, $list_count = 20) {
            return $this->_getFiles($module_srls_list, $search_keyword, $page, $list_count);
        }

        /**
         * @brief 첨부파일 검색
         **/
        function getFiles($module_srls_list, $search_keyword, $page=1, $list_count = 20) {
            return $this->_getFiles($module_srls_list, $search_keyword, $page, $list_count, 'N');
        }

    }
?>
