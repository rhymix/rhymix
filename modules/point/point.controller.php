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
            $this->setPoint($member_srl,$cur_point);

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

            // point 모듈 정보 가져옴
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');

            // 대상 회원의 포인트를 구함
            $oPointModel = &getModel('point');
            $cur_point = $oPointModel->getPoint($member_srl, true);

            $point = $config->module_point[$module_srl]['insert_document'];
            if(!isset($point)) $point = $config->insert_document;
            $cur_point += $point;

            // 첨부파일 등록에 대한 포인트 추가
            $point = $config->module_point[$module_srl]['upload_file'];
            if(!isset($point)) $point = $config->upload_file;
            if($obj->uploaded_count) $cur_point += $point * $obj->uploaded_count;

            // 포인트 증감
            $this->setPoint($member_srl,$cur_point);

            return new Object();
        }

        /**
         * @brief 게시글 삭제 포인트 적용 trigger
         **/
        function triggerDeleteDocument(&$obj) {
            $module_srl = $obj->module_srl;
            $member_srl = $obj->member_srl;
            if(!$module_srl || !$member_srl) return new Object();

            // 로그인 상태일때만 실행
            $logged_info = Context::get('logged_info');
            if(!$logged_info->member_srl) return new Object();

            // point 모듈 정보 가져옴
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');

            // 대상 회원의 포인트를 구함
            $oPointModel = &getModel('point');
            $cur_point = $oPointModel->getPoint($member_srl, true);

            $point = $config->module_point[$module_srl]['insert_document'];
            if(!isset($point)) $point = $config->insert_document;
            $cur_point -= $point;

            // 첨부파일 삭제에 대한 포인트 추가
            $point = $config->module_point[$module_srl]['upload_file'];
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

            // point 모듈 정보 가져옴
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');

            // 대상 회원의 포인트를 구함
            $oPointModel = &getModel('point');
            $cur_point = $oPointModel->getPoint($member_srl, true);

            $point = $config->module_point[$module_srl]['insert_comment'];
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
            $module_srl = $obj->module_srl;
            $member_srl = $obj->member_srl;
            if(!$module_srl || !$member_srl) return new Object();

            // point 모듈 정보 가져옴
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');

            // 대상 회원의 포인트를 구함
            $oPointModel = &getModel('point');
            $cur_point = $oPointModel->getPoint($member_srl, true);

            $point = $config->module_point[$module_srl]['insert_comment'];
            if(!isset($point)) $point = $config->insert_comment;

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
            $module_srl = $obj->module_srl;
            $member_srl = $obj->member_srl;
            if(!$module_srl || !$member_srl) return new Object();

            // point 모듈 정보 가져옴
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');

            // 대상 회원의 포인트를 구함
            $oPointModel = &getModel('point');
            $cur_point = $oPointModel->getPoint($member_srl, true);

            $point = $config->module_point[$module_srl]['upload_file'];
            if(!isset($point)) $point = $config->upload_file;

            // 포인트 증감
            $cur_point += $point;
            $this->setPoint($member_srl,$cur_point);

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

            // 대상 회원의 포인트를 구함
            $oPointModel = &getModel('point');
            $cur_point = $oPointModel->getPoint($member_srl, true);

            $point = $config->module_point[$module_srl]['upload_file'];
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
            // point 모듈 정보 가져옴
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');

            // 포인트가 없으면 다운로드가 안되도록 하였으면 비로그인 회원일 경우 중지
            if(!Context::get('is_logged') && $config->disable_download == 'Y') return new Object(-1,'msg_not_permitted_download');

            // 로그인 상태일때만 실행
            $logged_info = Context::get('logged_info');
            if(!$logged_info->member_srl) return new Object();

            $member_srl = $logged_info->member_srl;
            $module_srl = $obj->module_srl;
            if(!$module_srl) return new Object();

            // 대상 회원의 포인트를 구함
            $oPointModel = &getModel('point');
            $cur_point = $oPointModel->getPoint($member_srl, true);

            // 포인트를 구해옴
            $point = $config->module_point[$module_srl]['download_file'];
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

            // point 모듈 정보 가져옴
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');

            // 대상 회원의 포인트를 구함
            $oPointModel = &getModel('point');
            $cur_point = $oPointModel->getPoint($member_srl, true);

            // 포인트를 구해옴
            $point = $config->module_point[$module_srl]['download_file'];
            if(!isset($point)) $point = $config->download_file;

            // 포인트 증감
            $cur_point += $point;
            $this->setPoint($member_srl,$cur_point);

            return new Object();
        }

        /**
         * @brief 조회수 증가시 포인트 적용
         **/
        function triggerUpdateReadedCount(&$obj) {
            // 로그인 상태일때만 실행
            $logged_info = Context::get('logged_info');
            if(!$logged_info->member_srl) return new Object();

            // point 모듈 정보 가져옴
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');

            $member_srl = $logged_info->member_srl;
            $module_srl = $obj->get('module_srl');

            // 대상 회원의 포인트를 구함
            $oPointModel = &getModel('point');
            $cur_point = $oPointModel->getPoint($member_srl, true);

            // 포인트를 구해옴
            $point = $config->module_point[$obj->get('module_srl')]['read_document'];
            if(!isset($point)) $point = $config->read_document;
            
            // 포인트 증감
            $cur_point += $point;
            $this->setPoint($member_srl,$cur_point);

            return new Object();
        }

        /**
         * @brief 포인트 설정
         **/
        function setPoint($member_srl, $point) {
            if($point < 0) $point = 0;

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
            $args->point = $point;

            // 포인트가 있는지 체크
            $oPointModel = &getModel('point');
            if($oPointModel->isExistsPoint($member_srl)) {
                executeQuery("point.updatePoint", $args);
            } else {
                $args->point += (int)$config->signup_point;
                executeQuery("point.insertPoint", $args);
            }

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

                    // 포인트 그룹에 속한 그룹과 새로 부여 받을 그룹을 구함
                    $point_group_list = array();
                    $current_group_srl = 0;

                    asort($point_group);

                    // 포인트 그룹 설정을 돌면서 현재 레벨까지 체크
                    foreach($point_group as $group_srl => $target_level) {
                        $point_group_list[] = $group_srl;
                        if($target_level <= $level) {
                            $current_group_srl = $group_srl;
                        }
                    }
                    $point_group_list[] = $default_group->group_srl;

                    // 만약 새로운 그룹이 없다면 기본 그룹을 부여 받음
                    if(!$current_group_srl) $current_group_srl = $default_group->group_srl;

                    // 일단 기존의 그룹을 모두 삭제
                    $del_group_args->member_srl = $member_srl;
                    $del_group_args->group_srl = implode(',',$point_group_list);
                    $del_group_output = executeQuery('point.deleteMemberGroup', $del_group_args);

                    // 새로운 그룹을 부여
                    $new_group_args->member_srl = $member_srl;
                    $new_group_args->group_srl = $current_group_srl;
                    $new_group_output = executeQuery('member.addMemberToGroup', $new_group_args);

                    // 만약 대상 사용자와 로그인 사용자의 정보가 동일하다면 세션을 변경해줌
                    $logged_info = Context::get('logged_info');
                    if($logged_info->member_srl == $member_srl) {
                        $member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
                        $_SESSION['logged_info']->group_list = $member_info->group_list;
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

        /**
         * @brief 포인트 레벨 아이콘 표시
         **/
        function transLevelIcon($matches) {
            if(!$this->config) {
                $oModuleModel = &getModel('module');
                $this->config = $oModuleModel->getModuleConfig('point');
            }

            if(!$this->oPointModel) $this->oPointModel = &getModel('point');

            $member_srl = $matches[3];
            if($member_srl<1) return $matches[0];

            if($this->member_code[$member_srl]) return $this->member_code[$member_srl];

            $point = $this->oPointModel->getPoint($member_srl);
            $level = $this->oPointModel->getLevel($point, $this->config->level_step);

            $text = $matches[5];

            $src = sprintf("modules/point/icons/%s/%d.gif", $this->config->level_icon, $level);
            if(!$this->icon_width) {
                $info = getimagesize($src);
                $this->icon_width = $info[0];
                $this->icon_height = $info[1];
            }

            if($level < $this->config->max_level) {
                $next_point = $this->config->level_step[$level+1];
                if($next_point > 0) {
                    $per = (int)($point / $next_point*100);
                }
            }

            $title = sprintf("%s:%s%s %s, %s:%s/%s", Context::getLang('point'), $point, $this->config->point_name, $per?"(".$per."%)":"", Context::getLang('level'), $level, $this->config->max_level);

            $text = sprintf('<span class="nowrap member_%s" style="cursor:pointer"><img src="%s" width="%s" height="%s" alt="%s" title="%s" style="vertical-align:middle;margin-right:3px"/>%s</span>', $member_srl, Context::getRequestUri().$src, $this->icon_width, $this->icon_height, $title, $title, $text);

            $this->member_code[$member_srl] = $text;

            return $this->member_code[$member_srl];
        }
    }
?>
