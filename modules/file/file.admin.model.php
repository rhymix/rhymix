<?php
    /**
     * @class  fileAdminModel
     * @author zero (zero@nzeo.com)
     * @brief  file 모듈의 admin model 클래스
     **/

    class fileAdminModel extends file {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 모든 첨부파일을 시간 역순으로 가져옴 (관리자용)
         **/
        function getFileList($obj) {
            // 검색 옵션 정리
            $search_target = $obj->search_target?$obj->search_target:trim(Context::get('search_target'));
            $search_keyword = $obj->search_keyword?$obj->search_keyword:trim(Context::get('search_keyword'));

            if($search_target && $search_keyword) {
                switch($search_target) {
                    case 'filename' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_filename = $search_keyword;
                        break;
                    case 'filesize' :
                            $args->s_filesize = (int)$search_keyword;
                        break;
                    case 'filesize_mega' :
                            $args->s_filesize = (int)$search_keyword * 1024 * 1024;
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
                }
            }

            // 유효/대기 상태 설정
            if($obj->isvalid == 'Y') $args->isvalid = 'Y';
            elseif($obj->isvalid == 'N') $args->isvalid = 'N';

            // 멀티미디어/ 일반 상태 설정
            if($obj->direct_download == 'Y') $args->direct_download = 'Y';
            elseif($obj->direct_download == 'N') $args->direct_download= 'N';

            // 변수 설정
            $args->sort_index = $obj->sort_index;
            $args->page = $obj->page?$obj->page:1;
            $args->list_count = $obj->list_count?$obj->list_count:20;
            $args->page_count = $obj->page_count?$obj->page_count:10;
            $args->s_module_srl = $obj->module_srl;

            // file.getFileList쿼리 실행
            $output = executeQuery('file.getFileList', $args);

            // 결과가 없거나 오류 발생시 그냥 return
            if(!$output->toBool()||!count($output->data)) return $output;

            $oFileModel = &getModel('file');

            foreach($output->data as $key => $file) {
                $file->download_url = $oFileModel->getDownloadUrl($file->file_srl, $file->sid);
                $output->data[$key] = $file;
            }

            return $output;
        }

    }
?>
