<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file point.addon.php
     * @author zero (zero@nzeo.com)
     * @brief 포인트 애드온
     *
     * 포인트 시스템 모듈에 설정된 내용을 토대로 하여 포인트를 부여/차감하고,
     * 다운로드를 금지시키고,
     * 회원 이름 앞에 레벨 아이콘을 표시한다.
     **/

    // 관리자 모듈이면 패스~
    if(Context::get('module')=='admin') return;

    // 로그인 상태일때만 실행
    $logged_info = Context::get('logged_info');
    if(!$logged_info->member_srl) return;

    // point action cache file을 가져와서 현재 속한 캐시파일인지 확인
    $act_cache_file = "./files/cache/point.act.cache";
    $buff = FileHandler::readFile($act_cache_file);
    if(strpos($buff,$this->act)===false) return;

    // point 모듈 정보 가져옴
    $oModuleModel = &getModel('module');
    $config = $oModuleModel->getModuleConfig('point');

    // 현재 로그인 사용자의 포인트를 가져옴
    $member_srl = $logged_info->member_srl;

    $oPointModel = &getModel('point');
    $cur_point = $oPointModel->getPoint($member_srl, true);

    // 파일다운로드를 제외한 action은 called_position가 before_module_proc일때 실행
    if($called_position == 'after_module_proc') {

        // 게시글 작성
        if(strpos($config->insert_document_act,$this->act)!==false) {
            if(!$this->toBool()) return;
            $document_srl = $this->get('document_srl');

            $oDocumentModel = &getModel('document');
            $oDocument = $oDocumentModel->getDocument($document_srl);

            // 신규 글인지 체크
            if($oDocument->get('regdate')!=$oDocument->get('last_update')) return;
            $module_srl = $oDocument->get('module_srl');

            // 포인트를 구해옴
            $point = $config->module_point[$module_srl]['insert_document'];
            if($point == null) $point = $config->insert_document;

            // 포인트 증감
            $cur_point += $point;
            $oPointController = &getController('point');
            $oPointController->setPoint($member_srl,$cur_point);

        // 게시글 삭제
        } elseif(strpos($config->delete_document_act,$this->act)!==false) {
            if(!$this->toBool()) return;
            $target_member_srl = Context::get('_point_target_member_srl');
            if(!$target_member_srl) return;

            $module_srl = $this->module_srl;
            
            // 포인트를 구해옴
            $point = $config->module_point[$module_srl]['insert_document'];
            if($point == null) $point = $config->insert_document;

            // 포인트 차감
            $cur_point = $oPointModel->getPoint($target_member_srl, true);
            $cur_point -= $point;

            $oPointController = &getController('point');
            $oPointController->setPoint($target_member_srl,$cur_point);

        // 댓글 작성
        } elseif(strpos($config->insert_comment_act,$this->act)!==false) {
            $comment_srl = $this->get('comment_srl');
            $oCommentModel = &getModel('comment');
            $comment = $oCommentModel->getComment($comment_srl);

            // 이미 존재하는 댓글인지 체크
            if($comment->last_update) return;

            // 포인트를 구해옴
            $module_srl = $comment->module_srl;

            // 포인트를 구해옴
            $point = $config->module_point[$module_srl]['insert_comment'];
            if($point == null) $point = $config->insert_comment;

            // 포인트 증감
            $cur_point += $point;
            $oPointController = &getController('point');
            $oPointController->setPoint($member_srl,$cur_point);


        // 댓글 삭제
        } elseif(strpos($config->delete_comment_act,$this->act)!==false) {
            if(!$this->toBool()) return;
            $target_member_srl = Context::get('_point_target_member_srl');
            if(!$target_member_srl) return;

            // 포인트를 구해옴
            $module_srl = $this->module_srl;

            // 포인트를 구해옴
            $point = $config->module_point[$module_srl]['insert_comment'];
            if($point == null) $point = $config->insert_comment;

            // 포인트 증감
            $cur_point = $oPointModel->getPoint($target_member_srl, true);
            $cur_point -= $point;

            $oPointController = &getController('point');
            $oPointController->setPoint($target_member_srl,$cur_point);

        // 파일업로드
        } elseif(strpos($config->upload_file_act,$this->act)!==false) {
            if(!$output->toBool()||!$output->get('file_srl')) return;
            $file_srl = $output->get('file_srl');

            $oFileModel = &getModel('file');
            $file_info = $oFileModel->getFile($file_srl);

            $module_srl = $this->module_srl;

            // 포인트를 구해옴
            $point = $config->module_point[$module_srl]['upload_file'];
            if($point == null) $point = $config->upload_file;

            // 포인트 증감
            $cur_point += $point;
            $oPointController = &getController('point');
            $oPointController->setPoint($member_srl,$cur_point);

        // 파일삭제
        } elseif(strpos($config->delete_file_act,$this->act)!==false) {
            // 파일 정보를 구해옴
            $file_srl = Context::get('file_srl');
            if(!$file_srl) return;
            $target_member_srl = Context::get('_point_target_member_srl');
            if(!$target_member_srl) return;

            $module_srl = $this->module_srl;

            $target_member_srl = Context::get('_point_target_member_srl');
            if(!$target_member_srl) return;

            // 포인트를 구해옴
            $point = $config->module_point[$module_srl]['upload_file'];
            if($point == null) $point = $config->upload_file;

            // 포인트 차감
            $cur_point = $oPointModel->getPoint($target_member_srl, true);
            $cur_point -= $point;
            $oPointController = &getController('point');
            $oPointController->setPoint($target_member_srl,$cur_point);

        // 회원 가입일 경우
        } elseif(strpos($config->signup_act,$this->act)!==false) {
            // 가입이 제대로 되었는지 체크
            if(!$this->toBool()||!$this->get('member_srl')) return;
            $member_srl = $this->get('member_srl');

            // 포인트를 구해옴
            $point = $config->module_point[$module_srl]['signup'];
            if($point == null) $point = $config->signup;

            // 포인트 증감
            $cur_point += $point;
            $oPointController = &getController('point');
            $oPointController->setPoint($member_srl,$cur_point);
        }

    // 파일다운로드는 before_module_proc 일때 체크
    } else if($called_position == "before_module_proc") {

        // 파일다운로드
        if(strpos($config->download_file_act,$this->act)!==false) {
            // 파일 정보를 구해옴
            $file_srl = Context::get('file_srl');
            if(!$file_srl) return;

            $oFileModel = &getModel('file');
            $file_info = $oFileModel->getFile($file_srl);
            if($file_info->file_srl != $file_srl) return;

            $module_srl = $file_info->module_srl;

            // 포인트를 구해옴
            $point = $config->module_point[$module_srl]['download_file'];
            if($point == null) $point = $config->download_file;

            // 포인트가 0보다 작고 포인트가 없으면 파일 다운로드가 안되도록 했다면 오류
            if($cur_point + $point < 0 && $config->disable_download == 'Y') {
                $this->stop('msg_cannot_download');
            } else {
                // 포인트 차감
                $cur_point += $point;
                $oPointController = &getController('point');
                $oPointController->setPoint($member_srl,$cur_point);
            }

        // 글 삭제일 경우 대상 글의 사용자 번호 저장
        } elseif(strpos($config->delete_document_act,$this->act)!==false) {
            $document_srl = Context::get('document_srl');
            $oDocumentModel = &getModel('document');
            $oDocument = $oDocumentModel->getDocument($document_srl);
            $target_member_srl = $oDocument->get('member_srl');
            if($target_member_srl) Context::set('_point_target_member_srl', $target_member_srl);

        // 댓글 삭제일 경우 대상 댓글의 사용자 번호 저장
        } elseif(strpos($config->delete_comment_act,$this->act)!==false) {
            $comment_srl = Context::get('comment_srl');
            $oCommentModel = &getModel('comment');
            $comment = $oCommentModel->getComment($comment_srl);
            $target_member_srl = $comment->member_srl;
            if($target_member_srl) Context::set('_point_target_member_srl', $target_member_srl);

        // 파일삭제일 경우 대상 파일의 정보에서 사용자 번호 저장
        } elseif(strpos($config->delete_file_act,$this->act)!==false) {
            // 파일 정보를 구해옴
            $file_srl = Context::get('file_srl');
            if(!$file_srl) return;

            $oFileModel = &getModel('file');
            $file_info = $oFileModel->getFile($file_srl);
            if($file_info->file_srl != $file_srl) return;

            $target_member_srl = $file_info->member_srl;
            if($target_member_srl) Context::set('_point_target_member_srl', $target_member_srl);
        }
    }
?>
