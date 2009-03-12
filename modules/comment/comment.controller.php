<?php
    /**
     * @class  commentController
     * @author zero (zero@nzeo.com)
     * @brief  comment 모듈의 controller class
     **/

    class commentController extends comment {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 댓글의 추천을 처리하는 action (Up)
         **/
        function procCommentVoteUp() {
            if(!Context::get('is_logged')) return new Object(-1, 'msg_invalid_request');

            $comment_srl = Context::get('target_srl');
            if(!$comment_srl) return new Object(-1, 'msg_invalid_request');

            $point = 1;
            return $this->updateVotedCount($comment_srl, $point);
        }

        /**
         * @brief 댓글의 추천을 처리하는 action (Down)
         **/
        function procCommentVoteDown() {
            if(!Context::get('is_logged')) return new Object(-1, 'msg_invalid_request');

            $comment_srl = Context::get('target_srl');
            if(!$comment_srl) return new Object(-1, 'msg_invalid_request');

            $point = -1;
            return $this->updateVotedCount($comment_srl, $point);
        }

        /**
         * @brief 댓글이 신고될 경우 호출되는 action
         **/
        function procCommentDeclare() {
            if(!Context::get('is_logged')) return new Object(-1, 'msg_invalid_request');

            $comment_srl = Context::get('target_srl');
            if(!$comment_srl) return new Object(-1, 'msg_invalid_request');

            return $this->declaredComment($comment_srl);
        }

        /**
         * @brief document삭제시 해당 document의 댓글을 삭제하는 trigger
         **/
        function triggerDeleteDocumentComments(&$obj) {
            $document_srl = $obj->document_srl;
            if(!$document_srl) return new Object();

            return $this->deleteComments($document_srl, true);
        }

        /**
         * @brief module 삭제시 해당 댓글을 모두 삭제하는 trigger
         **/
        function triggerDeleteModuleComments(&$obj) {
            $module_srl = $obj->module_srl;
            if(!$module_srl) return new Object();

            $oCommentController = &getAdminController('comment');
            return $oCommentController->deleteModuleComments($module_srl);
        }

        /**
         * @brief 코멘트의 권한 부여
         * 세션값으로 현 접속상태에서만 사용 가능
         **/
        function addGrant($comment_srl) {
            $_SESSION['own_comment'][$comment_srl] = true;
        }

        /**
         * @brief 댓글 입력
         **/
        function insertComment($obj, $manual_inserted = false) {
            $obj->__isupdate = false;
            // trigger 호출 (before)
            $output = ModuleHandler::triggerCall('comment.insertComment', 'before', $obj);
            if(!$output->toBool()) return $output;

            // document_srl에 해당하는 글이 있는지 확인
            $document_srl = $obj->document_srl;
            if(!$document_srl) return new Object(-1,'msg_invalid_document');

            // document model 객체 생성
            $oDocumentModel = &getModel('document');

            // 원본글을 가져옴
            if(!$manual_inserted) {
                $oDocument = $oDocumentModel->getDocument($document_srl);

                if($document_srl != $oDocument->document_srl) return new Object(-1,'msg_invalid_document');
                if($oDocument->isLocked()) return new Object(-1,'msg_invalid_request');

                if($obj->password) $obj->password = md5($obj->password);
                if($obj->homepage &&  !preg_match('/^http:\/\//i',$obj->homepage)) $obj->homepage = 'http://'.$obj->homepage;

                // 로그인 된 회원일 경우 회원의 정보를 입력
                if(Context::get('is_logged')) {
                    $logged_info = Context::get('logged_info');
                    $obj->member_srl = $logged_info->member_srl;
                    $obj->user_id = $logged_info->user_id;
                    $obj->user_name = $logged_info->user_name;
                    $obj->nick_name = $logged_info->nick_name;
                    $obj->email_address = $logged_info->email_address;
                    $obj->homepage = $logged_info->homepage;
                }
            }

            // 로그인정보가 없고 사용자 이름이 없으면 오류 표시
            if(!$logged_info->member_srl && !$obj->nick_name) return new Object(-1,'msg_invalid_request');

            if(!$obj->comment_srl) $obj->comment_srl = getNextSequence();

            // 순서를 정함
            $obj->list_order = getNextSequence() * -1;

            // 내용에서 XE만의 태그를 삭제
            $obj->content = preg_replace('!<\!--(Before|After)(Document|Comment)\(([0-9]+),([0-9]+)\)-->!is', '', $obj->content);
            if(!$obj->regdate) $obj->regdate = date("YmdHis");

            // 세션에서 최고 관리자가 아니면 iframe, script 제거
            if($logged_info->is_admin != 'Y') $obj->content = removeHackTag($obj->content);

            if(!$obj->notify_message) $obj->notify_message = 'N';
            if(!$obj->is_secret) $obj->is_secret = 'N';

            // begin transaction
            $oDB = &DB::getInstance();
            $oDB->begin();

            // 댓글 목록 부분을 먼저 입력
            $list_args->comment_srl = $obj->comment_srl;
            $list_args->document_srl = $obj->document_srl;
            $list_args->module_srl = $obj->module_srl;
            $list_args->regdate = $obj->regdate;

            // 부모댓글이 없으면 바로 데이터를 설정
            if(!$obj->parent_srl) {
                $list_args->head = $list_args->arrange = $obj->comment_srl;
                $list_args->depth = 0;

            // 부모댓글이 있으면 부모글의 정보를 구해옴
            } else {
                // 부모댓글의 정보를 구함
                $parent_args->comment_srl = $obj->parent_srl;
                $parent_output = executeQuery('comment.getCommentListItem', $parent_args);

                // 부모댓글이 존재하지 않으면 return
                if(!$parent_output->toBool() || !$parent_output->data) return;
                $parent = $parent_output->data;

                $list_args->head = $parent->head;
                $list_args->depth = $parent->depth+1;

                // depth가 2단계 미만이면 별도의 update문 없이 insert만으로 쓰레드 정리
                if($list_args->depth<2) {
                    $list_args->arrange = $obj->comment_srl;

                // depth가 2단계 이상이면 반업데이트 실행
                } else {
                    // 부모 댓글과 같은 head를 가지고 depth가 같거나 작은 댓글중 제일 위 댓글을 구함
                    $p_args->head = $parent->head;
                    $p_args->arrange = $parent->arrange;
                    $p_args->depth = $parent->depth;
                    $output = executeQuery('comment.getCommentParentNextSibling', $p_args);

                    if($output->data->arrange) {
                        $list_args->arrange = $output->data->arrange;
                        $output = executeQuery('comment.updateCommentListArrange', $list_args);
                    } else {
                        $list_args->arrange = $obj->comment_srl;
                    }

                }
            }

            $output = executeQuery('comment.insertCommentList', $list_args);
            if(!$output->toBool()) return $output;

            // 댓글 본문을 입력
            $output = executeQuery('comment.insertComment', $obj);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            // 입력에 이상이 없으면 해당 글의 댓글 수를 올림
            if(!$manual_inserted) {
                // comment model객체 생성
                $oCommentModel = &getModel('comment');

                // 해당 글의 전체 댓글 수를 구해옴
                $comment_count = $oCommentModel->getCommentCount($document_srl);

                // document의 controller 객체 생성
                $oDocumentController = &getController('document');

                // 해당글의 댓글 수를 업데이트
                $output = $oDocumentController->updateCommentCount($document_srl, $comment_count, $obj->nick_name, true);

                // 댓글의 권한을 부여
                $this->addGrant($obj->comment_srl);
            }


            // trigger 호출 (after)
            if($output->toBool()) {
                $trigger_output = ModuleHandler::triggerCall('comment.insertComment', 'after', $obj);
                if(!$trigger_output->toBool()) {
                    $oDB->rollback();
                    return $trigger_output;
                }
            }

            // commit
            $oDB->commit();

            if(!$manual_inserted) {
                // 원본글에 알림(notify_message)가 설정되어 있으면 메세지 보냄
                $oDocument->notify(Context::getLang('comment'), $obj->content);

                // 원본 댓글이 있고 원본 댓글에 알림(notify_message)가 있으면 메세지 보냄
                if($obj->parent_srl) {
                    $oParent = $oCommentModel->getComment($obj->parent_srl);
                    $oParent->notify(Context::getLang('comment'), $obj->content);
                }
            }


            $output->add('comment_srl', $obj->comment_srl);
            return $output;
        }

        /**
         * @brief 댓글 수정
         **/
        function updateComment($obj, $is_admin = false) {
            $obj->__isupdate = true;
            // trigger 호출 (before)
            $output = ModuleHandler::triggerCall('comment.updateComment', 'before', $obj);
            if(!$output->toBool()) return $output;

            // comment model 객체 생성
            $oCommentModel = &getModel('comment');

            // 원본 데이터를 가져옴
            $source_obj = $oCommentModel->getComment($obj->comment_srl);
            if(!$source_obj->getMemberSrl()) {
                $obj->member_srl = $source_obj->get('member_srl');
                $obj->user_name = $source_obj->get('user_name');
                $obj->nick_name = $source_obj->get('nick_name');
                $obj->email_address = $source_obj->get('email_address');
                $obj->homepage = $source_obj->get('homepage');
            }

            // 권한이 있는지 확인
            if(!$is_admin && !$source_obj->isGranted()) return new Object(-1, 'msg_not_permitted');

            if($obj->password) $obj->password = md5($obj->password);
            if($obj->homepage &&  !preg_match('/^http:\/\//i',$obj->homepage)) $obj->homepage = 'http://'.$obj->homepage;

            // 로그인 되어 있고 작성자와 수정자가 동일하면 수정자의 정보를 세팅
            if(Context::get('is_logged')) {
                $logged_info = Context::get('logged_info');
                if($source_obj->member_srl == $logged_info->member_srl) {
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

            // 내용에서 XE만의 태그를 삭제
            $obj->content = preg_replace('!<\!--(Before|After)(Document|Comment)\(([0-9]+),([0-9]+)\)-->!is', '', $obj->content);

            // 세션에서 최고 관리자가 아니면 iframe, script 제거
            if($logged_info->is_admin != 'Y') $obj->content = removeHackTag($obj->content);

            // begin transaction
            $oDB = &DB::getInstance();
            $oDB->begin();

            // 업데이트
            $output = executeQuery('comment.updateComment', $obj);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            // trigger 호출 (after)
            if($output->toBool()) {
                $trigger_output = ModuleHandler::triggerCall('comment.updateComment', 'after', $obj);
                if(!$trigger_output->toBool()) {
                    $oDB->rollback();
                    return $trigger_output;
                }
            }

            // commit
            $oDB->commit();

            $output->add('comment_srl', $obj->comment_srl);
            return $output;
        }

        /**
         * @brief 댓글 삭제
         **/
        function deleteComment($comment_srl, $is_admin = false) {

            // comment model 객체 생성
            $oCommentModel = &getModel('comment');

            // 기존 댓글이 있는지 확인
            $comment = $oCommentModel->getComment($comment_srl);
            if($comment->comment_srl != $comment_srl) return new Object(-1, 'msg_invalid_request');
            $document_srl = $comment->document_srl;

            // trigger 호출 (before)
            $output = ModuleHandler::triggerCall('comment.deleteComment', 'before', $comment);
            if(!$output->toBool()) return $output;

            // 해당 댓글에 child가 있는지 확인
            $child_count = $oCommentModel->getChildCommentCount($comment_srl);
            if($child_count>0) return new Object(-1, 'fail_to_delete_have_children');

            // 권한이 있는지 확인
            if(!$is_admin && !$comment->isGranted()) return new Object(-1, 'msg_not_permitted');

            // begin transaction
            $oDB = &DB::getInstance();
            $oDB->begin();

            // 삭제
            $args->comment_srl = $comment_srl;
            $output = executeQuery('comment.deleteComment', $args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            $output = executeQuery('comment.deleteCommentList', $args);

            // 댓글 수를 구해서 업데이트
            $comment_count = $oCommentModel->getCommentCount($document_srl);

            // document의 controller 객체 생성
            $oDocumentController = &getController('document');

            // 해당글의 댓글 수를 업데이트
            $output = $oDocumentController->updateCommentCount($document_srl, $comment_count, null, false);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            // trigger 호출 (after)
            if($output->toBool()) {
                $trigger_output = ModuleHandler::triggerCall('comment.deleteComment', 'after', $comment);
                if(!$trigger_output->toBool()) {
                    $oDB->rollback();
                    return $trigger_output;
                }
            }

            // commit
            $oDB->commit();

            $output->add('document_srl', $document_srl);
            return $output;
        }

        /**
         * @brief 특정 글의 모든 댓글 삭제
         **/
        function deleteComments($document_srl) {
            // document model객체 생성
            $oDocumentModel = &getModel('document');

            // 권한이 있는지 확인
            $oDocument = $oDocumentModel->getDocument($document_srl);
            if(!$oDocument->isExists() || !$oDocument->isGranted()) return new Object(-1, 'msg_not_permitted');

            // 댓글 본문 삭제
            $args->document_srl = $document_srl;
            $output = executeQuery('comment.deleteComments', $args);
            if(!$output->toBool()) return $output;

            // 댓글 목록 삭제
            $output = executeQuery('comment.deleteCommentsList', $args);

            return $output;
        }

        /**
         * @brief 해당 comment의 추천수 증가
         **/
        function updateVotedCount($comment_srl, $point = 1) {
            if($point > 0) $failed_voted = 'failed_voted';
            else $failed_voted = 'failed_blamed';

            // 세션 정보에 추천 정보가 있으면 중단
            if($_SESSION['voted_comment'][$comment_srl]) return new Object(-1, 'failed_voted');

            // 문서 원본을 가져옴
            $oCommentModel = &getModel('comment');
            $oComment = $oCommentModel->getComment($comment_srl, false, false);

            // 글의 작성 ip와 현재 접속자의 ip가 동일하면 패스
            if($oComment->get('ipaddress') == $_SERVER['REMOTE_ADDR']) {
                $_SESSION['voted_comment'][$comment_srl] = true;
                return new Object(-1, 'failed_voted');
            }

            // comment의 작성자가 회원일때 조사
            if($oComment->get('member_srl')) {
                // member model 객체 생성
                $oMemberModel = &getModel('member');
                $member_srl = $oMemberModel->getLoggedMemberSrl();

                // 글쓴이와 현재 로그인 사용자의 정보가 일치하면 읽었다고 생각하고 세션 등록후 패스
                if($member_srl && $member_srl == $oComment->get('member_srl')) {
                    $_SESSION['voted_comment'][$comment_srl] = true;
                    return new Object(-1, 'failed_voted');
                }
            }

            // 로그인 사용자이면 member_srl, 비회원이면 ipaddress로 판단
            if($member_srl) {
                $args->member_srl = $member_srl;
            } else {
                $args->ipaddress = $_SERVER['REMOTE_ADDR'];
            }
            $args->comment_srl = $comment_srl;
            $output = executeQuery('comment.getCommentVotedLogInfo', $args);

            // 로그 정보에 추천 로그가 있으면 세션 등록후 패스
            if($output->data->count) {
                $_SESSION['voted_comment'][$comment_srl] = true;
                return new Object(-1, 'failed_voted');
            }

            // 추천수 업데이트
            if($point < 0)
            {
                $args->blamed_count = $oComment->get('blamed_count') + $point;
                $output = executeQuery('comment.updateBlamedCount', $args);
            }
            else
            {
                $args->voted_count = $oComment->get('voted_count') + $point;
                $output = executeQuery('comment.updateVotedCount', $args);
            }

            // 로그 남기기
            $args->point = $point;
            $output = executeQuery('comment.insertCommentVotedLog', $args);

            // 세션 정보에 남김
            $_SESSION['voted_comment'][$comment_srl] = true;

            // 결과 리턴
            if($point > 0)
                return new Object(0, 'success_voted');
            else
                return new Object(0, 'success_blamed');
        }

        /**
         * @brief 댓글 신고
         **/
        function declaredComment($comment_srl) {
            // 세션 정보에 신고 정보가 있으면 중단
            if($_SESSION['declared_comment'][$comment_srl]) return new Object(-1, 'failed_declared');

            // 이미 신고되었는지 검사
            $args->comment_srl = $comment_srl;
            $output = executeQuery('comment.getDeclaredComment', $args);
            if(!$output->toBool()) return $output;

            // 문서 원본을 가져옴
            $oCommentModel = &getModel('comment');
            $oComment = $oCommentModel->getComment($comment_srl, false, false);

            // 글의 작성 ip와 현재 접속자의 ip가 동일하면 패스
            if($oComment->get('ipaddress') == $_SERVER['REMOTE_ADDR']) {
                $_SESSION['declared_comment'][$comment_srl] = true;
                return new Object(-1, 'failed_declared');
            }

            // comment의 작성자가 회원일때 조사
            if($oComment->get('member_srl')) {
                // member model 객체 생성
                $oMemberModel = &getModel('member');
                $member_srl = $oMemberModel->getLoggedMemberSrl();

                // 글쓴이와 현재 로그인 사용자의 정보가 일치하면 읽었다고 생각하고 세션 등록후 패스
                if($member_srl && $member_srl == $oComment->get('member_srl')) {
                    $_SESSION['declared_comment'][$comment_srl] = true;
                    return new Object(-1, 'failed_declared');
                }
            }

            // 로그인 사용자이면 member_srl, 비회원이면 ipaddress로 판단
            if($member_srl) {
                $args->member_srl = $member_srl;
            } else {
                $args->ipaddress = $_SERVER['REMOTE_ADDR'];
            }
            $args->comment_srl = $comment_srl;
            $output = executeQuery('comment.getCommentDeclaredLogInfo', $args);

            // 로그 정보에 신고 로그가 있으면 세션 등록후 패스
            if($output->data->count) {
                $_SESSION['declared_comment'][$comment_srl] = true;
                return new Object(-1, 'failed_declared');
            }

            // 신고글 추가
            if($output->data->declared_count > 0) $output = executeQuery('comment.updateDeclaredComment', $args);
            else $output = executeQuery('comment.insertDeclaredComment', $args);
            if(!$output->toBool()) return $output;

            // 로그 남기기
            $output = executeQuery('comment.insertCommentDeclaredLog', $args);

            // 세션 정보에 남김
            $_SESSION['declared_comment'][$comment_srl] = true;

            $this->setMessage('success_declared');
        }

        /**
         * @brief 댓글의 이 댓글을.. 클릭시 나타나는 팝업 메뉴를 추가하는 method
         **/
        function addCommentPopupMenu($url, $str, $icon = '', $target = 'self') {
            $comment_popup_menu_list = Context::get('comment_popup_menu_list');
            if(!is_array($comment_popup_menu_list)) $comment_popup_menu_list = array();

            $obj->url = $url;
            $obj->str = $str;
            $obj->icon = $icon;
            $obj->target = $target;
            $comment_popup_menu_list[] = $obj;

            Context::set('comment_popup_menu_list', $comment_popup_menu_list);
        }

        /**
         * @brief 댓글의 모듈별 추가 확장 폼을 저장
         **/
        function procCommentInsertModuleConfig() {
            $module_srl = Context::get('target_module_srl');
            if(preg_match('/^([0-9,]+)$/',$module_srl)) $module_srl = explode(',',$module_srl);
            else $module_srl = array($module_srl);

            $comment_config = null;
            $comment_config->comment_count = (int)Context::get('comment_count');
            if(!$comment_config->comment_count) $comment_config->comment_count = 50;

            $oModuleController = &getController('module');
            for($i=0;$i<count($module_srl);$i++) {
                $srl = trim($module_srl[$i]);
                if(!$srl) continue;
                $output = $oModuleController->insertModulePartConfig('comment',$srl,$comment_config);
            }

            $this->setError(-1);
            $this->setMessage('success_updated');
        }
    }
?>
