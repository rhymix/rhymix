<?php
    /**
     * @class  fileModel
     * @author zero (zero@nzeo.com)
     * @brief  file 모듈의 model 클래스
     **/

    class fileModel extends file {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 특정 문서에 속한 첨부파일의 개수를 return
         **/
        function getFilesCount($upload_target_srl) {
            $args->upload_target_srl = $upload_target_srl;
            $output = executeQuery('file.getFilesCount', $args);
            return (int)$output->data->count;
        }

        /**
         * @brief 다운로드 경로를 구함
         **/
        function getDownloadUrl($file_srl, $sid) {
            return "./?module=file&amp;act=procFileDownload&amp;file_srl=".$file_srl."&amp;sid=".$sid;
        }

        /**
         * @brief 파일 정보를 구함
         **/
        function getFile($file_srl) {
            $args->file_srl = $file_srl;
            $output = executeQuery('file.getFile', $args);
            if(!$output->toBool()) return $output;

            $file = $output->data;
            $file->download_url = $this->getDownloadUrl($file->file_srl, $file->sid);

            return $file;
        }

        /**
         * @brief 특정 문서에 속한 파일을 모두 return
         **/
        function getFiles($upload_target_srl) {
            $args->upload_target_srl = $upload_target_srl;
            $args->sort_index = 'file_srl';
            $output = executeQuery('file.getFiles', $args);
            if(!$output->data) return;

            $file_list = $output->data;

            if($file_list && !is_array($file_list)) $file_list = array($file_list);

            $file_count = count($file_list);
            for($i=0;$i<$file_count;$i++) {
                $file = $file_list[$i];
                $file->download_url = $this->getDownloadUrl($file->file_srl, $file->sid);
                $file_list[$i] = $file;
            }

            return $file_list;
        }

        /**
         * @brief 모든 첨부파일을 시간 역순으로 가져옴 (관리자용)
         **/
        function getFileList($obj) {
            // 검색 옵션 정리
            $search_target = trim(Context::get('search_target'));
            $search_keyword = trim(Context::get('search_keyword'));

            if($search_target && $search_keyword) {
                switch($search_target) {
                    case 'filename' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_filename = $search_keyword;
                        break;
                    case 'filesize' :
                            $args->s_filesize = (int)$search_keyword;
                        break;
                    case 'download_count' :
                            $args->s_download_count = (int)$search_keyword;
                        break;
                    case 'regdate' :
                            $args->s_regdate = $search_keyword;
                        break;
                    case 'ipaddress' :
                            $args->s_ipaddress= $search_keyword;
                        break;
                }
            }

            // 유효/대기 상태 설정
            if($obj->isvalid == 'Y') $args->isvalid = 'Y';
            elseif($obj->isvalid == 'N') $args->isvalid = 'N';

            // 변수 설정
            $args->sort_index = $obj->sort_index;
            $args->page = $obj->page?$obj->page:1;
            $args->list_count = $obj->list_count?$obj->list_count:20;
            $args->page_count = $obj->page_count?$obj->page_count:10;

            // file.getFileList쿼리 실행
            $output = executeQuery('file.getFileList', $args);

            // 결과가 없거나 오류 발생시 그냥 return
            if(!$output->toBool()||!count($output->data)) return $output;

            foreach($output->data as $key => $file) {
                $file->download_url = $this->getDownloadUrl($file->file_srl, $file->sid);
                $output->data[$key] = $file;
            }

            return $output;
        }

    }
?>
