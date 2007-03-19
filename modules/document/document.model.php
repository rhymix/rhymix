<?php
    /**
     * @class  documentModel
     * @author zero (zero@nzeo.com)
     * @brief  document 모듈의 model 클래스
     **/

    class documentModel extends document {

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
        function getDocument($document_srl, $is_admin=false, $get_extra_info=false) {
            // DB에서 가져옴
            $oDB = &DB::getInstance();
            $args->document_srl = $document_srl;
            $output = $oDB->executeQuery('document.getDocument', $args);
            $document = $output->data;

            // 이 문서에 대한 권한이 있는지 확인
            if($this->isGranted($document->document_srl) || $is_admin) {
                $document->is_granted = true;
            } elseif($document->member_srl) {
                $oMemberModel = &getModel('member');
                $member_srl = $oMemberModel->getLoggedMemberSrl();
                if($member_srl && $member_srl ==$document->member_srl) $document->is_granted = true;
            } 

            // 비밀글이고 권한이 없을 경우 제목과 내용을 숨김
            if($document->is_secret=='Y' && !$document->is_granted) {
                $document->title =  $document->content = Context::getLang('msg_is_secret');
            }
            
            // 확장 정보(코멘트나 기타 등등) 플래그가 false이면 기본 문서 정보만 return
            if(!$get_extra_info) return $document;

            // document controller 객체 생성
            $oDocumentController = &getController('document');

            // 조회수 업데이트
            if($buff = $oDocumentController->updateReadedCount($document)) $document->readed_count++;

            // 댓글 가져오기
            if($document->comment_count && $document->allow_comment == 'Y') {
                $oCommentModel = &getModel('comment');
                $document->comment_list = $oCommentModel->getCommentList($document_srl, $is_admin);
            }

            // 트랙백 가져오기
            if($document->trackback_count && $document->allow_trackback == 'Y') {
                $oTrackbackModel = &getModel('trackback');
                $document->trackback_list = $oTrackbackModel->getTrackbackList($document_srl, $is_admin);
            }

            // 첨부파일 가져오기
            if($document->uploaded_count) {
                $oFileModel = &getModel('file');
                $file_list = $oFileModel->getFiles($document_srl, $is_admin);
                $document->uploaded_list = $file_list;
            }
            
            return $document;
        }

        /**
         * @brief 여러개의 문서들을 가져옴 (페이징 아님)
         **/
        function getDocuments($document_srl_list, $is_admin=false) {
            if(is_array($document_srl_list)) $document_srls = implode(',',$document_srl_list);

            // DB에서 가져옴
            $oDB = &DB::getInstance();
            $args->document_srls = $document_srls;
            $output = $oDB->executeQuery('document.getDocuments', $args);
            $document_list = $output->data;
            if(!$document_list) return;

            // 권한 체크
            $oMemberModel = &getModel('member');
            $member_srl = $oMemberModel->getLoggedMemberSrl();

            $document_count = count($document_list);
            for($i=0;$i<$document_count;$i++) {
                $document = $document_list[$i];
                $is_granted = false;

                if($this->isGranted($document->document_srl) || $is_admin) {
                    $is_granted = true;
                } elseif($member_srl && $member_srl == $document->member_srl) {
                    $is_granted = true;
                } 
                $document_list[$i]->is_granted = $is_granted;
            }
            return $document_list;
        }

        /**
         * @brief module_srl값을 가지는 문서의 목록을 가져옴
         **/
        function getDocumentList($obj) {

            // DB 객체 생성
            $oDB = &DB::getInstance();

            if(!in_array($obj->sort_index, array('list_order', 'update_order'))) $obj->sort_index = 'list_order';

            // 변수 설정
            $args->module_srl = $obj->module_srl;
            $args->category_srl = $obj->category_srl?$obj->category_srl:'';

            $args->sort_index = $obj->sort_index;
            $args->page = $obj->page?$obj->page:1;
            $args->list_count = $obj->list_count?$obj->list_count:20;
            $args->page_count = $obj->page_count?$obj->page_count:10;
            $query_id = 'document.getDocumentList';

            // 검색 옵션 정리
            $search_target = trim(Context::get('search_target'));
            $search_keyword = trim(Context::get('search_keyword'));
            if($search_target && $search_keyword) {
                switch($search_target) {
                    case 'title' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_title = $search_keyword;
                        break;
                    case 'content' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_content = $search_keyword;
                        break;
                    case 'user_id' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_user_id = $search_keyword;
                            $query_id = 'document.getDocumentListWithinMember';
                            $args->sort_index = 'documents.'.$args->sort_index;
                        break;
                    case 'member_srl' :
                            $args->s_member_srl = (int)$search_keyword;
                        break;
                    case 'user_name' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_user_name = $search_keyword;
                        break;
                    case 'nick_name' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_nick_name = $search_keyword;
                        break;
                    case 'email_address' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_email_address = $search_keyword;
                        break;
                    case 'homepage' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_homepage = $search_keyword;
                        break;
                    case 'is_notice' :
                            if($search_keyword=='Y') $args->s_is_notice = 'Y';
                            else $args->s_is_notice = '';
                        break;
                    case 'is_secret' :
                            if($search_keyword=='Y') $args->s_is_secret = 'Y';
                            else $args->s_is_secret = '';
                        break;
                    case 'tags' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_tags = $search_keyword;
                        break;
                    case 'readed_count' :
                            $args->s_readed_count = (int)$search_keyword;
                        break;
                    case 'voted_count' :
                            $args->s_voted_count = (int)$search_keyword;
                        break;
                    case 'comment_count' :
                            $args->s_comment_count = (int)$search_keyword;
                        break;
                    case 'trackback_count' :
                            $args->s_trackback_count = (int)$search_keyword;
                        break;
                    case 'uploaded_count' :
                            $args->s_uploaded_count = (int)$search_keyword;
                        break;
                    case 'regdate' :
                            $args->s_regdate = $search_keyword;
                        break;
                    case 'last_update' :
                            $args->s_last_upate = $search_keyword;
                        break;
                    case 'ipaddress' :
                            $args->s_ipaddress= $search_keyword;
                        break;
                }
            }

            // document.getDocumentList 쿼리 실행
            $output = $oDB->executeQuery($query_id, $args);

            // 결과가 없거나 오류 발생시 그냥 return
            if(!$output->toBool()||!count($output->data)) return $output;

            // 권한 체크
            $oMemberModel = &getModel('member');
            $member_srl = $oMemberModel->getLoggedMemberSrl();

            foreach($output->data as $key => $document) {
                $is_granted = false;

                if($this->isGranted($document->document_srl) || $is_admin) $is_granted = true;
                elseif($member_srl && $member_srl == $document->member_srl) $is_granted = true;

                $output->data[$key]->is_granted = $is_granted;
            }
            return $output;
        }

        /**
         * @brief module_srl에 해당하는 문서의 전체 갯수를 가져옴
         **/
        function getDocumentCount($module_srl, $search_obj = NULL) {
            // DB 객체 생성
            $oDB = &DB::getInstance();

            // 검색 옵션 추가
            $args->module_srl = $module_srl;
            $args->s_title = $search_obj->s_title;
            $args->s_content = $search_obj->s_content;
            $args->s_user_name = $search_obj->s_user_name;
            $args->s_member_srl = $search_obj->s_member_srl;
            $args->s_ipaddress = $search_obj->s_ipaddress;
            $args->s_regdate = $search_obj->s_regdate;
            $args->category_srl = $search_obj->category_srl;

            $output = $oDB->executeQuery('document.getDocumentCount', $args);

            // 전체 갯수를 return
            $total_count = $output->data->count;
            return (int)$total_count;
        }
        /**
         * @brief 해당 document의 page 가져오기, module_srl이 없으면 전체에서..
         **/
        function getDocumentPage($document_srl, $module_srl=0, $list_count) {
            // DB 객체 생성
            $oDB = &DB::getInstance();

            // 변수 설정
            $args->document_srl = $document_srl;
            $args->module_srl = $module_srl;

            // 전체 갯수를 구한후 해당 글의 페이지를 검색
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
    }
?>
