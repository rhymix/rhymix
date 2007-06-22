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
        function getDocument($document_srl, $is_admin = false) {
            $oDocument = new documentItem($document_srl);
            if($is_admin) $oDocument->setGrant();

            return $oDocument;
       }

        /**
         * @brief 여러개의 문서들을 가져옴 (페이징 아님)
         **/
        function getDocuments($document_srl_list, $is_admin = false) {
            if(is_array($document_srl_list)) $document_srls = implode(',',$document_srl_list);

            // DB에서 가져옴
            $args->document_srls = $document_srls;
            $output = executeQuery('document.getDocuments', $args);
            $document_list = $output->data;
            if(!$document_list) return;
            if(!is_array($document_list)) $document_list = array($document_list);

            $document_count = count($document_list);
            for($i=0;$i<$document_count;$i++) {
                $document_srl = $attribute->document_srl;
                $attribute = $document_list[$i];

                $oDocument = null;
                $oDocument = new documentItem();
                $oDocument->setAttribute($attribute);
                if($is_admin) $oDocument->setGrant();

                $document_list[$document_srl] = $oDocument;
            }
            return $document_list;
        }

        /**
         * @brief module_srl값을 가지는 문서의 목록을 가져옴
         **/
        function getDocumentList($obj) {
            // 정렬 대상과 순서 체크 
            if(!in_array($obj->sort_index, array('list_order','regdate','update_order','readed_count','voted_count'))) $obj->sort_index = 'list_order'; 
            if(!in_array($obj->order_type, array('desc','asc'))) $obj->order_type = 'asc'; 

            // module_srl 대신 mid가 넘어왔을 경우는 직접 module_srl을 구해줌
            if($obj->mid) {
                $oModuleModel = &getModel('module');
                $obj->module_srl = $oModuleModel->getModuleSrlByMid($obj->mid);
                unset($obj->mid);
            }

            // 넘어온 module_srl은 array일 수도 있기에 array인지를 체크 
            if(is_array($obj->module_srl)) $args->module_srl = implode(',', $obj->module_srl);
            else $args->module_srl = $obj->module_srl;

            // 변수 체크
            $args->category_srl = $obj->category_srl?$obj->category_srl:'';
            $args->sort_index = $obj->sort_index;
            $args->order_type = $obj->order_type;
            $args->page = $obj->page?$obj->page:1;
            $args->list_count = $obj->list_count?$obj->list_count:20;
            $args->page_count = $obj->page_count?$obj->page_count:10;
            $query_id = 'document.getDocumentList';

            // 검색 옵션 정리
            $search_target = $obj->search_target;
            $search_keyword = $obj->search_keyword;
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
                    case 'title_content' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_title = $search_keyword;
                            $args->s_content = $search_keyword;
                        break;
                    case 'user_id' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_user_id = $search_keyword;
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
                    case 'tag' :
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
            $output = executeQuery($query_id, $args);

            // 결과가 없거나 오류 발생시 그냥 return
            if(!$output->toBool()||!count($output->data)) return $output;

            foreach($output->data as $key => $attribute) {
                $document_srl = $attribute->document_srl;

                $oDocument = null;
                $oDocument = new documentItem();
                $oDocument->setAttribute($attribute);
                if($is_admin) $oDocument->setGrant();

                $output->data[$key] = $oDocument;
            
            }
            return $output;
        }

        /**
         * @brief module_srl에 해당하는 문서의 전체 갯수를 가져옴
         **/
        function getDocumentCount($module_srl, $search_obj = NULL) {
            // 검색 옵션 추가
            $args->module_srl = $module_srl;
            $args->s_title = $search_obj->s_title;
            $args->s_content = $search_obj->s_content;
            $args->s_user_name = $search_obj->s_user_name;
            $args->s_member_srl = $search_obj->s_member_srl;
            $args->s_ipaddress = $search_obj->s_ipaddress;
            $args->s_regdate = $search_obj->s_regdate;
            $args->category_srl = $search_obj->category_srl;

            $output = executeQuery('document.getDocumentCount', $args);

            // 전체 갯수를 return
            $total_count = $output->data->count;
            return (int)$total_count;
        }
        /**
         * @brief 해당 document의 page 가져오기, module_srl이 없으면 전체에서..
         **/
        function getDocumentPage($document_srl, $module_srl=0, $list_count) {
            // 변수 설정
            $args->document_srl = $document_srl;
            $args->module_srl = $module_srl;

            // 전체 갯수를 구한후 해당 글의 페이지를 검색
            $output = executeQuery('document.getDocumentPage', $args);
            $count = $output->data->count;
            $page = (int)(($count-1)/$list_count)+1;
            return $page;
        }

        /**
         * @brief 카테고리의 정보를 가져옴
         **/
        function getCategory($category_srl) {
            $args->category_srl = $category_srl;
            $output = executeQuery('document.getCategory', $args);
            return $output->data;
        }

        /**
         * @brief 특정 모듈의 카테고리 목록을 가져옴
         **/
        function getCategoryList($module_srl) {
            $args->module_srl = $module_srl;
            $args->sort_index = 'list_order';
            $output = executeQuery('document.getCategoryList', $args);

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
            $args->category_srl = $category_srl;
            $output = executeQuery('document.getCategoryDocumentCount', $args);
            return (int)$output->data->count;
        }

        /**
         * @brief 월별 글 보관현황을 가져옴
         **/
        function getMonthlyArchivedList($obj) {
            if($obj->mid) {
                $oModuleModel = &getModel('module');
                $obj->module_srl = $oModuleModel->getModuleSrlByMid($obj->mid);
                unset($obj->mid);
            }

            // 넘어온 module_srl은 array일 수도 있기에 array인지를 체크 
            if(is_array($obj->module_srl)) $args->module_srl = implode(',', $obj->module_srl);
            else $args->module_srl = $obj->module_srl;

            $output = executeQuery('document.getMonthlyArchivedList', $args);
            if(!$output->toBool()||!$output->data) return $output;

            if(!is_array($output->data)) $output->data = array($output->data);

            return $output;
        }

        /**
         * @brief 특정달의 일별 글 현황을 가져옴
         **/
        function getDailyArchivedList($obj) {
            if($obj->mid) {
                $oModuleModel = &getModel('module');
                $obj->module_srl = $oModuleModel->getModuleSrlByMid($obj->mid);
                unset($obj->mid);
            }

            // 넘어온 module_srl은 array일 수도 있기에 array인지를 체크 
            if(is_array($obj->module_srl)) $args->module_srl = implode(',', $obj->module_srl);
            else $args->module_srl = $obj->module_srl;
            $args->regdate = $obj->regdate;

            $output = executeQuery('document.getDailyArchivedList', $args);
            if(!$output->toBool()) return $output;

            if(!is_array($output->data)) $output->data = array($output->data);

            return $output;
        }
    }
?>
