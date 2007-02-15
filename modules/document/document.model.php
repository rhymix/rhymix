<?php
    /**
     * @class  documentModel
     * @author zero (zero@nzeo.com)
     * @brief  document 모듈의 model 클래스
     **/

    class documentModel extends Module {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief document에 대한 권한을 세션값으로 체크
         **/
        function isGranted($document_srl) {
            return $_SESSION['own_document'][$document_srl];
        }

        /**
         * @brief 문서 가져오기
         **/
        function getDocument($document_srl) {
            // DB에서 가져옴
            $oDB = &DB::getInstance();
            $args->document_srl = $document_srl;
            $output = $oDB->executeQuery('document.getDocument', $args);
            $document = $output->data;

            // 이 문서에 대한 권한이 있는지 확인
            if($this->isGranted($document->document_srl)) {
                $document->is_granted = true;
            } elseif($document->member_srl) {
                $oMember = getModule('member');
                $member_srl = $oMember->getMemberSrl();
                if($member_srl && $member_srl ==$document->member_srl) $document->is_granted = true;
            } 
            return $document;
        }

        /**
         * @brief 여러개의 문서들을 가져옴 (페이징 아님)
         **/
        function getDocuments($document_srl_list) {
            if(is_array($document_srl_list)) $document_srls = implode(',',$document_srl_list);

            // DB에서 가져옴
            $oDB = &DB::getInstance();
            $args->document_srls = $document_srls;
            $output = $oDB->executeQuery('document.getDocuments', $args);
            $document_list = $output->data;
            if(!$document_list) return;

            // 권한 체크
            $oMemberModel = getModel('member');
            $member_srl = $oMemberModel->getMemberSrl();

            $document_count = count($document_list);
            for($i=0;$i<$document_count;$i++) {
                $document = $document_list[$i];
                $is_granted = false;

                if($this->isGranted($document->document_srl)) {
                    $is_granted = true;
                } elseif($member_srl && $member_srl == $document->member_srl) {
                    $is_granted = true;
                } 
                $document_list[$i]->is_granted = $is_granted;
            }
            return $document_list;
        }

        /**
         * @brief module_srl에 해당하는 문서의 전체 갯수를 가져옴
         **/
        function getDocumentCount($module_srl, $search_obj = NULL) {
            $oDB = &DB::getInstance();

            $args->module_srl = $module_srl;
            $args->s_title = $search_obj->s_title;
            $args->s_content = $search_obj->s_content;
            $args->s_user_name = $search_obj->s_user_name;
            $args->s_member_srl = $search_obj->s_member_srl;
            $args->s_ipaddress = $search_obj->s_ipaddress;
            $args->s_regdate = $search_obj->s_regdate;
            $output = $oDB->executeQuery('document.getDocumentCount', $args);
            $total_count = $output->data->count;
            return (int)$total_count;
        }

        /**
         * @brief module_srl값을 가지는 문서의 목록을 가져옴
         **/
        function getDocumentList($module_srl, $sort_index = 'list_order', $page = 1, $list_count = 20, $page_count = 10, $search_obj = NULL) {
            $oDB = &DB::getInstance();

            $args->module_srl = $module_srl;
            $args->s_title = $search_obj->s_title;
            $args->s_content = $search_obj->s_content;
            $args->s_user_name = $search_obj->s_user_name;
            $args->s_member_srl = $search_obj->s_member_srl;
            $args->s_ipaddress = $search_obj->s_ipaddress;
            $args->s_regdate = $search_obj->s_regdate;
            $args->category_srl = $search_obj->category_srl;

            $args->sort_index = $sort_index;
            $args->page = $page;
            $args->list_count = $list_count;
            $args->page_count = $page_count;
            $output = $oDB->executeQuery('document.getDocumentList', $args);

            if(!count($output->data)) return $output;

            // 권한 체크
            $oMemberModel = getModel('member');
            $member_srl = $oMemberModel->getMemberSrl();

            foreach($output->data as $key => $document) {
                $is_granted = false;

                if($this->isGranted($document->document_srl)) $is_granted = true;
                elseif($member_srl && $member_srl == $document->member_srl) $is_granted = true;

                $output->data[$key]->is_granted = $is_granted;
            }
            return $output;
        }

        /**
         * @brief 해당 document의 page 가져오기, module_srl이 없으면 전체에서..
         **/
        function getDocumentPage($document_srl, $module_srl=0, $list_count) {
            $oDB = &DB::getInstance();

            $args->document_srl = $document_srl;
            $args->module_srl = $module_srl;
            $output = $oDB->executeQuery('document.getDocumentPage', $args);

            $count = $output->data->count;
            $page = (int)(($count-1)/$list_count)+1;
            return $page;
        }

        /**
         * @brief 카테고리의 정보를 가져옴
         **/
        function getCategory($category_srl) {
            $oDB = &DB::getInstance();

            $args->category_srl = $category_srl;
            $output = $oDB->executeQuery('document.getCategory', $args);
            return $output->data;
        }

        /**
         * @brief 특정 모듈의 카테고리 목록을 가져옴
         **/
        function getCategoryList($module_srl) {
            $oDB = &DB::getInstance();

            $args->module_srl = $module_srl;
            $args->sort_index = 'list_order';
            $output = $oDB->executeQuery('document.getCategoryList', $args);

            $category_list = $output->data;

            if(!$category_list) return NULL;
            if(!is_array($category_list)) $category_list = array($category_list);

            $category_count = count($category_list);
            for($i=0;$i<$category_count;$i++) {
                $category_srl = $category_list[$i]->category_srl;
                $list[$category_srl] = $category_list[$i];
            }
            return $list;
        }

        /**
         * @brief 카테고리에 속한 문서의 갯수를 구함
         **/
        function getCategoryDocumentCount($category_srl) {
            $oDB = &DB::getInstance();

            $args->category_srl = $category_srl;
            $output = $oDB->executeQuery('document.getCategoryDocumentCount', $args);
            return (int)$output->data->count;
        }

        /**
         * @brief 특정 문서에 속한 첨부파일의 개수를 return
         **/
        function getFilesCount($document_srl) {
            $oDB = &DB::getInstance();

            $args->document_srl = $document_srl;
            $output = $oDB->executeQuery('document.getFilesCount', $args);
            return (int)$output->data->count;
        }

        /**
         * @brief 파일 정보를 구함
         **/
        function getFile($file_srl) {
            $oDB = &DB::getInstance();

            $args->file_srl = $file_srl;
            $output = $oDB->executeQuery('document.getFile', $args);
            return $output->data;
        }

        /**
         * @brief 특정 문서에 속한 파일을 모두 return
         **/
        function getFiles($document_srl) {
            $oDB = &DB::getInstance();

            $args->document_srl = $document_srl;
            $args->sort_index = 'file_srl';
            $output = $oDB->executeQuery('document.getFiles', $args);

            $file_list = $output->data;

            if($file_list && !is_array($file_list)) $file_list = array($file_list);

            for($i=0;$i<count($file_list);$i++) {
                $direct_download = $file_list[$i]->direct_download;

                if($direct_download!='Y') continue;

                $uploaded_filename = Context::getRequestUri().substr($file_list[$i]->uploaded_filename,2);

                $file_list[$i]->uploaded_filename = $uploaded_filename;
            }
            return $file_list;
        }

        /**
         * @brief 내용의 플러그인이나 기타 기능에 대한 code를 실제 code로 변경
         **/
        function transContent($content) {
            // 멀티미디어 코드의 변환
            $content = preg_replace_callback('!<img([^\>]*)editor_multimedia([^\>]*?)>!is', array('Document','_transMultimedia'), $content);

            // <br> 코드 변환
            $content = str_replace(array("<BR>","<br>","<Br>"),"<br />", $content);

            // <img ...> 코드를 <img ... /> 코드로 변환
            $content = preg_replace('!<img(.*?)(\/){0,1}>!is','<img\\1 />', $content);

            return $content;
        }

        /**
         * @brief 내용의 멀티미디어 태그를 html 태그로 변경
         * <img ... class="multimedia" ..> 로 되어 있는 코드를 변경
         **/
        function _transMultimedia($matches) {
            preg_match("/style\=(\"|'){0,1}([^\"\']+)(\"|'){0,1}/i",$matches[0], $buff);
            $style = str_replace("\"","'",$buff[0]);
            preg_match("/alt\=\"{0,1}([^\"]+)\"{0,1}/i",$matches[0], $buff);
            $opt = explode('|@|',$buff[1]);
            if(count($opt)<1) return $matches[0];

            for($i=0;$i<count($opt);$i++) {
                $pos = strpos($opt[$i],"=");
                $cmd = substr($opt[$i],0,$pos);
                $val = substr($opt[$i],$pos+1);
                $obj->{$cmd} = $val;
            }
            return sprintf("<script type=\"text/javascript\">displayMultimedia(\"%s\", \"%s\", \"%s\");</script>", $obj->type, $obj->src, $style);
        }
    }
?>
