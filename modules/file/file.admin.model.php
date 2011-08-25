<?php
    /**
     * @class  fileAdminModel
     * @author NHN (developers@xpressengine.com)
     * @brief admin model class of the file module
     **/

    class fileAdminModel extends file {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief Get all the attachments in order by time descending (for administrators)
         **/
        function getFileList($obj, $columnList = array()) {
			$this->_makeSearchParam($obj, $args);

            // Set valid/invalid state
            if($obj->isvalid == 'Y') $args->isvalid = 'Y';
            elseif($obj->isvalid == 'N') $args->isvalid = 'N';
            // Set multimedia/common file
            if($obj->direct_download == 'Y') $args->direct_download = 'Y';
            elseif($obj->direct_download == 'N') $args->direct_download= 'N';
            // Set variables
            $args->sort_index = $obj->sort_index;
            $args->page = $obj->page?$obj->page:1;
            $args->list_count = $obj->list_count?$obj->list_count:20;
            $args->page_count = $obj->page_count?$obj->page_count:10;
            $args->s_module_srl = $obj->module_srl;
            $args->exclude_module_srl = $obj->exclude_module_srl;
            // Execute the file.getFileList query
            $output = executeQuery('file.getFileList', $args, $columnList);
            // Return if no result or an error occurs
            if(!$output->toBool()||!count($output->data)) return $output;

            $oFileModel = &getModel('file');

            foreach($output->data as $key => $file) {
				if($_SESSION['file_management'][$file->file_srl]) $file->isCarted = true;
				else $file->isCarted = false;

                $file->download_url = $oFileModel->getDownloadUrl($file->file_srl, $file->sid);
                $output->data[$key] = $file;
            }

            return $output;
        }

        /**
         * @brief Return number of attachments which belongs to a specific document
         **/
        function getFilesCountByGroupValid($obj = '') {
			//$this->_makeSearchParam($obj, $args);

            $output = executeQueryArray('file.getFilesCountByGroupValid', $args);
            return $output->data;
        }

        /**
         * @brief Return number of attachments which belongs to a specific document
         **/
        function getFilesCountByDate($date = '') {
			if($date) $args->regDate = date('Ymd', strtotime($date));

            $output = executeQuery('file.getFilesCount', $args);
			if(!$output->toBool()) return 0;

			return $output->data->count;
        }

		function _makeSearchParam(&$obj, &$args)
		{
            // Search options
            $search_target = $obj->search_target?$obj->search_target:trim(Context::get('search_target'));
            $search_keyword = $obj->search_keyword?$obj->search_keyword:trim(Context::get('search_keyword'));

            if($search_target && $search_keyword) {
                switch($search_target) {
                    case 'filename' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_filename = $search_keyword;
                        break;
                    case 'filesize_more' :
                            $args->s_filesize_more = (int)$search_keyword;
                        break;
                    case 'filesize_mega_more' :
                            $args->s_filesize_more = (int)$search_keyword * 1024 * 1024;
                        break;
					case 'filesize_less' :
                            $args->s_filesize_less = (int)$search_keyword;
                        break;
					case 'filesize_mega_less' :
                            $args->s_filesize_less = (int)$search_keyword * 1024 * 1024;
                        break;
                    case 'download_count' :
                            $args->s_download_count = (int)$search_keyword;
                        break;
                    case 'regdate' :
                            $args->s_regdate = $search_keyword;
                        break;
                    case 'ipaddress' :
                            $args->s_ipaddress = $search_keyword;
                        break;
                    case 'user_id' :
                            $args->s_user_id = $search_keyword;
                        break;
                    case 'user_name' :
                            $args->s_user_name = $search_keyword;
                        break;
                    case 'nick_name' :
                            $args->s_nick_name = $search_keyword;
                        break;
                    case 'isvalid' :
                            $args->isvalid = $search_keyword;
                        break;
                }
            }
		}
    }
?>
