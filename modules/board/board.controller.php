<?php
    /**
     * @class  boardController
     * @author zero (zero@nzeo.com)
     * @brief  board 모듈의 Controller class
     **/

    class boardController extends board {

        /**
         * @brief 초기화
         **/
        function init() {
        }
        
        /**
         * @brief 로그인
         **/
        function procLogin() {
            // 아이디, 비밀번호를 받음
            $user_id = Context::get('user_id');
            $password = Context::get('password');

            // member모듈 controller 객체 생성
            $oMemberController = &getController('member');
            $output = $oMemberController->doLogin($user_id, $password);
            if(!$output->toBool()) return $output;
        }

        /**
         * @brief 로그아웃
         **/
        function procLogout() {
            // member모듈 controller 객체 생성
            $oMemberController = &getController('member');
            return $oMemberController->doLogout();
        }

        /**
         * @brief 문서 입력
         **/
        function procInsertDocument() {

            // 글작성시 필요한 변수를 세팅
            $obj = Context::getRequestVars();
            $obj->module_srl = $this->module_srl;
            if($obj->is_notice!='Y'||!$this->grant->manager) $obj->is_notice = 'N';

            // document module의 model 객체 생성
            $oDocumentModel = &getModel('document');

            // document module의 controller 객체 생성
            $oDocumentController = &getController('document');

            // 이미 존재하는 글인지 체크
            $document = $oDocumentModel->getDocument($obj->document_srl, $this->grant->manager);

            // 이미 존재하는 경우 수정
            if($document->document_srl == $obj->document_srl) {
                $output = $oDocumentController->updateDocument($document, $obj);
                $msg_code = 'success_updated';

            // 그렇지 않으면 신규 등록
            } else {
                $output = $oDocumentController->insertDocument($obj);
                $msg_code = 'success_registed';
                $obj->document_srl = $output->get('document_srl');
            }
            if(!$output->toBool()) return $output;

            // 트랙백 발송
            $trackback_url = Context::get('trackback_url');
            $trackback_charset = Context::get('trackback_charset');
            if($trackback_url) {
                $oTrackbackController = &getController('trackback');
                $oTrackbackController->sendTrackback($obj, $trackback_url, $trackback_charset);
            }

            $this->add('mid', Context::get('mid'));
            $this->add('document_srl', $output->get('document_srl'));
            $this->setMessage($msg_code);
        }

        /**
         * @brief 문서 삭제
         **/
        function procDeleteDocument() {
            // 문서 번호 확인
            $document_srl = Context::get('document_srl');
            if(!$document_srl) return $this->doError('msg_invalid_document');

            // document module model 객체 생성
            $oDocumentController = &getController('document');

            // 삭제 시도
            $output = $oDocumentController->deleteDocument($document_srl, $this->grant->manager);
            if(!$output->toBool()) return $output;

            $this->add('mid', Context::get('mid'));
            $this->add('page', $output->get('page'));
            $this->setMessage('success_deleted');
        }

        /**
         * @brief 추천
         **/
        function procVoteDocument() {
            // document module controller 객체 생성
            $oDocumentController = &getController('document');

            $document_srl = Context::get('document_srl');
            return $oDocumentController->updateVotedCount($document_srl);
        }

        /**
         * @brief 코멘트 추가
         **/
        function procInsertComment() {
            // 댓글 입력에 필요한 데이터 추출
            $obj = Context::gets('document_srl','comment_srl','parent_srl','content','password','nick_name','user_name','member_srl','email_address','homepage');
            $obj->module_srl = $this->module_srl;

            // comment 모듈의 model 객체 생성
            $oCommentModel = &getModel('comment');

            // comment 모듈의 controller 객체 생성
            $oCommentController = &getController('comment');

            // comment_srl이 없을 경우 신규 입력
            if(!$obj->comment_srl) {

                // parent_srl이 있으면 답변으로
                if($obj->parent_srl) {
                    $comment = $oCommentModel->getComment($obj->parent_srl);
                    if(!$comment) return new Object(-1, 'msg_invalid_request');

                    $output = $oCommentController->insertComment($obj);
                    $comment_srl = $output->get('comment_srl');

                // 없으면 신규
                } else {
                    $output = $oCommentController->insertComment($obj);
                }

            // comment_srl이 있으면 수정으로
            } else {

                $comment = $oCommentModel->getComment($obj->comment_srl);
                if(!$comment) return new Object(-1, 'msg_invalid_request');

                $obj->parent_srl = $comment->parent_srl;
                $output = $oCommentController->updateComment($obj);
                $comment_srl = $obj->comment_srl;
            }

            if(!$output->toBool()) return $output;

            $this->setMessage('success_registed');
            $this->add('mid', Context::get('mid'));
            $this->add('document_srl', $obj->document_srl);
            $this->add('comment_srl', $comment_srl);
        }

        /**
         * @brief 코멘트 삭제
         **/
        function procDeleteComment() {
            // 댓글 번호 확인
            $comment_srl = Context::get('comment_srl');
            if(!$comment_srl) return $this->doError('msg_invalid_request');

            // 삭제
            // comment 모듈의 controller 객체 생성
            $oCommentController = &getController('comment');

            $output = $oCommentController->deleteComment($comment_srl);
            if(!$output->toBool()) return $output;

            $this->add('mid', Context::get('mid'));
            $this->add('page', Context::get('page'));
            $this->add('document_srl', $output->get('document_srl'));
            $this->setMessage('success_deleted');
        }

        /**
         * @brief 엮인글 추가
         **/
        function procReceiveTrackback() {
            $obj = Context::gets('document_srl','url','title','excerpt');

            // trackback module의 controller 객체 생성
            $oTrackbackController = &getController('trackback');
            $oTrackbackController->insertTrackback($obj);
        }

        /**
         * @brief 엮인글 삭제
         **/
        function procDeleteTrackback() {
            $trackback_srl = Context::get('trackback_srl');

            // trackback module의 controller 객체 생성
            $oTrackbackController = &getController('trackback');
            $output = $oTrackbackController->deleteTrackback($trackback_srl);
            if(!$output->toBool()) return $output;

            $this->add('mid', Context::get('mid'));
            $this->add('page', Context::get('page'));
            $this->add('document_srl', $output->get('document_srl'));
            $this->setMessage('success_deleted');
        }

        /**
         * @brief 문서와 댓글의 비밀번호를 확인
         **/
        function procVerificationPassword() {
            // 비밀번호와 문서 번호를 받음
            $password = md5(Context::get('password'));
            $document_srl = Context::get('document_srl');
            $comment_srl = Context::get('comment_srl');

            // comment_srl이 있을 경우 댓글이 대상
            if($comment_srl) {
                // 문서번호에 해당하는 글이 있는지 확인
                $oCommentModel = &getModel('comment');
                $data = $oCommentModel->getComment($comment_srl);
                // comment_srl이 없으면 문서가 대상
            } else {
                // 문서번호에 해당하는 글이 있는지 확인
                $oDocumentModel = &getModel('document');
                $data = $oDocumentModel->getDocument($document_srl);
            }

            // 글이 없을 경우 에러
            if(!$data) return new Object(-1, 'msg_invalid_request');

            // 문서의 비밀번호와 입력한 비밀번호의 비교
            if($data->password != $password) return new Object(-1, 'msg_invalid_password');

            // 해당 글에 대한 권한 부여
            if($comment_srl) {
                $oCommentController = &getController('comment');
                $oCommentController->addGrant($comment_srl);
            } else {
                $oDocumentController = &getController('document');
                $oDocumentController->addGrant($document_srl);
            }
        }
 
        /**
         * @brief 첨부파일 삭제
         * 에디터에서 개별 파일 삭제시 사용
         **/
        function procDeleteFile() {
            // 기본적으로 필요한 변수인 document_srl, module_srl을 설정
            $document_srl = Context::get('document_srl');
            $module_srl = $this->module_srl;
            $file_srl = Context::get('file_srl');

            // file class의 controller 객체 생성
            $oFileController = &getController('file');
            $output = $oFileController->deleteFile($file_srl);

            // 첨부파일의 목록을 java script로 출력
            $oFileController->printUploadedFileList($document_srl);
        }

        /**
         * @brief 첨부파일 업로드
         **/
        function procUploadFile() {
            // 업로드 권한이 없거나 정보가 없을시 종료
            if(!Context::isUploaded() || !$this->grant->fileupload) exit();

            // 기본적으로 필요한 변수인 document_srl, module_srl을 설정
            $document_srl = Context::get('document_srl');
            $module_srl = $this->module_srl;

            // file class의 controller 객체 생성
            $oFileController = &getController('file');
            $output = $oFileController->insertFile($module_srl, $document_srl);

            // 첨부파일의 목록을 java script로 출력
            $oFileController->printUploadedFileList($document_srl);
        }

        /**
         * @brief 첨부파일 다운로드
         * 직접 요청을 받음\n
         * file_srl : 파일의 sequence\n
         * sid : db에 저장된 비교 값, 틀리면 다운로드 하지 낳음\n
         **/
        function procDownloadFile() {
            // 다운로드에 필요한 변수 체크
            $file_srl = Context::get('file_srl');
            $sid = Context::get('sid');

            // document module 객체 생성후 해당 파일의 정보를 체크
            $oFileModel = &getModel('file');
            $oFileModel->procDownload($file_srl, $sid);
        }

        /**
         * @brief document_srl의 등록 유무를 체크하여 등록되지 않았다면 첨부파일 삭제
         *
         * 글 작성중 저장하지 않고 빠져나갔을 경우에 대비한 코드인데\n
         * javascript로 빠져나가는 경우 확인이 어려워서 사용되지 않을 코드
         **/
        function procClearFile() {
            $document_srl = Context::get('document_srl');

            // document_srl의 글이 등록되어 있다면 pass
            $oDocumentModel = &getModel('document');
            $data = $oDocumentModel->getDocument($document_srl);
            if($data) exit();

            // 등록되어 있지 않다면 첨부파일 삭제
            $oFileController = &getController('file');
            $oFileController->deleteFiles($this->module_srl, $document_srl);
        }

        /**
         * @brief 권한 추가
         **/
        function procInsertGrant() {
            $module_srl = Context::get('module_srl');

            // 현 모듈의 권한 목록을 가져옴
            $grant_list = $this->xml_info->grant;

            if(count($grant_list)) {
                foreach($grant_list as $key => $val) {
                    $group_srls = Context::get($key);
                    if($group_srls) $arr_grant[$key] = explode(',',$group_srls);
                }
                $grants = serialize($arr_grant);
            }

            $oModuleController = &getController('module');
            $oModuleController->updateModuleGrant($module_srl, $grants);

            $this->add('module_srl',Context::get('module_srl'));
            $this->setMessage('success_registed');
        }

        /**
         * @brief 스킨 정보 업데이트
         **/
        function procUpdateSkinInfo() {
            // module_srl에 해당하는 정보들을 가져오기
            $module_srl = Context::get('module_srl');
            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
            $skin = $module_info->skin;

            // 스킨의 정보르 구해옴 (extra_vars를 체크하기 위해서)
            $skin_info = $oModuleModel->loadSkinInfo($this->module, $skin);

            // 입력받은 변수들을 체크 (mo, act, module_srl, page등 기본적인 변수들 없앰)
            $obj = Context::getRequestVars();
            unset($obj->mo);
            unset($obj->act);
            unset($obj->module_srl);
            unset($obj->page);

            // 원 skin_info에서 extra_vars의 type이 image일 경우 별도 처리를 해줌
            if($skin_info->extra_vars) {
                foreach($skin_info->extra_vars as $vars) {
                    if($vars->type!='image') continue;

                    $image_obj = $obj->{$vars->name};

                    // 삭제 요청에 대한 변수를 구함
                    $del_var = $obj->{"del_".$vars->name};
                    unset($obj->{"del_".$vars->name});
                    if($del_var == 'Y') {
                        @unlink($module_info->{$vars->name});
                        continue;
                    }

                    // 업로드 되지 않았다면 이전 데이터를 그대로 사용
                    if(!$image_obj['tmp_name']) {
                        $obj->{$vars->name} = $module_info->{$vars->name};
                        continue;
                    }

                    // 정상적으로 업로드된 파일이 아니면 무시
                    if(!is_uploaded_file($image_obj['tmp_name'])) {
                        unset($obj->{$vars->name});
                        continue;
                    }

                    // 이미지 파일이 아니어도 무시
                    if(!eregi("\.(jpg|jpeg|gif|png)$", $image_obj['name'])) {
                        unset($obj->{$vars->name});
                        continue;
                    }

                    // 경로를 정해서 업로드
                    $path = sprintf("./files/attach/images/%s/", $module_srl);

                    // 디렉토리 생성
                    if(!FileHandler::makeDir($path)) return false;

                    $filename = $path.$image_obj['name'];

                    // 파일 이동
                    if(!move_uploaded_file($image_obj['tmp_name'], $filename)) {
                        unset($obj->{$vars->name});
                        continue;
                    }

                    // 변수를 바꿈
                    unset($obj->{$vars->name});
                    $obj->{$vars->name} = $filename;
                }
            }

            // serialize하여 저장
            $extra_vars = serialize($obj);

            $oModuleController = &getController('module');
            $oModuleController->updateModuleExtraVars($module_srl, $extra_vars);

            $url = sprintf("./?module=admin&mo=board&module_srl=%s&act=dispAdminSkinInfo&page=%s", $module_srl, Context::get('page'));
            print "<script type=\"text/javascript\">location.href=\"".$url."\";</script>";
            exit();
        }

        /**
         * @brief 게시판 추가
         **/
        function procInsertBoard() {
            // 일단 입력된 값들을 모두 받아서 db 입력항목과 그외 것으로 분리
            $args = Context::gets('module_srl','board_name','skin','use_category','browser_title','description','is_default','header_text','footer_text','admin_id');
            $args->module = 'board';
            $args->mid = $args->board_name;
            unset($args->board_name);
            if($args->is_default!='Y') $args->is_default = 'N';
            if($args->use_category!='Y') $args->use_category = 'N';

            // 기본 값외의 것들을 정리
            $extra_var = delObjectVars(Context::getRequestVars(), $args);
            unset($extra_var->mo);
            unset($extra_var->act);
            unset($extra_var->page);

            // module_srl이 넘어오면 원 모듈이 있는지 확인
            if($args->module_srl) {
                $oModuleModel = &getModel('module');
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);

                // 만약 원래 모듈이 없으면 새로 입력하기 위한 처리
                if($module_info->module_srl != $args->module_srl) unset($args->module_srl);
            }

            // $extra_var를 serialize
            $args->extra_var = serialize($extra_var);

            // module 모듈의 controller 객체 생성
            $oModuleController = &getController('module');

            // is_default=='Y' 이면
            if($args->is_default=='Y') $oModuleController->clearDefaultModule();

            // module_srl의 값에 따라 insert/update
            if(!$args->module_srl) {
                $output = $oModuleController->insertModule($args);
                $msg_code = 'success_registed';
            } else {
                $output = $oModuleController->updateModule($args);
                $msg_code = 'success_updated';
            }

            if(!$output->toBool()) return $output;

            $this->add('page',Context::get('page'));
            $this->add('module_srl',$output->get('module_srl'));
            $this->setMessage($msg_code);
        }

        /**
         * @brief 게시판 삭제
         **/
        function procDeleteBoard() {
            $module_srl = Context::get('module_srl');

            // 원본을 구해온다
            $oModuleController = &getController('module');
            $output = $oModuleController->deleteModule($module_srl);
            if(!$output->toBool()) return $output;

            $this->add('module','board');
            $this->add('page',Context::get('page'));
            $this->setMessage('success_deleted');
        }

        /**
         * @brief 카테고리 추가
         **/
        function procInsertCategory() {
            // 일단 입력된 값들을 모두 받아서 db 입력항목과 그외 것으로 분리
            $module_srl = Context::get('module_srl');
            $category_title = Context::get('category_title');

            // module_srl이 있으면 원본을 구해온다
            $oDocumentController = &getController('document');
            $output = $oDocumentController->insertCategory($module_srl, $category_title);
            if(!$output->toBool()) return $output;

            $this->add('page',Context::get('page'));
            $this->add('module_srl',$module_srl);
            $this->setMessage('success_registed');
        }

        /**
         * @brief 카테고리의 내용 수정
         **/
        function procUpdateCategory() {
            $module_srl = Context::get('module_srl');
            $category_srl = Context::get('category_srl');
            $mode = Context::get('mode');

            $oDocumentModel = &getModel('document');
            $oDocumentController = &getController('document');

            switch($mode) {
                case 'up' :
                        $output = $oDocumentController->moveCategoryUp($category_srl);
                        $msg_code = 'success_moved';
                    break;
                case 'down' :
                        $output = $oDocumentController->moveCategoryDown($category_srl);
                        $msg_code = 'success_moved';
                    break;
                case 'delete' :
                        $output = $oDocumentController->deleteCategory($category_srl);
                        $msg_code = 'success_deleted';
                    break;
                case 'update' :
                        $selected_category = $oDocumentModel->getCategory($category_srl);
                        $args->category_srl = $selected_category->category_srl;
                        $args->title = Context::get('category_title');
                        $args->list_order = $selected_category->list_order;
                        $output = $oDocumentController->updateCategory($args);
                        $msg_code = 'success_updated';
                    break;
            }
            if(!$output->toBool()) return $output;

            $this->add('module_srl', $module_srl);
            $this->setMessage($msg_code);
        }
    }
?>
