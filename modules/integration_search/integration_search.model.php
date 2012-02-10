<?php
    /**
     * @class  integrationModel
     * @author NHN (developers@xpressengine.com)
     * @brief Model class of integration module
     **/

    class integration_searchModel extends module {
        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief Search documents
         **/
        function getDocuments($target, $module_srls_list, $search_target, $search_keyword, $page=1, $list_count = 20) {
            if(is_array($module_srls_list)) $module_srls_list = implode(',',$module_srls_list);

            if($target == 'exclude') {
				$module_srls_list .= ',0'; // exclude 'trash'
				if ($module_srls_list{0} == ',') $module_srls_list = substr($module_srls_list, 1);
            	$args->exclude_module_srl = $module_srls_list;
            } else {
            	$args->module_srl = $module_srls_list;
            	$args->exclude_module_srl = '0'; // exclude 'trash'
            }

            $args->page = $page;
            $args->list_count = $list_count;
            $args->page_count = 10;
            $args->search_target = $search_target;
            $args->search_keyword = $search_keyword;
            $args->sort_index = 'list_order'; 
            $args->order_type = 'asc';
			$args->statusList = array('PUBLIC');
            if(!$args->module_srl) unset($args->module_srl);
            // Get a list of documents
            $oDocumentModel = &getModel('document');

            return $oDocumentModel->getDocumentList($args);
        }

        /**
         * @brief Comment Search
         **/
        function getComments($target, $module_srls_list, $search_keyword, $page=1, $list_count = 20) {
            if(is_array($module_srls_list)){
				if (count($module_srls_list) > 0) $module_srls = implode(',',$module_srls_list); 
				else $module_srls = 0; 
			}
            else {
				$module_srls = ($module_srls_list)?$module_srls_list:0;
			}
            if($target == 'exclude') $args->exclude_module_srl = $module_srls;
            else $args->module_srl = $module_srls;

            $args->page = $page;
            $args->list_count = $list_count;
            $args->page_count = 10;
            $args->search_target = 'content';
            $args->search_keyword = $search_keyword;
            $args->sort_index = 'list_order'; 
            $args->order_type = 'asc';
            // Get a list of documents
            $oCommentModel = &getModel('comment');
            $output = $oCommentModel->getTotalCommentList($args);
            if(!$output->toBool()|| !$output->data) return $output;
            return $output;
        }

        /**
         * @brief Search trackbacks
         **/
        function getTrackbacks($target, $module_srls_list, $search_target = "title", $search_keyword, $page=1, $list_count = 20) {
            if(is_array($module_srls_list)) $module_srls = implode(',',$module_srls_list);
            else $module_srls = $module_srls_list;
            if($target == 'exclude') $args->exclude_module_srl = $module_srls;
            else $args->module_srl = $module_srls;
            $args->page = $page;
            $args->list_count = $list_count;
            $args->page_count = 10;
            $args->search_target = $search_target;
            $args->search_keyword = $search_keyword;
            $args->sort_index = 'list_order'; 
            $args->order_type = 'asc';
            // Get a list of documents
            $oTrackbackModel = &getAdminModel('trackback');
            $output = $oTrackbackModel->getTotalTrackbackList($args);
            if(!$output->toBool()|| !$output->data) return $output;
            return $output;
        }

        /**
         * @brief File Search
         **/
        function _getFiles($target, $module_srls_list, $search_keyword, $page, $list_count, $direct_download = 'Y') {
            if(is_array($module_srls_list)) $module_srls = implode(',',$module_srls_list);
            else $module_srls = $module_srls_list;
            if($target == 'exclude') $args->exclude_module_srl = $module_srls;
            else $args->module_srl = $module_srls;
            $args->page = $page;
            $args->list_count = $list_count;
            $args->page_count = 10;
            $args->search_target = 'filename';
            $args->search_keyword = $search_keyword;
            $args->sort_index = 'files.file_srl'; 
            $args->order_type = 'desc';
            $args->isvalid = 'Y';
            $args->direct_download = $direct_download=='Y'?'Y':'N';
            // Get a list of documents
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
                // Images
                if(preg_match('/\.(jpg|jpeg|gif|png)$/i', $val->source_filename)) {
                    $obj->type = 'image';

                    $thumbnail_path = sprintf('files/cache/thumbnails/%s',getNumberingPath($val->file_srl, 3));
                    if(!is_dir($thumbnail_path)) FileHandler::makeDir($thumbnail_path);
                    $thumbnail_file = sprintf('%s%dx%d.%s.jpg', $thumbnail_path, 120, 120, 'crop');
                    $thumbnail_url  = Context::getRequestUri().$thumbnail_file;
                    if(!file_exists($thumbnail_file)) FileHandler::createImageFile($val->uploaded_filename, $thumbnail_file, 120, 120, 'jpg', 'crop');
                    $obj->src = sprintf('<img src="%s" alt="%s" width="%d" height="%d" />', $thumbnail_url, htmlspecialchars($obj->filename), 120, 120);
                // Videos
                } elseif(preg_match('/\.(swf|flv|wmv|avi|mpg|mpeg|asx|asf|mp3)$/i', $val->source_filename)) {
                    $obj->type = 'multimedia';
                    $obj->src = sprintf('<script type="text/javascript">displayMultimedia("%s",120,120);</script>', $obj->download_url);
                // Others
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
         * @brief Multimedia Search
         **/
        function getImages($target, $module_srls_list, $search_keyword, $page=1, $list_count = 20) {
            return $this->_getFiles($target, $module_srls_list, $search_keyword, $page, $list_count);
        }

        /**
         * @brief Search for attachments
         **/
        function getFiles($target, $module_srls_list, $search_keyword, $page=1, $list_count = 20) {
            return $this->_getFiles($target, $module_srls_list, $search_keyword, $page, $list_count, 'N');
        }

    }
?>
