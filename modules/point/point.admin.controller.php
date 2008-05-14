<?php
    /**
     * @class  pointAdminController
     * @author zero (zero@nzeo.com)
     * @brief  point모듈의 admin controller class
     **/

    class pointAdminController extends point {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 기본 설정 저장
         **/
        function procPointAdminInsertConfig() {
            // 설정 정보 가져오기
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');

            // 변수 정리
            $args = Context::getRequestVars();

            // 포인트 이름 체크
            $config->point_name = $args->point_name;
            if(!$config->point_name) $config->point_name = 'point';

            // 기본 포인트 지정
            $config->signup_point = (int)$args->signup_point;
            $config->login_point = (int)$args->login_point;
            $config->insert_document = (int)$args->insert_document;
            $config->read_document = (int)$args->read_document;
            $config->insert_comment = (int)$args->insert_comment;
            $config->upload_file = (int)$args->upload_file;
            $config->download_file = (int)$args->download_file;
            $config->voted = (int)$args->voted;
            $config->blamed = (int)$args->blamed;

            // 최고 레벨
            $config->max_level = $args->max_level;
            if($config->max_level>1000) $config->max_level = 1000;
            if($config->max_level<1) $config->max_level = 1;

            // 레벨 아이콘 설정
            $config->level_icon = $args->level_icon;

            // 포인트 미달시 다운로드 금지 여부 체크
            if($args->disable_download == 'Y') $config->disable_download = 'Y';
            else $config->disable_download = 'N';

            // 레벨별 그룹 설정
            foreach($args as $key => $val) {
                if(substr($key, 0, strlen('point_group_')) != 'point_group_') continue;
                $group_srl = substr($key, strlen('point_group_'));
                $level = $val;
                if(!$level) unset($config->point_group[$group_srl]);
                else $config->point_group[$group_srl] = $level;
            }

            // 레벨별 포인트 설정
            unset($config->level_step);
            for($i=1;$i<=$config->max_level;$i++) {
                $key = "level_step_".$i;
                $config->level_step[$i] = (int)$args->{$key};
            }

            // 레벨별 포인트 계산 함수
            $config->expression = $args->expression;

            // 저장
            $oModuleController = &getController('module');
            $oModuleController->insertModuleConfig('point', $config);

            $this->cacheActList();

            $this->setMessage('success_updated');
        }

        /**
         * @brief 모듈별 설정 저장
         **/
        function procPointAdminInsertModuleConfig() {
            // 설정 정보 가져오기
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');

            // 변수 정리
            $args = Context::getRequestVars();

            foreach($args as $key => $val) {
                preg_match("/^(insert_document|insert_comment|upload_file|download_file|read_document|voted|blamed)_([0-9]+)$/", $key, $matches);
                if(!$matches[1]) continue;
                $name = $matches[1];
                $module_srl = $matches[2];
                if(strlen($val)==0) unset($config->module_point[$module_srl][$name]);
                else $config->module_point[$module_srl][$name] = (int)$val;
            }

            // 저장
            $oModuleController = &getController('module');
            $oModuleController->insertModuleConfig('point', $config);

            $this->cacheActList();

            $this->setMessage('success_updated');
        }

        /**
         * @brief 모듈별 개별 포인트 저장
         **/
        function procPointAdminInsertPointModuleConfig() {
            $module_srl = Context::get('target_module_srl');
            if(!$module_srl) return new Object(-1, 'msg_invalid_request');

            // 여러개의 모듈 일괄 설정일 경우
            if(preg_match('/^([0-9,]+)$/',$module_srl)) $module_srl = explode(',',$module_srl);
            else $module_srl = array($module_srl);

            // 설정 정보 가져오기
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');

            // 설정 저장
            for($i=0;$i<count($module_srl);$i++) {
                $srl = trim($module_srl[$i]);
                if(!$srl) continue;
                $config->module_point[$srl]['insert_document'] = (int)Context::get('insert_document');
                $config->module_point[$srl]['insert_comment'] = (int)Context::get('insert_comment');
                $config->module_point[$srl]['upload_file'] = (int)Context::get('upload_file');
                $config->module_point[$srl]['download_file'] = (int)Context::get('download_file');
                $config->module_point[$srl]['read_document'] = (int)Context::get('read_document');
                $config->module_point[$srl]['voted'] = (int)Context::get('voted');
                $config->module_point[$srl]['blamed'] = (int)Context::get('blamed');
            }

            $oModuleController = &getController('module');
            $oModuleController->insertModuleConfig('point', $config);

            $this->setError(-1);
            $this->setMessage('success_updated');
        }

        /**
         * @brief 기능별 act 저장
         **/
        function procPointAdminInsertActConfig() {
            // 설정 정보 가져오기
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');

            // 변수 정리
            $args = Context::getRequestVars();

            $config->insert_document_act = $args->insert_document_act;
            $config->delete_document_act = $args->delete_document_act;
            $config->insert_comment_act = $args->insert_comment_act;
            $config->delete_comment_act = $args->delete_comment_act;
            $config->upload_file_act = $args->upload_file_act;
            $config->delete_file_act = $args->delete_file_act;
            $config->download_file_act = $args->download_file_act;

            // 저장
            $oModuleController = &getController('module');
            $oModuleController->insertModuleConfig('point', $config);

            $this->cacheActList();

            $this->setMessage('success_updated');
        }

        /**
         * @brief 회원 포인트 변경
         **/
        function procPointAdminUpdatePoint() {
            $member_srl = Context::get('member_srl');
            $point = Context::get('point');

            $oPointController = &getController('point');
            return $oPointController->setPoint($member_srl, (int)$point);
        }

        /**
         * @brief 전체글/ 댓글/ 첨부파일과 가입정보를 바탕으로 포인트를 재계산함. 단 로그인 점수는 1번만 부여됨
         **/
        function procPointAdminReCal() {
            set_time_limit(0);

            // 모듈별 포인트 정보를 가져옴
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');

            // 회원의 포인트 저장을 위한 변수
            $member = array();
            
            // 게시글 정보를 가져옴
            $output = executeQueryArray('point.getDocumentPoint');
            if(!$output->toBool()) return $output;

            if($output->data) {
                foreach($output->data as $key => $val) {
                    if($config->module_point[$val->module_srl]['insert_document']) $insert_point = $config->module_point[$val->module_srl]['insert_document'];
                    else $insert_point = $config->insert_document;

                    if(!$val->member_srl) continue;
                    $point = $insert_point * $val->count;
                    $member[$val->member_srl] += $point;
                }
            }
            $output = null;

            // 댓글 정보를 가져옴
            $output = executeQueryArray('point.getCommentPoint');
            if(!$output->toBool()) return $output;

            if($output->data) {
                foreach($output->data as $key => $val) {
                    if($config->module_point[$val->module_srl]['insert_comment']) $insert_point = $config->module_point[$val->module_srl]['insert_comment'];
                    else $insert_point = $config->insert_comment;

                    if(!$val->member_srl) continue;
                    $point = $insert_point * $val->count;
                    $member[$val->member_srl] += $point;
                }
            }
            $output = null;

            // 첨부파일 정보를 가져옴
            $output = executeQueryArray('point.getFilePoint');
            if(!$output->toBool()) return $output;

            if($output->data) {
                foreach($output->data as $key => $val) {
                    if($config->module_point[$val->module_srl]['upload_file']) $insert_point = $config->module_point[$val->module_srl]['upload_file'];
                    else $insert_point = $config->upload_file;

                    if(!$val->member_srl) continue;
                    $point = $insert_point * $val->count;
                    $member[$val->member_srl] += $point;
                }
            }
            $output = null;

            // 모든 회원의 포인트를 0으로 세팅
            $output = executeQuery("point.initMemberPoint");
            if(!$output->toBool()) return $output;

            // 임시로 파일 저장
            $f = fopen("./files/cache/pointRecal.txt","w");
            foreach($member as $key => $val) {
                $val += (int)$config->signup_point;
                fwrite($f, $key.','.$val."\r\n");
            }
            fclose($f);

            $this->add('total', count($member));
            $this->add('position', 0);
            $this->setMessage( sprintf(Context::getLang('point_recal_message'), 0, $this->get('total')) );
        }

        /**
         * @brief 파일로 저장한 회원 포인트를 5000명 단위로 적용
         **/
        function procPointAdminApplyPoint() {
            $position = (int)Context::get('position');
            $total = (int)Context::get('total');

            if(!file_exists('./files/cache/pointRecal.txt')) return new Object(-1, 'msg_invalid_request');

            $idx = 0;
            $f = fopen("./files/cache/pointRecal.txt","r");
            while(!feof($f)) {
                $str = trim(fgets($f, 1024));
                $idx ++;
                if($idx > $position) {
                    list($member_srl, $point) = explode(',',$str);

                    $args = null;
                    $args->member_srl = $member_srl;
                    $args->point = $point;
                    $output = executeQuery('point.insertPoint',$args);
                    if($idx%5000==0) break;
                }
            }

            if(feof($f)) {
                @unlink('./files/cache/pointRecal.txt');
                $idx = $total;

                @rename('./files/member_extra_info/point','./files/member_extra_info/point.old');
                FileHandler::removeDir('./files/member_extra_info/point.old');
            }
            fclose($f);


            $this->add('total', $total);
            $this->add('position', $idx);
            $this->setMessage(sprintf(Context::getLang('point_recal_message'), $idx, $total));

        }

        /**
         * @brief 캐시파일 저장
         **/
        function cacheActList() {
            // 설정 정보 가져오기
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');

            // 각 act값을 정리
            $act_list = sprintf("%s,%s,%s,%s,%s,%s,%s",
                    $config->insert_document_act,
                    $config->delete_document_act,
                    $config->insert_comment_act,
                    $config->delete_comment_act,
                    $config->upload_file_act,
                    $config->delete_file_act,
                    $config->download_file_act
            );

            $act_cache_file = "./files/cache/point.act.cache";
            FileHandler::writeFile($act_cache_file, $act_list);
        }

    }
?>
