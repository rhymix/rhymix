<?php
    /**
     * @class  documentController
     * @author zero (zero@nzeo.com)
     * @brief  document 모듈의 controller 클래스
     **/

    class documentController extends document {

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
            for($i=0;$i<$document_count;$i++) {
                $document_srl = trim($document_srl_list[$i]);
                if(!$document_srl) continue;

                $this->deleteDocument($document_srl, true);
            }

            $this->setMessage( sprintf(Context::getLang('msg_checked_document_is_deleted'), $document_count) );
        }

        /**
         * @brief 문서의 권한 부여 
         * 세션값으로 현 접속상태에서만 사용 가능
         **/
        function addGrant($document_srl) {
            $_SESSION['own_document'][$document_srl] = true;
        }

        /**
         * @brief 문서 입력
         **/
        function insertDocument($obj) {
            // DB 객체 생성
            $oDB = &DB::getInstance();

            // 기본 변수들 정리
            if($obj->is_secret!='Y') $obj->is_secret = 'N';
            if($obj->allow_comment!='Y') $obj->allow_comment = 'N';
            if($obj->lock_comment!='Y') $obj->lock_comment = 'N';
            if($obj->allow_trackback!='Y') $obj->allow_trackback = 'N';

            // 자동저장용 필드 제거
            unset($obj->_saved_doc_srl);
            unset($obj->_saved_doc_title);
            unset($obj->_saved_doc_content);
            unset($obj->_saved_doc_message);

            // file의 Model객체 생성
            $oFileModel = &getModel('file');

            // 첨부 파일의 갯수를 구함
            $obj->uploaded_count = $oFileModel->getFilesCount($obj->document_srl);

            // 카테고리가 있나 검사하여 없는 카테고리면 0으로 세팅
            if($obj->category_srl) {
                $oDocumentModel = &getModel('document');
                $category_list = $oDocumentModel->getCategoryList($obj->module_srl);
                if(!$category_list[$obj->category_srl]) $obj->category_srl = 0;
            }

            // 태그 처리
            $oTagController = &getController('tag');
            $obj->tags = $oTagController->insertTag($obj->module_srl, $obj->document_srl, $obj->tags);

            // 글 입력
            $obj->readed_count = 0;
            $obj->update_order = $obj->list_order = $obj->document_srl * -1;
            if($obj->password) $obj->password = md5($obj->password);

            // 공지사항일 경우 list_order에 무지막지한 값;;을 입력
            if($obj->is_notice=='Y') $obj->list_order = $this->notice_list_order;

            // 로그인 된 회원일 경우 회원의 정보를 입력
            if(Context::get('is_logged')) {
                $logged_info = Context::get('logged_info');
                $obj->member_srl = $logged_info->member_srl;
                $obj->user_name = $logged_info->user_name;
                $obj->nick_name = $logged_info->nick_name;
                $obj->email_address = $logged_info->email_address;
                $obj->homepage = $logged_info->homepage;
            }

            // DB에 입력
            $output = $oDB->executeQuery('document.insertDocument', $obj);

            if(!$output->toBool()) return $output;

            // 성공하였을 경우 category_srl이 있으면 카테고리 update
            if($obj->category_srl) $this->updateCategoryCount($obj->category_srl);

            // 자동 저장 문서 삭제
            $oEditorController = &getController('editor');
            $oEditorController->deleteSavedDoc();

            // return
            $this->addGrant($obj->document_srl);
            $output->add('document_srl',$obj->document_srl);
            $output->add('category_srl',$obj->category_srl);
            return $output;
        }

        /**
         * @brief 문서 수정
         **/
        function updateDocument($source_obj, $obj) {
            $oDB = &DB::getInstance();

            // 기본 변수들 정리
            if($obj->is_secret!='Y') $obj->is_secret = 'N';
            if($obj->allow_comment!='Y') $obj->allow_comment = 'N';
            if($obj->lock_comment!='Y') $obj->lock_comment = 'N';
            if($obj->allow_trackback!='Y') $obj->allow_trackback = 'N';

            // 자동저장용 필드 제거
            unset($obj->_saved_doc_srl);
            unset($obj->_saved_doc_title);
            unset($obj->_saved_doc_content);
            unset($obj->_saved_doc_message);

            // file의 Model객체 생성
            $oFileModel = &getModel('file');

            // 첨부 파일의 갯수를 구함
            $obj->uploaded_count = $oFileModel->getFilesCount($obj->document_srl);

            // 카테고리가 변경되었으면 검사후 없는 카테고리면 0으로 세팅
            if($source_obj->category_srl!=$obj->category_srl) {
                $oDocumentModel = &getModel('document');
                $category_list = $oDocumentModel->getCategoryList($obj->module_srl);
                if(!$category_list[$obj->category_srl]) $obj->category_srl = 0;
            }

            // 태그 처리
            if($source_obj->tags != $obj->tags) {
                $oTagController = &getController('tag');
                $obj->tags = $oTagController->insertTag($obj->module_srl, $obj->document_srl, $obj->tags);
            }

            // 수정
            $obj->update_order = $oDB->getNextSequence() * -1;

            // 공지사항일 경우 list_order에 무지막지한 값을, 그렇지 않으면 document_srl*-1값을
            if($obj->is_notice=='Y') $obj->list_order = $this->notice_list_order;
            else $obj->list_order = $obj->document_srl*-1;

            if($obj->password) $obj->password = md5($obj->password);

            // 원본 작성인과 수정하려는 수정인이 동일할 시에 로그인 회원의 정보를 입력
            if(Context::get('is_logged')) {
                $logged_info = Context::get('logged_info');
                if($source_obj->member_srl==$logged_info->member_srl) {
                    $obj->member_srl = $logged_info->member_srl;
                    $obj->user_name = $logged_info->user_name;
                    $obj->nick_name = $logged_info->nick_name;
                    $obj->email_address = $logged_info->email_address;
                    $obj->homepage = $logged_info->homepage;
                }
            }

            // 로그인한 유저가 작성한 글인데 nick_name이 없을 경우
            if($source_obj->member_srl && !$obj->nick_name) {
                $obj->member_srl = $source_obj->member_srl;
                $obj->user_name = $source_obj->user_name;
                $obj->nick_name = $source_obj->nick_name;
                $obj->email_address = $source_obj->email_address;
                $obj->homepage = $source_obj->homepage;
            }

            // DB에 입력
            $output = $oDB->executeQuery('document.updateDocument', $obj);
            if(!$output->toBool()) return $output;

            // 성공하였을 경우 category_srl이 있으면 카테고리 update
            if($source_obj->category_srl!=$obj->category_srl) {
                if($source_obj->category_srl) $this->updateCategoryCount($source_obj->category_srl);
                if($obj->category_srl) $this->updateCategoryCount($obj->category_srl);
            }

            // 자동 저장 문서 삭제
            $oEditorController = &getController('editor');
            $oEditorController->deleteSavedDoc();

            $output->add('document_srl',$obj->document_srl);
            return $output;
        }

        /**
         * @brief 문서 삭제
         **/
        function deleteDocument($document_srl, $is_admin = false) {

            // document의 model 객체 생성
            $oDocumentModel = &getModel('document');

            // 기존 문서가 있는지 확인
            $document = $oDocumentModel->getDocument($document_srl, $is_admin);
            if($document->document_srl != $document_srl) return new Object(-1, 'msg_invalid_document');

            // 권한이 있는지 확인
            if(!$document->is_granted&&!$is_admin) return new Object(-1, 'msg_not_permitted');

            $oDB = &DB::getInstance();

            // 글 삭제
            $args->document_srl = $document_srl;
            $output = $oDB->executeQuery('document.deleteDocument', $args);
            if(!$output->toBool()) return $output;

            // 댓글 삭제
            $oCommentController = &getController('comment');
            $output = $oCommentController->deleteComments($document_srl, $is_admin);

            // 엮인글 삭제
            $oTrackbackController = &getController('trackback');
            $output = $oTrackbackController->deleteTrackbacks($document_srl, $is_admin);

            // 태그 삭제
            $oTagController = &getController('tag');
            $oTagController->deleteTag($document_srl, $is_admin);

            // 첨부 파일 삭제
            if($document->uploaded_count) {
                $oFileController = &getController('file');
                $oFileController->deleteFiles($document->module_srl, $document_srl);
            }

            // 카테고리가 있으면 카테고리 정보 변경
            if($document->category_srl) $this->updateCategoryCount($document->category_srl);

            return $output;
        }

        /**
         * @brief 특정 모듈의 전체 문서 삭제
         **/
        function deleteModuleDocument($module_srl) {
            $oDB = &DB::getInstance();

            $args->module_srl = $module_srl;
            $output = $oDB->executeQuery('document.deleteModuleDocument', $args);
            return $output;
        }

        /**
         * @brief 해당 document의 조회수 증가
         **/
        function updateReadedCount($document) {
            $document_srl = $document->document_srl;

            // session에 정보로 조회수를 증가하였다고 생각하면 패스
            if($_SESSION['readed_document'][$document_srl]) return false;

            // 글의 작성 ip와 현재 접속자의 ip가 동일하면 패스
            if($document->ipaddress == $_SERVER['REMOTE_ADDR']) {
                $_SESSION['readed_document'][$document_srl] = true;
                return false;
            }

            // document의 작성자가 회원일때 조사
            if($document->member_srl) {
                // member model 객체 생성
                $oMemberModel = &getModel('member');
                $member_srl = $oMemberModel->getLoggedMemberSrl();

                // 글쓴이와 현재 로그인 사용자의 정보가 일치하면 읽었다고 생각하고 세션 등록후 패스
                if($member_srl && $member_srl == $document->member_srl) {
                    $_SESSION['readed_document'][$document_srl] = true;
                    return false;
                }
            }

            // DB 객체 생성
            $oDB = &DB::getInstance();

            // 로그인 사용자이면 member_srl, 비회원이면 ipaddress로 판단
            if($member_srl) {
                $args->member_srl = $member_srl;
            } else {
                $args->ipaddress = $_SERVER['REMOTE_ADDR'];
            }
            $args->document_srl = $document_srl;
            $output = $oDB->executeQuery('document.getDocumentReadedLogInfo', $args);

            // 로그 정보에 조회 로그가 있으면 세션 등록후 패스
            if($output->data->count) return $_SESSION['readed_document'][$document_srl] = true;

            // 조회수 업데이트
            $output = $oDB->executeQuery('document.updateReadedCount', $args);

            // 로그 남기기
            $output = $oDB->executeQuery('document.insertDocumentReadedLog', $args);

            // 세션 정보에 남김
            return $_SESSION['readed_document'][$document_srl] = true;
        }

        /**
         * @brief 해당 document의 추천수 증가
         **/
        function updateVotedCount($document_srl) {
            // 세션 정보에 추천 정보가 있으면 중단
            if($_SESSION['voted_document'][$document_srl]) return new Object(-1, 'failed_voted');

            // 문서 원본을 가져옴
            $oDocumentModel = &getModel('document');
            $document = $oDocumentModel->getDocument($document_srl, false, false);

            // 글의 작성 ip와 현재 접속자의 ip가 동일하면 패스
            if($document->ipaddress == $_SERVER['REMOTE_ADDR']) {
                $_SESSION['voted_document'][$document_srl] = true;
                return new Object(-1, 'failed_voted');
            }

            // document의 작성자가 회원일때 조사
            if($document->member_srl) {
                // member model 객체 생성
                $oMemberModel = &getModel('member');
                $member_srl = $oMemberModel->getLoggedMemberSrl();

                // 글쓴이와 현재 로그인 사용자의 정보가 일치하면 읽었다고 생각하고 세션 등록후 패스
                if($member_srl && $member_srl == $document->member_srl) {
                    $_SESSION['voted_document'][$document_srl] = true;
                    return new Object(-1, 'failed_voted');
                }
            }

            // DB 객체 생성
            $oDB = &DB::getInstance();

            // 로그인 사용자이면 member_srl, 비회원이면 ipaddress로 판단
            if($member_srl) {
                $args->member_srl = $member_srl;
            } else {
                $args->ipaddress = $_SERVER['REMOTE_ADDR'];
            }
            $args->document_srl = $document_srl;
            $output = $oDB->executeQuery('document.getDocumentVotedLogInfo', $args);

            // 로그 정보에 추천 로그가 있으면 세션 등록후 패스
            if($output->data->count) {
                $_SESSION['voted_document'][$document_srl] = true;
                return new Object(-1, 'failed_voted');
            }

            // 추천수 업데이트
            $output = $oDB->executeQuery('document.updateVotedCount', $args);

            // 로그 남기기
            $output = $oDB->executeQuery('document.insertDocumentVotedLog', $args);

            // 세션 정보에 남김
            $_SESSION['voted_document'][$document_srl] = true;

            // 결과 리턴
            return new Object(0, 'success_voted');
        }

        /**
         * @brief 해당 document의 댓글 수 증가
         **/
        function updateCommentCount($document_srl, $comment_count) {
            $oDB = &DB::getInstance();

            $args->document_srl = $document_srl;
            $args->comment_count = $comment_count;

            return $oDB->executeQuery('document.updateCommentCount', $args);
        }

        /**
         * @brief 해당 document의 엮인글 수증가
         **/
        function updateTrackbackCount($document_srl, $trackback_count) {
            $oDB = &DB::getInstance();

            $args->document_srl = $document_srl;
            $args->trackback_count = $trackback_count;

            return $oDB->executeQuery('document.updateTrackbackCount', $args);
        }

        /**
         * @brief 카테고리 추가
         **/
        function insertCategory($module_srl, $title) {
            $oDB = &DB::getInstance();

            $args->list_order = $args->category_srl = $oDB->getNextSequence();
            $args->module_srl = $module_srl;
            $args->title = $title;
            $args->document_count = 0;

            return $oDB->executeQuery('document.insertCategory', $args);
        }

        /**
         * @brief 카테고리 정보 수정
         **/
        function updateCategory($args) {
            $oDB = &DB::getInstance();
            return $oDB->executeQuery('document.updateCategory', $args);
        }

        /** 
         * @brief 카테고리에 문서의 숫자를 변경
         **/
        function updateCategoryCount($category_srl, $document_count = 0) {
            // document model 객체 생성
            $oDocumentModel = &getModel('document');
            if(!$document_count) $document_count = $oDocumentModel->getCategoryDocumentCount($category_srl);

            $oDB = &DB::getInstance();

            $args->category_srl = $category_srl;
            $args->document_count = $document_count;
            return $oDB->executeQuery('document.updateCategoryCount', $args);
        }

        /**
         * @brief 카테고리 삭제
         **/
        function deleteCategory($category_srl) {
            $oDB = &DB::getInstance();

            $args->category_srl = $category_srl;

            // 카테고리 정보를 삭제
            $output = $oDB->executeQuery('document.deleteCategory', $args);
            if(!$output->toBool()) return $output;

            // 현 카테고리 값을 가지는 문서들의 category_srl을 0 으로 세팅
            unset($args);

            $args->target_category_srl = 0;
            $args->source_category_srl = $category_srl;
            $output = $oDB->executeQuery('document.updateDocumentCategory', $args);
            return $output;
        }

        /**
         * @brief 특정 모듈의 카테고리를 모두 삭제
         **/
        function deleteModuleCategory($module_srl) {
            $oDB = &DB::getInstance();

            $args->module_srl = $module_srl;
            $output = $oDB->executeQuery('document.deleteModuleCategory', $args);
            return $output;
        }

        /**
         * @brief 카테고리를 상단으로 이동
         **/
        function moveCategoryUp($category_srl) {
            $oDB = &DB::getInstance();
            $oDocumentModel = &getModel('document');

            // 선택된 카테고리의 정보를 구한다
            $args->category_srl = $category_srl;
            $output = $oDB->executeQuery('document.getCategory', $args);

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
            $oDB = &DB::getInstance();
            $oDocumentModel = &getModel('document');

            // 선택된 카테고리의 정보를 구한다
            $args->category_srl = $category_srl;
            $output = $oDB->executeQuery('document.getCategory', $args);

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
    }
?>
