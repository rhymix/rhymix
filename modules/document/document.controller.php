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
         * @breif 게시글의 추천을 처리하는 action (Up)
         **/
        function procDocumentVoteUp() {
            if(!Context::get('is_logged')) return new Object(-1, 'msg_invalid_request');

            $document_srl = Context::get('target_srl');
            if(!$document_srl) return new Object(-1, 'msg_invalid_request');

            $point = 1;
            return $this->updateVotedCount($document_srl, $point);
        }

        function insertAlias($module_srl, $document_srl, $alias_title) {
            $args->alias_srl = getNextSequence();
            $args->module_srl = $module_srl;
            $args->document_srl = $document_srl;
            $args->alias_title = $alias_title;
            $query = "document.insertAlias";
            $output = executeQuery($query, $args);
            return $output;
        }

        /**
         * @breif 게시글의 추천을 처리하는 action (Down)
         **/
        function procDocumentVoteDown() {
            if(!Context::get('is_logged')) return new Object(-1, 'msg_invalid_request');

            $document_srl = Context::get('target_srl');
            if(!$document_srl) return new Object(-1, 'msg_invalid_request');

            $point = -1;
            return $this->updateVotedCount($document_srl, $point);
        }

        /**
         * @brief 게시글이 신고될 경우 호출되는 action
         **/
        function procDocumentDeclare() {
            if(!Context::get('is_logged')) return new Object(-1, 'msg_invalid_request');

            $document_srl = Context::get('target_srl');
            if(!$document_srl) return new Object(-1, 'msg_invalid_request');

            return $this->declaredDocument($document_srl);
        }

        function deleteDocumentAliasByModule($module_srl)
        {
            $args->module_srl = $module_srl;
            executeQuery("document.deleteAlias", $args);
        }

        function deleteDocumentAliasByDocument($document_srl)
        {
            $args->document_srl = $document_srl;
            executeQuery("document.deleteAlias", $args);
        }

        function deleteDocumentHistory($history_srl, $document_srl, $module_srl)
        {
            $args->history_srl = $history_srl;
            $args->module_srl = $module_srl;
            $args->document_srl = $document_srl;
            if(!$args->history_srl && !$args->module_srl && !$args->document_srl) return;
            executeQuery("document.deleteHistory", $args);
        }

        /**
         * @brief 모듈이 삭제될때 등록된 모든 글을 삭제하는 trigger
         **/
        function triggerDeleteModuleDocuments(&$obj) {
            $module_srl = $obj->module_srl;
            if(!$module_srl) return new Object();

            // document 삭제
            $oDocumentAdminController = &getAdminController('document');
            $output = $oDocumentAdminController->deleteModuleDocument($module_srl);
            if(!$output->toBool()) return $output;

            // category 삭제
            $oDocumentController = &getController('document');
            $output = $oDocumentController->deleteModuleCategory($module_srl);
            if(!$output->toBool()) return $output;

            // 확장변수 삭제
            $this->deleteDocumentExtraVars($module_srl);

            // remove aliases
            $this->deleteDocumentAliasByModule($module_srl);

            // remove histories
            $this->deleteDocumentHistory(null, null, $module_srl);

            return new Object();
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
        function insertDocument($obj, $manual_inserted = false) {
            // begin transaction
            $oDB = &DB::getInstance();
            $oDB->begin();

            // 기본 변수들 정리
            if($obj->is_secret!='Y') $obj->is_secret = 'N';
            if($obj->allow_comment!='Y') $obj->allow_comment = 'N';
            if($obj->lock_comment!='Y') $obj->lock_comment = 'N';
            if($obj->allow_trackback!='Y') $obj->allow_trackback = 'N';
            if($obj->homepage &&  !preg_match('/^http:\/\//i',$obj->homepage)) $obj->homepage = 'http://'.$obj->homepage;
            if($obj->notify_message != 'Y') $obj->notify_message = 'N';

            // $extra_vars를 serialize
            $obj->extra_vars = serialize($obj->extra_vars);

            // 자동저장용 필드 제거
            unset($obj->_saved_doc_srl);
            unset($obj->_saved_doc_title);
            unset($obj->_saved_doc_content);
            unset($obj->_saved_doc_message);

            // 주어진 문서 번호가 없으면 문서 번호 등록
            if(!$obj->document_srl) $obj->document_srl = getNextSequence();

            // trigger 호출 (before)
            $output = ModuleHandler::triggerCall('document.insertDocument', 'before', $obj);
            if(!$output->toBool()) return $output;

            $oDocumentModel = &getModel('document');

            // 카테고리가 있나 검사하여 없는 카테고리면 0으로 세팅
            if($obj->category_srl) {
                $category_list = $oDocumentModel->getCategoryList($obj->module_srl);
                if(!$category_list[$obj->category_srl]) $obj->category_srl = 0;
            }

            // 조회수, 등록순서 설정
            if(!$obj->readed_count) $obj->readed_count = 0;
            $obj->update_order = $obj->list_order = getNextSequence() * -1;

            // 수동입력을 대비해서 비밀번호의 hash상태를 점검, 수동입력이 아니면 무조건 md5 hash
            if($obj->password && !$obj->password_is_hashed) $obj->password = md5($obj->password);

            // 수동 등록이 아니고 로그인 된 회원일 경우 회원의 정보를 입력
            if(Context::get('is_logged')&&!$manual_inserted) {
                $logged_info = Context::get('logged_info');
                $obj->member_srl = $logged_info->member_srl;
                $obj->user_id = $logged_info->user_id;
                $obj->user_name = $logged_info->user_name;
                $obj->nick_name = $logged_info->nick_name;
                $obj->email_address = $logged_info->email_address;
                $obj->homepage = $logged_info->homepage;
            }

            // 제목이 없으면 내용에서 추출
            settype($obj->title, "string");
            if($obj->title == '') $obj->title = cut_str(strip_tags($obj->content),20,'...');
            //그래도 없으면 Untitled
            if($obj->title == '') $obj->title = 'Untitled';

            // 내용에서 XE만의 태그를 삭제
            $obj->content = preg_replace('!<\!--(Before|After)(Document|Comment)\(([0-9]+),([0-9]+)\)-->!is', '', $obj->content);

            // 세션에서 최고 관리자가 아니면 iframe, script 제거
            if($logged_info->is_admin != 'Y') $obj->content = removeHackTag($obj->content);

            // 로그인정보가 없고 사용자 이름이 없으면 오류 표시
            if(!$logged_info->member_srl && !$obj->nick_name) return new Object(-1,'msg_invalid_request');

            // DB에 입력
            $output = executeQuery('document.insertDocument', $obj);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            // 등록 성공시 확장 변수 등록
            $extra_keys = $oDocumentModel->getExtraKeys($obj->module_srl);
            if(count($extra_keys)) {
                foreach($extra_keys as $idx => $extra_item) {
                    $value = '';
                    if(isset($obj->{'extra_vars'.$idx})) $value = trim($obj->{'extra_vars'.$idx});
                    elseif(isset($obj->{$extra_item->name})) $value = trim($obj->{$extra_item->name});
                    if(!isset($value)) continue;
                    $this->insertDocumentExtraVar($obj->module_srl, $obj->document_srl, $idx, $value);
                }
            }

            // 성공하였을 경우 category_srl이 있으면 카테고리 update
            if($obj->category_srl) $this->updateCategoryCount($obj->module_srl, $obj->category_srl);

            // 공지사항 글이면 공지사항 캐시 업데이트
            if($obj->is_notice == 'Y') $this->updateDocumentNoticeCache($obj->module_srl);

            // trigger 호출 (after)
            if($output->toBool()) {
	            $trigger_output = ModuleHandler::triggerCall('document.insertDocument', 'after', $obj);
                if(!$trigger_output->toBool()) {
                    $oDB->rollback();
                    return $trigger_output;
                }
            }

            // commit
            $oDB->commit();

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
            // trigger 호출 (before)
            $output = ModuleHandler::triggerCall('document.updateDocument', 'before', $obj);
            if(!$output->toBool()) return $output;

            // begin transaction
            $oDB = &DB::getInstance();
            $oDB->begin();

            $oModuleModel = &getModel('module');
            $module_srl = $source_obj->get('module_srl');
            $document_config = $oModuleModel->getModulePartConfig('document', $module_srl);
            if(!isset($document_config->use_history)) $document_config->use_history = 'N';
            $bUseHistory = $document_config->use_history == 'Y' || $document_config->use_history == 'Trace';

            if($bUseHistory)
            {
                $args->history_srl = getNextSequence();
                $args->document_srl = $obj->document_srl;
                $args->module_srl = $module_srl;
                if($document_config->use_history == 'Y')
                    $args->content = $source_obj->get('content');
                $args->nick_name = $source_obj->get('nick_name');
                $args->member_srl = $source_obj->get('member_srl');
                $args->regdate = $source_obj->get('last_update');
                $args->ipaddress = $source_obj->get('ipaddress');
                $output = executeQuery("document.insertHistory", $args);
            }

            // 기본 변수들 정리
            if($obj->is_secret!='Y') $obj->is_secret = 'N';
            if($obj->allow_comment!='Y') $obj->allow_comment = 'N';
            if($obj->lock_comment!='Y') $obj->lock_comment = 'N';
            if($obj->allow_trackback!='Y') $obj->allow_trackback = 'N';
            if($obj->homepage &&  !preg_match('/^http:\/\//i',$obj->homepage)) $obj->homepage = 'http://'.$obj->homepage;
            if($obj->notify_message != 'Y') $obj->notify_message = 'N';

            // $extra_vars를 serialize
            $obj->extra_vars = serialize($obj->extra_vars);

            // 자동저장용 필드 제거
            unset($obj->_saved_doc_srl);
            unset($obj->_saved_doc_title);
            unset($obj->_saved_doc_content);
            unset($obj->_saved_doc_message);

            $oDocumentModel = &getModel('document');

            // 카테고리가 변경되었으면 검사후 없는 카테고리면 0으로 세팅
            if($source_obj->get('category_srl')!=$obj->category_srl) {
                $category_list = $oDocumentModel->getCategoryList($obj->module_srl);
                if(!$category_list[$obj->category_srl]) $obj->category_srl = 0;
            }

            // 수정 순서를 조절
            $obj->update_order = getNextSequence() * -1;

            // 비밀번호가 있으면 md5 hash
            if($obj->password) $obj->password = md5($obj->password);

            // 원본 작성인과 수정하려는 수정인이 동일할 시에 또는 History를 사용하면 로그인된 사용자 정보를 입력
            if(Context::get('is_logged')) {
                $logged_info = Context::get('logged_info');
                if($source_obj->get('member_srl')==$logged_info->member_srl || $bUseHistory) {
                    $obj->member_srl = $logged_info->member_srl;
                    $obj->user_name = $logged_info->user_name;
                    $obj->nick_name = $logged_info->nick_name;
                    $obj->email_address = $logged_info->email_address;
                    $obj->homepage = $logged_info->homepage;
                }
            }

            // 로그인한 유저가 작성한 글인데 nick_name이 없을 경우
            if($source_obj->get('member_srl')&& !$obj->nick_name) {
                $obj->member_srl = $source_obj->get('member_srl');
                $obj->user_name = $source_obj->get('user_name');
                $obj->nick_name = $source_obj->get('nick_name');
                $obj->email_address = $source_obj->get('email_address');
                $obj->homepage = $source_obj->get('homepage');
            }

            // 제목이 없으면 내용에서 추출
            settype($obj->title, "string");
            if($obj->title == '') $obj->title = cut_str(strip_tags($obj->content),20,'...');
            //그래도 없으면 Untitled
            if($obj->title == '') $obj->title = 'Untitled';

            // 내용에서 XE만의 태그를 삭제
            $obj->content = preg_replace('!<\!--(Before|After)(Document|Comment)\(([0-9]+),([0-9]+)\)-->!is', '', $obj->content);

            // 세션에서 최고 관리자가 아니면 iframe, script 제거
            if($logged_info->is_admin != 'Y') $obj->content = removeHackTag($obj->content);

            // DB에 입력
            $output = executeQuery('document.updateDocument', $obj);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            // 등록 성공시 확장 변수 등록
            $extra_keys = $oDocumentModel->getExtraKeys($obj->module_srl);
            if(count($extra_keys)) {
                $this->deleteDocumentExtraVars($obj->module_srl, $obj->document_srl);
                foreach($extra_keys as $idx => $extra_item) {
                    $value = '';
                    if(isset($obj->{'extra_vars'.$idx})) $value = trim($obj->{'extra_vars'.$idx});
                    elseif(isset($obj->{$extra_item->name})) $value = trim($obj->{$extra_item->name});
                    if(!isset($value)) continue;
                    $this->insertDocumentExtraVar($obj->module_srl, $obj->document_srl, $idx, $value);
                }
            }

            // 성공하였을 경우 category_srl이 있으면 카테고리 update
            if($source_obj->get('category_srl')!=$obj->category_srl) {
                if($source_obj->get('category_srl')) $this->updateCategoryCount($obj->module_srl, $source_obj->get('category_srl'));
                if($obj->category_srl) $this->updateCategoryCount($obj->module_srl, $obj->category_srl);
            }

            // 공지사항 글이면 공지사항 캐시 업데이트
            if($source_obj->is_notice == 'Y' || $obj->is_notice == 'Y') $this->updateDocumentNoticeCache($obj->module_srl);

            // trigger 호출 (after)
            if($output->toBool()) {
                $trigger_output = ModuleHandler::triggerCall('document.updateDocument', 'after', $obj);
                if(!$trigger_output->toBool()) {
                    $oDB->rollback();
                    return $trigger_output;
                }
            }

            // commit
            $oDB->commit();

            // 썸네일 파일 제거
            FileHandler::removeDir(sprintf('files/cache/thumbnails/%s',getNumberingPath($obj->document_srl, 3)));

            $output->add('document_srl',$obj->document_srl);
            return $output;
        }

        /**
         * @brief 문서 삭제
         **/
        function deleteDocument($document_srl, $is_admin = false) {
            // trigger 호출 (before)
            $trigger_obj->document_srl = $document_srl;
            $output = ModuleHandler::triggerCall('document.deleteDocument', 'before', $trigger_obj);
            if(!$output->toBool()) return $output;

            // begin transaction
            $oDB = &DB::getInstance();
            $oDB->begin();

            // document의 model 객체 생성
            $oDocumentModel = &getModel('document');

            // 기존 문서가 있는지 확인
            $oDocument = $oDocumentModel->getDocument($document_srl, $is_admin);
            if(!$oDocument->isExists() || $oDocument->document_srl != $document_srl) return new Object(-1, 'msg_invalid_document');

            // 권한이 있는지 확인
            if(!$oDocument->isGranted()) return new Object(-1, 'msg_not_permitted');

            // 글 삭제
            $args->document_srl = $document_srl;
            $output = executeQuery('document.deleteDocument', $args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            $this->deleteDocumentAliasByDocument($document_srl);

            $this->deleteDocumentHistory(null, $document_srl, null);

            // 카테고리가 있으면 카테고리 정보 변경
            if($oDocument->get('category_srl')) $this->updateCategoryCount($oDocument->get('module_srl'),$oDocument->get('category_srl'));

            // 신고 삭제
            executeQuery('document.deleteDeclared', $args);

            // 확장 변수 삭제
            $this->deleteDocumentExtraVars($oDocument->get('module_srl'), $oDocument->document_srl);

            // 공지사항 글이면 공지사항 캐시 업데이트
            if($oDocument->get('is_notice') == 'Y') $this->updateDocumentNoticeCache($oDocument->get('module_srl'));

            // trigger 호출 (after)
            if($output->toBool()) {
                $trigger_obj = $oDocument->getObjectVars();
                $trigger_output = ModuleHandler::triggerCall('document.deleteDocument', 'after', $trigger_obj);
                if(!$trigger_output->toBool()) {
                    $oDB->rollback();
                    return $trigger_output;
                }
            }

            // 썸네일 파일 제거
            FileHandler::removeDir(sprintf('files/cache/thumbnails/%s',getNumberingPath($document_srl, 3)));

            // commit
            $oDB->commit();

            return $output;
        }

        /**
         * @brief 특정 모듈의 공지사항 글에 대해 캐시
         **/
        function updateDocumentNoticeCache($module_srl) {
            $cache_file = _XE_PATH_.'files/cache/document_notice/'.getNumberingPath($module_srl,4).$module_srl.'.txt';
            FileHandler::removeFile($cache_file);
            $args->module_srl = $module_srl;
            $output = executeQueryArray('document.getNoticeList', $args);
            if(!$output->toBool()|| !$output->data) return;

            foreach($output->data as $key => $val) {
                $document_srls[] = $val->document_srl;
            }
            FileHandler::writeFile($cache_file, implode(',',$document_srls));
        }

        /**
         * @brief 해당 document의 조회수 증가
         **/
        function updateReadedCount($oDocument) {
            $document_srl = $oDocument->document_srl;
            $member_srl = $oDocument->get('member_srl');
            $logged_info = Context::get('logged_info');

            // 조회수 업데이트가 되면 trigger 호출 (after)
            $output = ModuleHandler::triggerCall('document.updateReadedCount', 'after', $oDocument);
            if(!$output->toBool()) return $output;
            // session에 정보로 조회수를 증가하였다고 생각하면 패스
            if($_SESSION['readed_document'][$document_srl]) return false;

            // 글의 작성 ip와 현재 접속자의 ip가 동일하면 패스
            if($document->ipaddress == $_SERVER['REMOTE_ADDR']) {
                $_SESSION['readed_document'][$document_srl] = true;
                return false;
            }

            // document의 작성자가 회원일때 글쓴이와 현재 로그인 사용자의 정보가 일치하면 읽었다고 판단후 세션 등록하고 패스
            if($member_srl && $logged_info->member_srl == $member_srl) {
                $_SESSION['readed_document'][$document_srl] = true;
                return false;
            }

            // 조회수 업데이트
            $args->document_srl = $document_srl;
            $output = executeQuery('document.updateReadedCount', $args);

            // 세션 등록
            $_SESSION['readed_document'][$document_srl] = true;
        }

        /**
         * @breif documents 테이블의 확장 변수 등록
         **/
        function insertDocumentExtraKey($module_srl, $var_idx, $var_name, $var_type, $var_is_required = 'N', $var_search = 'N', $var_default = '', $var_desc = '') {
            if(!$module_srl || !$var_idx || !$var_name || !$var_type) return new Object(-1,'msg_invalid_request');
    
            $obj->module_srl = $module_srl;
            $obj->var_idx = $var_idx;
            $obj->var_name = $var_name;
            $obj->var_type = $var_type;
            $obj->var_is_required = $var_is_required=='Y'?'Y':'N';
            $obj->var_search = $var_search=='Y'?'Y':'N';
            $obj->var_default = $var_default;
            $obj->var_desc = $var_desc;

            $output = executeQuery('document.getDocumentExtraKeys', $obj);
            if(!$output->data) return executeQuery('document.insertDocumentExtraKey', $obj);
            $output = executeQuery('document.updateDocumentExtraKey', $obj);
            return $output;
        }

        /**
         * @brief documents 확장변수 제거
         **/
        function deleteDocumentExtraKeys($module_srl, $var_idx = null) {
            if(!$module_srl) return new Object(-1,'msg_invalid_request');
            $obj->module_srl = $module_srl;
            if(!is_null($var_idx)) $obj->var_idx = $var_idx;
            $output = executeQuery('document.deleteDocumentExtraKeys', $obj);
            if(!$output->toBool()) return $output;

            return executeQuery('document.deleteDocumentExtraVars', $obj);
        }

        /**
         * @breif documents 테이블의 확장 변수 값 등록
         **/
        function insertDocumentExtraVar($module_srl, $document_srl, $var_idx, $value, $lang_code = '') {
            if(!$module_srl || !$document_srl || !$var_idx || !isset($value)) return new Object(-1,'msg_invalid_request');
            if(!$lang_code) $lang_code = Context::getLangType();
    
            $obj->module_srl = $module_srl;
            $obj->document_srl = $document_srl;
            $obj->var_idx = $var_idx;
            $obj->value = $value;
            $obj->lang_code = $lang_code ;

            executeQuery('document.insertDocumentExtraVar', $obj);
        }

        /**
         * @brief documents 확장변수 값 제거
         **/
        function deleteDocumentExtraVars($module_srl, $document_srl = null, $var_idx = null) {
            $obj->module_srl = $module_srl;
            if(!is_null($document_srl)) $obj->document_srl = $document_srl;
            if(!is_null($var_idx)) $obj->var_idx = $var_idx;
            return executeQuery('document.deleteDocumentExtraVars', $obj);
        }
        

        /**
         * @brief 해당 document의 추천수 증가
         **/
        function updateVotedCount($document_srl, $point = 1) {
            if($point > 0) $failed_voted = 'failed_voted';
            else $failed_voted = 'failed_blamed';

            // 세션 정보에 추천 정보가 있으면 중단
            if($_SESSION['voted_document'][$document_srl]) return new Object(-1, $failed_voted);

            // 문서 원본을 가져옴
            $oDocumentModel = &getModel('document');
            $oDocument = $oDocumentModel->getDocument($document_srl, false, false);

            // 글의 작성 ip와 현재 접속자의 ip가 동일하면 패스
            if($oDocument->get('ipaddress') == $_SERVER['REMOTE_ADDR']) {
                $_SESSION['voted_document'][$document_srl] = true;
                return new Object(-1, $failed_voted);
            }

            // document의 작성자가 회원일때 조사
            if($oDocument->get('member_srl')) {
                // member model 객체 생성
                $oMemberModel = &getModel('member');
                $member_srl = $oMemberModel->getLoggedMemberSrl();

                // 글쓴이와 현재 로그인 사용자의 정보가 일치하면 읽었다고 생각하고 세션 등록후 패스
                if($member_srl && $member_srl == $oDocument->get('member_srl')) {
                    $_SESSION['voted_document'][$document_srl] = true;
                    return new Object(-1, $failed_voted);
                }
            }

            // 로그인 사용자이면 member_srl, 비회원이면 ipaddress로 판단
            if($member_srl) {
                $args->member_srl = $member_srl;
            } else {
                $args->ipaddress = $_SERVER['REMOTE_ADDR'];
            }
            $args->document_srl = $document_srl;
            $output = executeQuery('document.getDocumentVotedLogInfo', $args);

            // 로그 정보에 추천 로그가 있으면 세션 등록후 패스
            if($output->data->count) {
                $_SESSION['voted_document'][$document_srl] = true;
                return new Object(-1, $failed_voted);
            }

            // 추천수 업데이트
            if($point < 0)
            {
                $args->blamed_count = $oDocument->get('blamed_count') + $point;
                $output = executeQuery('document.updateBlamedCount', $args);
            }
            else
            {
                $args->voted_count = $oDocument->get('voted_count') + $point;
                $output = executeQuery('document.updateVotedCount', $args);
            }
            if(!$output->toBool()) return $output;

            // 로그 남기기
            $args->point = $point;
            $output = executeQuery('document.insertDocumentVotedLog', $args);
            if(!$output->toBool()) return $output;

            // 세션 정보에 남김
            $_SESSION['voted_document'][$document_srl] = true;

            $obj->member_srl = $oDocument->get('member_srl');
            $obj->module_srl = $oDocument->get('module_srl');
            $obj->point = $point;
            $output = ModuleHandler::triggerCall('document.updateVotedCount', 'after', $obj);
            if(!$output->toBool()) return $output;

            // 결과 리턴
            if($point > 0)
                return new Object(0, 'success_voted');
            else
                return new Object(0, 'success_blamed');
        }

        /**
         * @brief 게시글 신고
         **/
        function declaredDocument($document_srl) {
            // 세션 정보에 신고 정보가 있으면 중단
            if($_SESSION['declared_document'][$document_srl]) return new Object(-1, 'failed_declared');

            // 이미 신고되었는지 검사
            $args->document_srl = $document_srl;
            $output = executeQuery('document.getDeclaredDocument', $args);
            if(!$output->toBool()) return $output;
            $declared_count = $output->data->declared_count;

            // 문서 원본을 가져옴
            $oDocumentModel = &getModel('document');
            $oDocument = $oDocumentModel->getDocument($document_srl, false, false);

            // 글의 작성 ip와 현재 접속자의 ip가 동일하면 패스
            if($oDocument->get('ipaddress') == $_SERVER['REMOTE_ADDR']) {
                $_SESSION['declared_document'][$document_srl] = true;
                return new Object(-1, 'failed_declared');
            }

            // document의 작성자가 회원일때 조사
            if($oDocument->get('member_srl')) {
                // member model 객체 생성
                $oMemberModel = &getModel('member');
                $member_srl = $oMemberModel->getLoggedMemberSrl();

                // 글쓴이와 현재 로그인 사용자의 정보가 일치하면 읽었다고 생각하고 세션 등록후 패스
                if($member_srl && $member_srl == $oDocument->get('member_srl')) {
                    $_SESSION['declared_document'][$document_srl] = true;
                    return new Object(-1, 'failed_declared');
                }
            }

            // 로그인 사용자이면 member_srl, 비회원이면 ipaddress로 판단
            if($member_srl) {
                $args->member_srl = $member_srl;
            } else {
                $args->ipaddress = $_SERVER['REMOTE_ADDR'];
            }
            $args->document_srl = $document_srl;
            $output = executeQuery('document.getDocumentDeclaredLogInfo', $args);

            // 로그 정보에 신고 로그가 있으면 세션 등록후 패스
            if($output->data->count) {
                $_SESSION['declared_document'][$document_srl] = true;
                return new Object(-1, 'failed_declared');
            }

            // 신고글 추가
            if($declared_count > 0) $output = executeQuery('document.updateDeclaredDocument', $args);
            else $output = executeQuery('document.insertDeclaredDocument', $args);
            if(!$output->toBool()) return $output;

            // 로그 남기기
            $output = executeQuery('document.insertDocumentDeclaredLog', $args);

            // 세션 정보에 남김
            $_SESSION['declared_document'][$document_srl] = true;

            $this->setMessage('success_declared');
        }

        /**
         * @brief 해당 document의 댓글 수 증가
         * 댓글수를 증가시키면서 수정 순서와 수정일, 수정자를 등록
         **/
        function updateCommentCount($document_srl, $comment_count, $last_updater, $comment_inserted = false) {
            $args->document_srl = $document_srl;
            $args->comment_count = $comment_count;

            if($comment_inserted) {
                $args->update_order = -1*getNextSequence();
                $args->last_updater = $last_updater;
            }

            return executeQuery('document.updateCommentCount', $args);
        }

        /**
         * @brief 해당 document의 엮인글 수증가
         **/
        function updateTrackbackCount($document_srl, $trackback_count) {
            $args->document_srl = $document_srl;
            $args->trackback_count = $trackback_count;

            return executeQuery('document.updateTrackbackCount', $args);
        }

        /**
         * @brief 카테고리 추가
         **/
        function insertCategory($obj) {
            // 특정 카테고리의 하단으로 추가시 정렬순서 재정렬
            if($obj->parent_srl) {
                // 부모 카테고리 구함
                $oDocumentModel = &getModel('document');
                $parent_category = $oDocumentModel->getCategory($obj->parent_srl);
                $obj->list_order = $parent_category->list_order;
                $this->updateCategoryListOrder($parent_category->module_srl, $parent_category->list_order+1);
                if(!$obj->category_srl) $obj->category_srl = getNextSequence();
            } else {
                $obj->list_order = $obj->category_srl = getNextSequence();
            }

            $output = executeQuery('document.insertCategory', $obj);
            if($output->toBool()) {
                $output->add('category_srl', $obj->category_srl);
                $this->makeCategoryFile($obj->module_srl);
            }

            return $output;
        }

        /**
         * @brief 특정 카테고리 부터 list_count 증가
         **/
        function updateCategoryListOrder($module_srl, $list_order) {
            $args->module_srl = $module_srl;
            $args->list_order = $list_order;
            return executeQuery('document.updateCategoryOrder', $args);
        }

        /**
         * @brief 카테고리에 문서의 숫자를 변경
         **/
        function updateCategoryCount($module_srl, $category_srl, $document_count = 0) {
            // document model 객체 생성
            $oDocumentModel = &getModel('document');
            if(!$document_count) $document_count = $oDocumentModel->getCategoryDocumentCount($category_srl);

            $args->category_srl = $category_srl;
            $args->document_count = $document_count;
            $output = executeQuery('document.updateCategoryCount', $args);
            if($output->toBool()) $this->makeCategoryFile($module_srl);

            return $output;
        }

        /**
         * @brief 카테고리의 정보를 수정
         **/
        function updateCategory($obj) {
            $output = executeQuery('document.updateCategory', $obj);
            if($output->toBool()) $this->makeCategoryFile($obj->module_srl);
            return $output;
        }

        /**
        /**
         * @brief 카테고리 삭제
         **/
        function deleteCategory($category_srl) {
            $args->category_srl = $category_srl;
            $oDocumentModel = &getModel('document');
            $category_info = $oDocumentModel->getCategory($category_srl);

            // 자식 카테고리가 있는지 체크하여 있으면 삭제 못한다는 에러 출력
            $output = executeQuery('document.getChildCategoryCount', $args);
            if(!$output->toBool()) return $output;
            if($output->data->count>0) return new Object(-1, 'msg_cannot_delete_for_child');

            // 카테고리 정보를 삭제
            $output = executeQuery('document.deleteCategory', $args);
            if(!$output->toBool()) return $output;

            $this->makeCategoryFile($category_info->module_srl);

            // 현 카테고리 값을 가지는 문서들의 category_srl을 0 으로 세팅
            unset($args);

            $args->target_category_srl = 0;
            $args->source_category_srl = $category_srl;
            $output = executeQuery('document.updateDocumentCategory', $args);

            return $output;
        }

        /**
         * @brief 특정 모듈의 카테고리를 모두 삭제
         **/
        function deleteModuleCategory($module_srl) {
            $args->module_srl = $module_srl;
            $output = executeQuery('document.deleteModuleCategory', $args);
            return $output;
        }

        /**
         * @brief 카테고리를 상단으로 이동
         **/
        function moveCategoryUp($category_srl) {
            $oDocumentModel = &getModel('document');

            // 선택된 카테고리의 정보를 구한다
            $args->category_srl = $category_srl;
            $output = executeQuery('document.getCategory', $args);

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
            $oDocumentModel = &getModel('document');

            // 선택된 카테고리의 정보를 구한다
            $args->category_srl = $category_srl;
            $output = executeQuery('document.getCategory', $args);

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

        /**
         * @brief 특정 module_srl에 해당하는 document_extra_keys type, required등의 값을 체크하여 header에 javascript 코드 추가
         **/
        function addXmlJsFilter($module_srl) {
            $oDocumentModel = &getModel('document');
            $extra_keys = $oDocumentModel->getExtraKeys($module_srl);
            if(!count($extra_keys)) return;

            $js_code = "";

            $logged_info = Context::get('logged_info');

            foreach($extra_keys as $idx => $val) {
                $js_code .= sprintf('alertMsg["extra_vars%s"] = "%s";', $val->idx, $val->name);
                $js_code .= sprintf('target_type_list["extra_vars%s"] = "%s";', $val->idx, $val->type);
                $js_code .= sprintf('extra_vars[extra_vars.length] = "extra_vars%s";', $val->idx);
                if($val->is_required == 'Y' && $logged_info->is_admin != 'Y') $js_code .= sprintf('notnull_list[notnull_list.length] = "extra_vars%s";',$val->idx);
            }

            $js_code = "<script type=\"text/javascript\">//<![CDATA[\n".$js_code."\n//]]></script>";
            Context::addHtmlHeader($js_code);
        }

        /**
         * @brief 카테고리를 캐시 파일로 저장
         **/
        function makeCategoryFile($module_srl) {
            // 캐시 파일 생성시 필요한 정보가 없으면 그냥 return
            if(!$module_srl) return false;

            // 모듈 정보를 가져옴 (mid를 구하기 위해)
            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
            $mid = $module_info->mid;

            if(!is_dir('./files/cache/document_category')) FileHandler::makeDir('./files/cache/document_category');

            // 캐시 파일의 이름을 지정
            $xml_file = sprintf("./files/cache/document_category/%s.xml.php", $module_srl);
            $php_file = sprintf("./files/cache/document_category/%s.php", $module_srl);

            // 카테고리 목록을 구함
            $args->module_srl = $module_srl;
            $args->sort_index = 'list_order';
            $output = executeQuery('document.getCategoryList', $args);

            $category_list = $output->data;

            if(!$category_list) {
                FileHandler::removeFile($xml_file);
                FileHandler::removeFile($php_file);
                return false;
            }
            if(!is_array($category_list)) $category_list = array($category_list);

            $category_count = count($category_list);
            for($i=0;$i<$category_count;$i++) {
                $category_srl = $category_list[$i]->category_srl;
                if(!preg_match('/^[0-9,]+$/', $category_list[$i]->group_srls)) $category_list[$i]->group_srls = '';
                $list[$category_srl] = $category_list[$i];
            }

            // 구해온 데이터가 없다면 노드데이터가 없는 xml 파일만 생성
            if(!$list) {
                $xml_buff = "<root />";
                FileHandler::writeFile($xml_file, $xml_buff);
                FileHandler::writeFile($php_file, '<?php if(!defined("__ZBXE__")) exit(); ?>');
                return $xml_file;
            }

            // 구해온 데이터가 하나라면 array로 바꾸어줌
            if(!is_array($list)) $list = array($list);

            // 루프를 돌면서 tree 구성
            foreach($list as $category_srl => $node) {
                $node->mid = $mid;
                $parent_srl = (int)$node->parent_srl;
                $tree[$parent_srl][$category_srl] = $node;
            }

            // 캐시 파일의 권한과 그룹 설정을 위한 공통 헤더
            $header_script = 
                '$lang_type = Context::getLangType(); '.
                '$is_logged = Context::get(\'is_logged\'); '.
                '$logged_info = Context::get(\'logged_info\'); '.
                'if($is_logged) {'.
                    'if($logged_info->is_admin=="Y") $is_admin = true; '.
                    'else $is_admin = false; '.
                    '$group_srls = array_keys($logged_info->group_list); '.
                '} else { '.
                    '$is_admin = false; '.
                    '$group_srsl = array(); '.
                '} '."\n";

            // xml 캐시 파일 생성 (xml캐시는 따로 동작하기에 session 지정을 해주어야 함)
            $xml_header_buff = '';
            $xml_body_buff = $this->getXmlTree($tree[0], $tree, $module_info->site_srl, $xml_header_buff);
            $xml_buff = sprintf(
                '<?php '.
                'define(\'__ZBXE__\', true); '.
                'require_once(\'../../../config/config.inc.php\'); '.
                '$oContext = &Context::getInstance(); '.
                '$oContext->init(); '.
                'header("Content-Type: text/xml; charset=UTF-8"); '.
                'header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); '.
                'header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); '.
                'header("Cache-Control: no-store, no-cache, must-revalidate"); '.
                'header("Cache-Control: post-check=0, pre-check=0", false); '.
                'header("Pragma: no-cache"); '.
                '%s'.
                '%s'.
                '?>'.
                '<root>%s</root>', 
                $header_script,
                $xml_header_buff,
                $xml_body_buff
            );

            // php 캐시 파일 생성
            $php_output = $this->getPhpCacheCode($tree[0], $tree, $module_info->site_srl, $php_header_buff);
            $php_buff = sprintf(
                '<?php '.
                'if(!defined("__ZBXE__")) exit(); '.
                '%s; '.
                '%s; '.
                '$menu->list = array(%s); '.
                '?>', 
                $header_script,
                $php_header_buff,
                $php_output['buff']
            );

            // 파일 저장
            FileHandler::writeFile($xml_file, $xml_buff);
            FileHandler::writeFile($php_file, $php_buff);
            return $xml_file;
        }

        /**
         * @brief array로 정렬된 노드들을 parent_srl을 참조하면서 recursive하게 돌면서 xml 데이터 생성
         * 메뉴 xml파일은 node라는 tag가 중첩으로 사용되며 이 xml doc으로 관리자 페이지에서 메뉴를 구성해줌\n
         * (tree_menu.js 에서 xml파일을 바로 읽고 tree menu를 구현)
         **/
        function getXmlTree($source_node, $tree, $site_srl, &$xml_header_buff) {
            if(!$source_node) return;
            
            foreach($source_node as $category_srl => $node) {
                $child_buff = "";

                // 자식 노드의 데이터 가져옴
                if($category_srl && $tree[$category_srl]) $child_buff = $this->getXmlTree($tree[$category_srl], $tree, $site_srl, $xml_header_buff);

                // 변수 정리
                $expand = $node->expand;
                $group_srls = $node->group_srls;
                $mid = $node->mid;
                $module_srl = $node->module_srl;
                $parent_srl = $node->parent_srl;
                $color = $node->color;
                // node->group_srls값이 있으면
                if($group_srls) $group_check_code = sprintf('($is_admin==true||(is_array($group_srls)&&count(array_intersect($group_srls, array(%s)))))',$group_srls);
                else $group_check_code = "true";

                $title = $node->title;
                $oModuleAdminModel = &getAdminModel('module');
                $langs = $oModuleAdminModel->getLangCode($site_srl, $title);
                if(count($langs)) foreach($langs as $key => $val) $xml_header_buff .= sprintf('$_titles[%d]["%s"] = "%s"; ', $category_srl, $key, str_replace('"','\\"',$val));

                $attribute = sprintf(
                        'mid="%s" module_srl="%d" node_srl="%d" parent_srl="%d" category_srl="%d" text="<?php echo (%s?($_titles[%d][$lang_type]):"")?>" url="%s" expand="%s" color="%s" document_count="%d" ',
                        $mid,
                        $module_srl,
                        $category_srl,
                        $parent_srl,
                        $category_srl,
                        $group_check_code,
                        $category_srl,
                        getUrl('','mid',$node->mid,'category',$category_srl),
                        $expand,
                        $color,
                        $node->document_count
                );

                if($child_buff) $buff .= sprintf('<node %s>%s</node>', $attribute, $child_buff);
                else $buff .=  sprintf('<node %s />', $attribute);
            }
            return $buff;
        }

        /**
         * @brief array로 정렬된 노드들을 php code로 변경하여 return
         * 메뉴에서 메뉴를 tpl에 사용시 xml데이터를 사용할 수도 있지만 별도의 javascript 사용이 필요하기에
         * php로 된 캐시파일을 만들어서 db이용없이 바로 메뉴 정보를 구할 수 있도록 한다
         * 이 캐시는 ModuleHandler::displayContent() 에서 include하여 Context::set() 한다
         **/
        function getPhpCacheCode($source_node, $tree, $site_srl, &$php_header_buff) {
            $output = array("buff"=>"", "category_srl_list"=>array());
            if(!$source_node) return $output;

            // 루프를 돌면서 1차 배열로 정리하고 include할 수 있는 php script 코드를 생성
            foreach($source_node as $category_srl => $node) {

                // 자식 노드가 있으면 자식 노드의 데이터를 먼저 얻어옴 
                if($category_srl&&$tree[$category_srl]) $child_output = $this->getPhpCacheCode($tree[$category_srl], $tree, $site_srl, $php_header_buff);
                else $child_output = array("buff"=>"", "category_srl_list"=>array());

                // 현재 노드의 url값이 공란이 아니라면 category_srl_list 배열값에 입력
                $child_output['category_srl_list'][] = $node->category_srl;
                $output['category_srl_list'] = array_merge($output['category_srl_list'], $child_output['category_srl_list']);

                // node->group_srls값이 있으면 
                if($node->group_srls) $group_check_code = sprintf('($is_admin==true||(is_array($group_srls)&&count(array_intersect($group_srls, array(%s)))))',$node->group_srls);
                else $group_check_code = "true";

                // 변수 정리
                $selected = '"'.implode('","',$child_output['category_srl_list']).'"';
                $child_buff = $child_output['buff'];
                $expand = $node->expand;

                $title = $node->title;
                $oModuleAdminModel = &getAdminModel('module');
                $langs = $oModuleAdminModel->getLangCode($site_srl, $title);
                if(count($langs)) foreach($langs as $key => $val) $php_header_buff .= sprintf('$_titles[%d]["%s"] = "%s"; ', $category_srl, $key, str_replace('"','\\"',$val));

                // 속성을 생성한다 ( category_srl_list를 이용해서 선택된 메뉴의 노드에 속하는지를 검사한다. 꽁수지만 빠르고 강력하다고 생각;;)
                $attribute = sprintf(
                    '"mid" => "%s", "module_srl" => "%d","node_srl"=>"%s","category_srl"=>"%s","parent_srl"=>"%s","text"=>$_titles[%d][$lang_type],"selected"=>(in_array(Context::get("category"),array(%s))?1:0),"expand"=>"%s","color"=>"%s", "list"=>array(%s),"document_count"=>"%d","grant"=>%s?true:false',
                    $node->mid,
                    $node->module_srl,
                    $node->category_srl,
                    $node->category_srl,
                    $node->parent_srl,
                    $node->category_srl,
                    $selected,
                    $expand,
                    $node->color,
                    $child_buff,
                    $node->document_count,
                    $group_check_code
                );
                
                // buff 데이터를 생성한다
                $output['buff'] .=  sprintf('%s=>array(%s),', $node->category_srl, $attribute);
            }
            return $output;
        }

        /**
         * @brief 게시물의 이 게시물을.. 클릭시 나타나는 팝업 메뉴를 추가하는 method
         **/
        function addDocumentPopupMenu($url, $str, $icon = '', $target = 'self') {
            $document_popup_menu_list = Context::get('document_popup_menu_list');
            if(!is_array($document_popup_menu_list)) $document_popup_menu_list = array();

            $obj->url = $url;
            $obj->str = $str;
            $obj->icon = $icon;
            $obj->target = $target;
            $document_popup_menu_list[] = $obj;

            Context::set('document_popup_menu_list', $document_popup_menu_list);
        }

        /**
         * @brief 관리자가 글 선택시 세션에 담음
         **/
        function procDocumentAddCart() {
            if(!Context::get('is_logged')) return new Object(-1, 'msg_not_permitted');

            // 게시글 번호 구함
            $srls = explode(',',Context::get('srls'));
            for($i=0;$i<count($srls);$i++) {
                $srl = trim($srls[$i]);
                if(!$srl) continue;
                $document_srls[] = $srl;
            }
            if(!count($document_srls)) return;

            // 게시글들의 모듈 번호를 구함
            $args->list_count = count($document_srls);
            $args->document_srls = implode(',',$document_srls);
            $args->order_type = 'asc';
            $output = executeQueryArray('document.getDocuments', $args);
            if(!$output->data) return new Object();

            unset($document_srls);
            foreach($output->data as $key => $val) {
                $document_srls[$val->module_srl][] = $val->document_srl;
            }
            if(!$document_srls || !count($document_srls)) return new Object();

            // 각 문서들의 모듈 관리자 여부 확인
            $oModuleModel = &getModel('module');
            $module_srls = array_keys($document_srls);
            for($i=0;$i<count($module_srls);$i++) {
                $module_srl = $module_srls[$i];
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
                if(!$module_info) {
                    unset($document_srls[$module_srl]);
                    continue;
                }
                $grant = $oModuleModel->getGrant($module_info, Context::get('logged_info'));
                if(!$grant->manager) {
                    unset($document_srls[$module_srl]);
                    continue;
                }

            }
            if(!count($document_srls)) return new Object();

            foreach($document_srls as $module_srl => $documents) {
                $cnt = count($documents);
                for($i=0;$i<$cnt;$i++) {
                    $document_srl = (int)trim($documents[$i]);
                    if(!$document_srls) continue;
                    if($_SESSION['document_management'][$document_srl]) unset($_SESSION['document_management'][$document_srl]);
                    else $_SESSION['document_management'][$document_srl] = true;
                }
            }
        }

        /**
         * @brief 세션에 담긴 선택글의 이동/ 삭제
         **/
        function procDocumentManageCheckedDocument() {
            if(!Context::get('is_logged')) return new Object(-1,'msg_not_permitted');

            $type = Context::get('type');
            $module_srl = Context::get('target_module');
            $category_srl = Context::get('target_category');
            $message_content = Context::get('message_content');
            if($message_content) $message_content = nl2br($message_content);

            $cart = Context::get('cart');
            if($cart) $document_srl_list = explode('|@|', $cart);
            else $document_srl_list = array();

            $document_srl_count = count($document_srl_list);

            // 쪽지 발송
            if($message_content) {

                $oCommunicationController = &getController('communication');
                $oDocumentModel = &getModel('document');

                $logged_info = Context::get('logged_info');

                $title = cut_str($message_content,10,'...');
                $sender_member_srl = $logged_info->member_srl;

                for($i=0;$i<$document_srl_count;$i++) {
                    $document_srl = $document_srl_list[$i];
                    $oDocument = $oDocumentModel->getDocument($document_srl);
                    if(!$oDocument->get('member_srl') || $oDocument->get('member_srl')==$sender_member_srl) continue;

                    if($type=='move') $purl = sprintf("<a href=\"%s\" onclick=\"window.open(this.href);return false;\">%s</a>", $oDocument->getPermanentUrl(), $oDocument->getPermanentUrl());
                    else $purl = "";
                    $content .= sprintf("<div>%s</div><hr />%s<div style=\"font-weight:bold\">%s</div>%s",$message_content, $purl, $oDocument->getTitleText(), $oDocument->getContent(false, false, false));

                    $oCommunicationController->sendMessage($sender_member_srl, $oDocument->get('member_srl'), $title, $content, false);
                }
            }

            // 스팸 처리가 되지 않도록 스팸필터 설정
            $oSpamController = &getController('spamfilter');
            $oSpamController->setAvoidLog();
            $oDocumentAdminController = &getAdminController('document');

            if($type == 'move') {
                if(!$module_srl) return new Object(-1, 'fail_to_move');

                $output = $oDocumentAdminController->moveDocumentModule($document_srl_list, $module_srl, $category_srl);
                if(!$output->toBool()) return new Object(-1, 'fail_to_move');

                $msg_code = 'success_moved';

            } elseif($type == 'copy') {
                if(!$module_srl) return new Object(-1, 'fail_to_move');

                $output = $oDocumentAdminController->copyDocumentModule($document_srl_list, $module_srl, $category_srl);
                if(!$output->toBool()) return new Object(-1, 'fail_to_move');

                $msg_code = 'success_registed';

            } elseif($type =='delete') {
                $oDB = &DB::getInstance();
                $oDB->begin();
                for($i=0;$i<$document_srl_count;$i++) {
                    $document_srl = $document_srl_list[$i];
                    $output = $this->deleteDocument($document_srl, true);
                    if(!$output->toBool()) return new Object(-1, 'fail_to_delete');
                }
                $oDB->commit();
                $msg_code = 'success_deleted';
            }

            $_SESSION['document_management'] = array();

            $this->setMessage($msg_code);
        }

        function procDocumentInsertModuleConfig()
        {
            $module_srl = Context::get('target_module_srl');
            if(preg_match('/^([0-9,]+)$/',$module_srl)) $module_srl = explode(',',$module_srl);
            else $module_srl = array($module_srl);

            $document_config = null;
            $document_config->use_history = Context::get('use_history');
            if(!$document_config->use_history) $document_config->user_history = 'N';

            $oModuleController = &getController('module');
            for($i=0;$i<count($module_srl);$i++) {
                $srl = trim($module_srl[$i]);
                if(!$srl) continue;
                $output = $oModuleController->insertModulePartConfig('document',$srl,$document_config);
            }
            $this->setError(-1);
            $this->setMessage('success_updated');
        }

    }
?>
