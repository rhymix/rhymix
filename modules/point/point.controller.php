<?php
    /**
     * @class  pointController
     * @author zero (zero@nzeo.com)
     * @brief  point모듈의 Controller class
     **/

    class pointController extends point {

        var $config = null;
        var $oPointModel = null;
        var $member_code = array();
        var $icon_width = 0;
        var $icon_height = 0;

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 회원가입 포인트 적용 trigger
         **/
        function triggerInsertMember(&$obj) {
            // point 모듈 정보 가져옴
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');

            // 가입한 회원의 member_srl을 구함
            $member_srl = $obj->member_srl;

            // 대상 회원의 포인트를 구함
            $oPointModel = &getModel('point');
            $cur_point = $oPointModel->getPoint($member_srl, true);

            $point = $config->signup_point;

            // 포인트 증감
            $cur_point += $point;
            $this->setPoint($member_srl,$cur_point, 'signup');

            return new Object();
        }

        /**
         * @brief 회원 로그인 포인트 적용 trigger
         **/
        function triggerAfterLogin(&$obj) {
            $member_srl = $obj->member_srl;
            if(!$member_srl) return new Object();

            // 바로 이전 로그인이 오늘이 아니어야 포인트를 줌
            if(substr($obj->last_login,0,8)==date("Ymd")) return new Object();

            // point 모듈 정보 가져옴
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');

            // 대상 회원의 포인트를 구함
            $oPointModel = &getModel('point');
            $cur_point = $oPointModel->getPoint($member_srl, true);

            $point = $config->login_point;

            // 포인트 증감
            $cur_point += $point;
            $this->setPoint($member_srl,$cur_point);

            return new Object();
        }

        /**
         * @brief 게시글 등록 포인트 적용 trigger
         **/
        function triggerInsertDocument(&$obj) {
            $module_srl = $obj->module_srl;
            $member_srl = $obj->member_srl;
            if(!$module_srl || !$member_srl) return new Object();

            // 임시저장일 경우 포인트를 지급하지 않도록 수정
            if($module_srl == $member_srl) return new Object();

            // point 모듈 정보 가져옴
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');
            $module_config = $oModuleModel->getModulePartConfig('point',$module_srl);

            // 대상 회원의 포인트를 구함
            $oPointModel = &getModel('point');
            $cur_point = $oPointModel->getPoint($member_srl, true);

            $point = $module_config['insert_document'];
            if(!isset($point)) $point = $config->insert_document;
            $cur_point += $point;

            // 첨부파일 등록에 대한 포인트 추가
            $point = $module_config['upload_file'];
            if(!isset($point)) $point = $config->upload_file;
            if($obj->uploaded_count) $cur_point += $point * $obj->uploaded_count;

            // 포인트 증감
            $this->setPoint($member_srl,$cur_point);

            return new Object();
        }

        /**
         * @brief 임시저장된 글을 정상 등록시 포인트 적용하는 trigger
         * 1.2.3 에서 임시저장시에 포인트 지급되지 않도록 변경됨
         **/
        function triggerUpdateDocument(&$obj) {
            $oDocumentModel = &getModel('document');
            $oModuleModel = &getModel('module');

            $document_srl = $obj->document_srl;
            $oDocument = $oDocumentModel->getDocument($document_srl);

            $module_srl = $oDocument->get('module_srl');
            $member_srl = $oDocument->get('member_srl');
            if(!$module_srl || !$member_srl) return new Object();

            // 임시저장된 것이 아니면 return
            if($module_srl != $member_srl) return new Object();

            // point 모듈 정보 가져옴
            $config = $oModuleModel->getModuleConfig('point');
            $module_config = $oModuleModel->getModulePartConfig('point',$obj->module_srl);

            // 대상 회원의 포인트를 구함
            $oPointModel = &getModel('point');
            $cur_point = $oPointModel->getPoint($member_srl, true);

            $point = $module_config['insert_document'];
            if(!isset($point)) $point = $config->insert_document;
            $cur_point += $point;

            // 첨부파일 등록에 대한 포인트 추가
            $point = $module_config['upload_file'];
            if(!isset($point)) $point = $config->upload_file;
            if($obj->uploaded_count) $cur_point += $point * $obj->uploaded_count;

            // 포인트 증감
            $this->setPoint($member_srl,$cur_point);

            return new Object();

        }

        /**
         * @brief 게시글 삭제 이전에 게시글의 댓글에 대한 포인트 감소 처리를 하는 trigger
         **/
        function triggerBeforeDeleteDocument(&$obj) {
            $document_srl = $obj->document_srl;
            $member_srl = $obj->member_srl;

            $oDocumentModel = &getModel('document');
            $oDocument = $oDocumentModel->getDocument($document_srl);
            if(!$oDocument->isExists()) return new Object();

            // point 모듈 정보 가져옴
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');
            $module_config = $oModuleModel->getModulePartConfig('point',$oDocument->get('module_srl'));

            // 지울 대상 글의 댓글에 대한 처리
            $comment_point = $module_config['insert_comment'];
            if(!isset($comment_point)) $comment_point = $config->insert_comment;

            // 댓글 포인트가 있으면 증감(+) 이면 차감 시도
            if($comment_point>0) return new Object();

            // 해당 글에 포함된 모든 댓글을 추출
            $cp_args->document_srl = $document_srl;
            $output = executeQueryArray('point.getCommentUsers', $cp_args);

            // 대상이 없으면 return
            if(!$output->data) return new Object();

            // 대상 회원 번호를 정리
            $member_srls = array();
            $cnt = count($output->data);
            for($i=0;$i<$cnt;$i++) {
                if($output->data[$i]->member_srl<1) continue;
                $member_srls[abs($output->data[$i]->member_srl)] = $output->data[$i]->count;
            }

            // 원글 작성 회원의 번호는 제거
            if($member_srl) unset($member_srls[abs($member_srl)]);
            if(!count($member_srls)) return new Object();

            // 각 회원들을 모두 돌면서 포인트 감소
            $oPointModel = &getModel('point');

            // 포인트를 구해옴
            $point = $module_config['download_file'];
            foreach($member_srls as $member_srl => $cnt) {
                $cur_point = $oPointModel->getPoint($member_srl, true);
                $cur_point -= $cnt * $comment_point;
                $this->setPoint($member_srl,$cur_point);
            }

            return new Object();
        }

        /**
         * @brief 게시글 삭제 포인트 적용 trigger
         **/
        function triggerDeleteDocument(&$obj) {
            $module_srl = $obj->module_srl;
            $member_srl = $obj->member_srl;

            // 지울 대상 글에 대한 처리
            if(!$module_srl || !$member_srl) return new Object();

            // 로그인 상태일때만 실행
            $logged_info = Context::get('logged_info');
            if(!$logged_info->member_srl) return new Object();

            // 대상 회원의 포인트를 구함
            $oPointModel = &getModel('point');
            $cur_point = $oPointModel->getPoint($member_srl, true);

            // point 모듈 정보 가져옴
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');
            $module_config = $oModuleModel->getModulePartConfig('point', $module_srl);

            $point = $module_config['insert_document'];
            if(!isset($point)) $point = $config->insert_document;

            // 포인트가 마이너스 즉 글을 작성시 마다 차감되는 경우라면 글 삭제시 증가시켜주지 않도록 수정
            if($point < 0) return new Object();
            $cur_point -= $point;

            // 첨부파일 삭제에 대한 포인트 추가
            $point = $module_config['upload_file'];
            if(!isset($point)) $point = $config->upload_file;
            if($obj->uploaded_count) $cur_point -= $point * $obj->uploaded_count;

            // 포인트 증감
            $this->setPoint($member_srl,$cur_point);

            return new Object();
        }

        /**
         * @brief 댓글 등록 포인트 적용 trigger
         **/
        function triggerInsertComment(&$obj) {
            $module_srl = $obj->module_srl;
            $member_srl = $obj->member_srl;
            if(!$module_srl || !$member_srl) return new Object();

            // 원글이 본인의 글이라면 포인트를 올리지 않음
            $document_srl = $obj->document_srl;
            $oDocumentModel = &getModel('document');
            $oDocument = $oDocumentModel->getDocument($document_srl);
            if(!$oDocument->isExists() || abs($oDocument->get('member_srl'))==abs($member_srl)) return new Object();

            // point 모듈 정보 가져옴
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');
            $module_config = $oModuleModel->getModulePartConfig('point', $module_srl);

            // 대상 회원의 포인트를 구함
            $oPointModel = &getModel('point');
            $cur_point = $oPointModel->getPoint($member_srl, true);

            $point = $module_config['insert_comment'];
            if(!isset($point)) $point = $config->insert_comment;

            // 포인트 증감
            $cur_point += $point;
            $this->setPoint($member_srl,$cur_point);

            return new Object();
        }

        /**
         * @brief 댓글 삭제 포인트 적용 trigger
         **/
        function triggerDeleteComment(&$obj) {
            $oModuleModel = &getModel('module');
            $oPointModel = &getModel('point');
            $oDocumentModel = &getModel('document');

            $module_srl = $obj->module_srl;
            $member_srl = abs($obj->member_srl);
            $document_srl = $obj->document_srl;
            if(!$module_srl || !$member_srl) return new Object();

            // 원글을 가져옴 (원글이 없거나 원글이 자신의 글이라면 포인트 적용 하지 않음)
            $oDocument = $oDocumentModel->getDocument($document_srl);
            if(!$oDocument->isExists()) return new Object();
            if($oDocument->get('member_srl')==$member_srl) return new Object();

            // point 모듈 정보 가져옴
            $config = $oModuleModel->getModuleConfig('point');
            $module_config = $oModuleModel->getModulePartConfig('point', $module_srl);

            // 대상 회원의 포인트를 구함
            $cur_point = $oPointModel->getPoint($member_srl, true);

            $point = $module_config['insert_comment'];
            if(!isset($point)) $point = $config->insert_comment;

            // 포인트가 마이너스 즉 댓글을 작성시 마다 차감되는 경우라면 댓글 삭제시 증가시켜주지 않도록 수정
            if($point < 0) return new Object();

            // 포인트 증감
            $cur_point -= $point;
            $this->setPoint($member_srl,$cur_point);

            return new Object();
        }

        /**
         * @brief 파일 등록 trigger 추가
         * 비유효 파일의 등록에 의한 포인트 획득을 방지하고자 이 method는 일단 무효로 둠
         **/
        function triggerInsertFile(&$obj) {
            return new Object();
        }

        /**
         * @brief 파일 삭제 포인트 적용 trigger
         * 유효파일을 삭제할 경우에만 포인트 삭제
         **/
        function triggerDeleteFile(&$obj) {
            if($obj->isvalid != 'Y') return new Object();

            $module_srl = $obj->module_srl;
            $member_srl = $obj->member_srl;
            if(!$module_srl || !$member_srl) return new Object();

            // point 모듈 정보 가져옴
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');
            $module_config = $oModuleModel->getModulePartConfig('point', $module_srl);

            // 대상 회원의 포인트를 구함
            $oPointModel = &getModel('point');
            $cur_point = $oPointModel->getPoint($member_srl, true);

            $point = $module_config['upload_file'];
            if(!isset($point)) $point = $config->upload_file;

            // 포인트 증감
            $cur_point -= $point;
            $this->setPoint($member_srl,$cur_point);

            return new Object();
        }

        /**
         * @brief 파일 다운로드 전에 호출되는 trigger
         **/
        function triggerBeforeDownloadFile(&$obj) {
            $logged_info = Context::get('logged_info');
            $member_srl = $logged_info->member_srl;
            $module_srl = $obj->module_srl;
            if(!$module_srl) return new Object();

            // 자신의 올린 파일이면 패스
            if(abs($obj->member_srl) == abs($member_srl)) return new Object();

            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');
            $module_config = $oModuleModel->getModulePartConfig('point', $module_srl);

            // 포인트가 없으면 다운로드가 안되도록 하였으면 비로그인 회원일 경우 중지
            if(!Context::get('is_logged'))
			{
				if($config->disable_download == 'Y') return new Object(-1,'msg_not_permitted_download');
				else return new Object();
			}

            // 대상 회원의 포인트를 구함
            $oPointModel = &getModel('point');
            $cur_point = $oPointModel->getPoint($member_srl, true);

            // 포인트를 구해옴
            $point = $module_config['download_file'];
            if(!isset($point)) $point = $config->download_file;

            // 포인트가 0보다 작고 포인트가 없으면 파일 다운로드가 안되도록 했다면 오류
            if($cur_point + $point < 0 && $config->disable_download == 'Y') return new Object(-1,'msg_not_permitted_download');

            return new Object();
        }

        /**
         * @brief 파일 다운로드 포인트 적용 trigger
         **/
        function triggerDownloadFile(&$obj) {
            // 로그인 상태일때만 실행
            $logged_info = Context::get('logged_info');
            if(!$logged_info->member_srl) return new Object();
            $module_srl = $obj->module_srl;
            $member_srl = $logged_info->member_srl;
            if(!$module_srl) return new Object();

            // 자신의 올린 파일이면 패스
            if(abs($obj->member_srl) == abs($member_srl)) return new Object();

            // point 모듈 정보 가져옴
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');
            $module_config = $oModuleModel->getModulePartConfig('point', $module_srl);

            // 대상 회원의 포인트를 구함
            $oPointModel = &getModel('point');
            $cur_point = $oPointModel->getPoint($member_srl, true);

            // 포인트를 구해옴
            $point = $module_config['download_file'];
            if(!isset($point)) $point = $config->download_file;

            // 포인트 증감
            $cur_point += $point;
            $this->setPoint($member_srl,$cur_point);

            return new Object();
        }

        /**
         * @brief 조회수 증가시 포인트 적용
         * 포인트가 없을때 조회를 하지 못하는 기능의 적용도 실행
         **/
        function triggerUpdateReadedCount(&$obj) {
            $oModuleModel = &getModel('module');
            $oPointModel = &getModel('point');

            // 접속자의 정보 구함
            $logged_info = Context::get('logged_info');
            $member_srl = $logged_info->member_srl;

            // 원글의 회원 번호 구함
            $target_member_srl = abs($obj->get('member_srl'));

            // 원글을 적은 이와 동일하면 조회수 올리지 않고 pass
            if($target_member_srl == $member_srl) return new Object();

            // 모듈별 point 정보 가져옴
            $config = $oModuleModel->getModuleConfig('point');
            $module_config = $oModuleModel->getModulePartConfig('point', $obj->get('module_srl'));

            // 조회 포인트를 구해옴
            $point = $module_config['read_document'];
            if(!isset($point)) $point = $config->read_document;

            // 조회 포인트가 없으면 pass
            if(!$point) return new Object();

            // 회원일 경우 읽은 적이 있으면 그냥 pass 하고 그렇지 않으면 현재 포인트 구함
            if($member_srl) {
                $args->member_srl = $member_srl;
                $args->document_srl = $obj->document_srl;
                $output = executeQuery('document.getDocumentReadedLogInfo', $args);
                if($output->data->count) return new Object();
                $cur_point = $oPointModel->getPoint($member_srl, true);
            } else {
                $cur_point = 0;
            }

            // 포인트 모듈 기본 설정 가져옴
            $config = $oModuleModel->getModuleConfig('point');

            // 조회 포인트가 -(마이너스)일때 현재 포인트와 비교
            if($config->disable_read_document == 'Y' && $point < 0 && abs($point)>$cur_point) {
                $obj->add('content', sprintf(Context::getLang('msg_disallow_by_point'), abs($point), $cur_point));
                return new Object();
            }

            // 로그인 사용자가 아니라면 pass
            if(!$logged_info->member_srl) return new Object();

            // 조회 포인트가 없으면 패스
            if(!$point) return new Object();

            // 읽은 기록이 없으면 기록 남김
            $output = executeQuery('document.insertDocumentReadedLog', $args);

            // 포인트 증감
            $cur_point += $point;
            $this->setPoint($member_srl,$cur_point);

            return new Object();
        }

        /**
         * @brief 추천/비추천 시 포인트 적용
         **/

        function triggerUpdateVotedCount(&$obj) {
            $module_srl = $obj->module_srl;
            $member_srl = $obj->member_srl;
            if(!$module_srl || !$member_srl) return new Object();

            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');
            $module_config = $oModuleModel->getModulePartConfig('point', $module_srl);

            $oPointModel = &getModel('point');
            $cur_point = $oPointModel->getPoint($member_srl, true);

            if( $obj->point > 0 ) {
                $point = $module_config['voted'];
                if(!isset($point)) $point = $config->voted;
            } else {
                $point = $module_config['blamed'];
                if(!isset($point)) $point = $config->blamed;
            }

            if(!$point) return new Object();

            // 포인트 증감
            $cur_point += $point;
            $this->setPoint($member_srl,$cur_point);

            return new Object();
        }

        /**
         * @brief 포인트 설정
         **/
        function setPoint($member_srl, $point, $mode = null) {
            $member_srl = abs($member_srl);
            $mode_arr = array('add', 'minus', 'update', 'signup');
            if(!$mode || !in_array($mode,$mode_arr)) $mode = 'update';

            // 설정 정보 가져오기
            $oMemberModel = &getModel('member');
            $oModuleModel = &getModel('module');
            $oPointModel = &getModel('point');
            $config = $oModuleModel->getModuleConfig('point');

            // 기존 포인트 정보를 구함
            $prev_point = $oPointModel->getPoint($member_srl, true);
            $prev_level = $oPointModel->getLevel($prev_point, $config->level_step);

            // 포인트 변경
            $args->member_srl = $member_srl;
            $args->point = $prev_point;

            switch($mode) {

                case 'add' :
                        $args->point += $point;
                    break;
                case 'minus' :
                        $args->point -= $point;
                    break;
                case 'update' :
                        $args->point = $point;
                    break;
                case 'signup' :
                        $args->point = $point;
                    break;
            }
            if($args->point < 0) $args->point = 0;
            $point = $args->point;

            // 포인트가 있으면 update, 없으면 insert
            $oPointModel = &getModel('point');
            if($oPointModel->isExistsPoint($member_srl)) executeQuery("point.updatePoint", $args);
            else executeQuery("point.insertPoint", $args);

            // 새로운 레벨을 구함
            $level = $oPointModel->getLevel($point, $config->level_step);

            // 기존 레벨과 새로운 레벨이 다르면 포인트 그룹 설정 시도
            if($level != $prev_level) {

                // 현재 포인트 대비하여 레벨을 계산하고 레벨에 맞는 그룹 설정을 체크
                $point_group = $config->point_group;

                // 포인트 그룹 정보가 있을때 시행
                if($point_group && is_array($point_group) && count($point_group) ) {

                    // 기본 그룹을 구함
                    $default_group = $oMemberModel->getDefaultGroup();

                    // 제거될 그룹과 새로 부여 받을 그룹을 구함
                    $del_group_list = array();
                    $new_group_srls = array();

                    asort($point_group);

					//그룹 초기화 후 재설정
					if($config->group_reset != 'N') {
						//새로운 레벨에 맞는 그룹이 있다면
						if(in_array($level, $point_group)) {
							//현재 레벨의 그룹을 제외한 나머지는 모두 삭제
							foreach($point_group as $group_srl => $target_level) {
								$del_group_list[] = $group_srl;
								if($target_level == $level) $new_group_srls[] = $group_srl;
							}
						}
						//그렇지 않고 레벨 감소인 경우 바로 이전 그룹을 추가
						else {
							$i = $level;
							while($i > 0) {
								if(in_array($i, $point_group)) {
									foreach($point_group as $group_srl => $target_level) {
										if ($target_level == $i) {
											$new_group_srls[] = $group_srl;
										}
									}
									$i = 0;
								}
								
								$i--;
							}
						}

						//현재 레벨보다 높은 레벨의 그룹은 삭제
						foreach($point_group as $group_srl => $target_level) {
							if($target_level	 > $level) $del_group_list[] = $group_srl;
						}
					}
					//새 그룹만 부여
					else {			
						// 포인트 그룹 설정을 돌면서 현재 레벨까지 체크
						foreach($point_group as $group_srl => $target_level) {
							$del_group_list[] = $group_srl;
							if($target_level <= $level) $new_group_srls[] = $group_srl;
						}
					}

                    // 만약 새로운 그룹이 없다면 기본 그룹을 부여 받음
                    if(!$new_group_srls[0]) $new_group_srls[0] = $default_group->group_srl;

                    // 연동 그룹 제거
                    if($del_group_list && count($del_group_list)) {
                        $del_group_args->member_srl = $member_srl;
                        $del_group_args->group_srl = implode(',', $del_group_list);
                        $del_group_output = executeQuery('point.deleteMemberGroup', $del_group_args);
                    }

                    // 새로운 그룹을 부여
                    foreach($new_group_srls as $group_srl) {
                        $new_group_args->member_srl = $member_srl;
                        $new_group_args->group_srl = $group_srl;
                        executeQuery('member.addMemberToGroup', $new_group_args);
                    }
                }
            }

            // 캐시 설정
            $cache_path = sprintf('./files/member_extra_info/point/%s/', getNumberingPath($member_srl));
            FileHandler::makedir($cache_path);

            $cache_filename = sprintf('%s%d.cache.txt', $cache_path, $member_srl);
            FileHandler::writeFile($cache_filename, $point);

            return $output;
        }
    }
?>
