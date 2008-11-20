<?php
    /**
     * @class  planetController
     * @author sol (sol@ngleader.com)
     * @brief  planet 모듈의 Controller class
     **/

    class planetController extends planet {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 플래닛 생성
         **/
        function procPlanetCreate() {
            if(!Context::get('is_logged')) return new Object(-1,'msg_not_logged');
            if(!$this->grant->create) return new Object(-1,'msg_not_permitted');

            $oPlanetModel = &getModel('planet');
            $myplanet = $oPlanetModel->getMemberPlanet();
            if($myplanet->isExists()) return new Object(-1,'msg_planet_exists');

            $args->planet_title = Context::get("planet_title");
            $args->browser_title = Context::get("browser_title");
            $args->mid = Context::get("planet_mid");
            $args->tag = Context::get("tag");

        if(in_array($args->mid, array('www','naver','hangame','promotion','notice','group','team','center','division','tf','faq','question','uit'))) return new Object(-1,'msg_not_permitted');

            $output = $this->insertPlanet($args);
            if(!$output->toBool()) return $output;

            $this->setError($output->getError());
            $this->setMessage($output->getMessage());
            $this->add('mid', $args->mid);
            $this->add('mid_url', getUrl('','mid',$args->mid));
        }

        /**
         * @brief 플래닛 사진 업로드
         **/
        function procPlanetPhotoModify() {
            if(!Context::isUploaded()) exit();

            $photo = Context::get('photo');
            if(!is_uploaded_file($photo['tmp_name'])) exit();

            $oPlanetModel = &getModel('planet');
            $planet = $oPlanetModel->getMemberPlanet();
            if($planet->isExists()) $url = getUrl('','mid',$planet->getMid());
            else {
                $config = $oPlanetModel->getPlanetConfig();
                $url = getUrl('','mid',$config->mid);
            }
            Context::set('url',$url);

            $this->insertPlanetPhoto($planet->getModuleSrl(), $photo['tmp_name']);

            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('move_myplanet');
        }


        /**
         * @brief 플래닛 컬러셋 변경
         **/
        function procPlanetColorsetModify() {
            $oPlanetModel = &getModel('planet');
            $myplanet = $oPlanetModel->getMemberPlanet();
            if(!$myplanet->isExists()) return new Object(-1, 'msg_not_permitted');

            $colorset = Context::get('colorset');
            if(!$colorset) return new Object(-1,'msg_invalid_request');

            $this->updatePlanetColorset($myplanet->getModuleSrl(), $colorset);

            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('move_myplanet');
        }

        /**
         * @brief 회원 - 플래닛 글 등록
         * 새글 등록. document 모듈을 이용
         **/

        function procPlanetContentWrite() {

            $oPlanetModel = &getModel('planet');
            $myplanet = $oPlanetModel->getMemberPlanet();
            if(!$myplanet->isExists()) return new Object(-1, 'msg_not_permitted');
            if($this->module_srl != $myplanet->getModuleSrl()) return new Object(-1, 'msg_not_permitted');

            $obj = Context::getRequestVars();
            $obj->module_srl = $myplanet->module_srl;

            $output = $this->insertContent($obj);

            // 오류 발생시 멈춤
            if(!$output->toBool()) return $output;

            // me2day연동 처리
            if(Context::get('me2day_autopush')=='Y') {
                $content = Context::get('content');
                $tags = Context::get('tags');
                $postscript = Context::get('extra_vars20');
                if($postscript) $content .= " (".$postscript.")";
                if($tags) $tags = str_replace(',',' ',str_replace(' ','',$tags));
                $this->doPostToMe2day($myplanet->getMe2dayUID(), $myplanet->getMe2dayUKey(), $content, $tags);
            }

            // 결과를 리턴
            $this->add('mid', Context::get('mid'));
            $this->add('document_srl', $output->get('document_srl'));

            // 성공 메세지 등록
            $this->setMessage('success_registed');
        }


        function insertContent($obj,$manual_inserted=false){

            $obj->content = str_replace(array('<','>'),array('&lt;','&gt;'),$obj->content);
            $obj->content = str_replace('...', '…', $obj->content);
            $obj->content = str_replace('--', '—', $obj->content);
            $obj->content = preg_replace('/"([^"]*)":(http|ftp|https|mms)([^ ]+)/is','<a href="$2$3" onclick="window.open(this.href);return false;">$1</a>$4', $obj->content);
            $oDocumentController = &getController('document');
            $output = $oDocumentController->insertDocument($obj,$manual_inserted);
            if(!$output->toBool()) return $output;
            $planet_args->latest_document_srl = $output->get('document_srl');
            $planet_args->module_srl = $obj->module_srl;
            $output = executeQuery('planet.updatePlanetLatestDocument', $planet_args);

            return $output;
        }

        /**
         * @brief 컨텐츠의 태그 수정
         **/
        function procPlanetContentTagModify(){

            $req = Context::getRequestVars();

             // document module의 model 객체 생성
            $oDocumentModel = &getModel('document');

            // document module의 controller 객체 생성
            $oDocumentController = &getController('document');
            $oDocument = $oDocumentModel->getDocument($req->document_srl);
            $oDocument->add('tags',$req->planet_content_tag);
            $obj = $oDocument->getObjectVars();

            $output = $oDocumentController->updateDocument($oDocument, $obj);
            $this->setMessage('success_updated');
        }

        /**
         * @brief 타인의 플래닛에 메모 추가
         **/
        function procPlanetInsertMemo() {
            $planet_memo = trim(Context::get('planet_memo'));

            if(!$planet_memo) return new Object(-1,'msg_invalid_request');
            if(!Context::get('is_logged')) return new Object(-1,'msg_not_permitted');

            $oPlanetModel = &getModel('planet');
            $myplanet =  $oPlanetModel->getMemberPlanet();

            // 대상 플래닛의 존재 유무 점검
            $planet = $oPlanetModel->getPlanet($this->module_srl);
            if(!$planet->isExists()) return new Object(-1,'msg_invalid_request');

            // 현재 접속자의 플래닛 점검
            if(!$myplanet->isExists()) return new Object(-1,'msg_not_permitted');

            // 메모 등록
            $output = $this->insertMemo($this->module_srl, $myplanet->getModuleSrl(), $planet_memo);
            if(!$output->toBool()) return $output;

            // 가장 최신 페이지 추출하여 tpl로 return
            $this->add('tpl', $oPlanetModel->getMemoHtml($this->module_srl, 1));
        }

        /**
         * @brief 메모 삭제
         **/
        function procPlanetDeleteMemo() {
            $planet_memo_srl = trim(Context::get("planet_memo_srl"));
            if(!$planet_memo_srl) return new Object(-1,'msg_invalid_request');

            $args->planet_memo_srl = $planet_memo_srl;
            $output = executeQuery('planet.getPlanetMemo', $args);
            if(!$output->toBool()) return $output;
            $memo = $output->data;

            if(!$output->data) return new Object(-1,'msg_invalid_request');

            $oPlanetModel = &getModel('planet');
            $myplanet =  $oPlanetModel->getMemberPlanet();
            if(!$myplanet->isExists()) return new Object(-1,'msg_invalid_request');
            Context::set('myplanet', $myplanet);

            $planet = $oPlanetModel->getPlanet($memo->module_srl);
            if(!$planet->isExists()) return new Object(-1,'msg_invalid_request');
            Context::set('planet', $planet);

            // 내플래닛인지 아닌지
            $logged_info = Context::get('logged_info');
            Context::set('isMyPlanet', $planet->getMemberSrl() == $logged_info->member_srl);

            if($planet->getModuleSrl() != $memo->module_srl && $myplanet->getModuleSrl() != $memo->write_planet_srl) return new Object(-1,'msg_not_permitted');

            $this->deleteMemo($planet_memo_srl);

            // 가장 최신 페이지 추출하여 tpl로 return
            $this->add('tpl', $oPlanetModel->getMemoHtml($memo->module_srl, 1));
        }

        /**
         * @brief 플래닛 기본 설정 저장
         * 플래닛의 전체 설정은 module config를 이용해서 저장함
         * 대상 : 기본 플래닛 스킨, 권한, 스킨 정보
         **/
        function insertPlanetConfig($planet) {
            $oModuleController = &getController('module');
            $oModuleController->insertModuleConfig('planet', $planet);
        }

        /**
         * @brief 플래닛 생성
         * 플래닛은 modules 테이블에 기본적인 정보(mid, browser_title)을 입력하고 planet테이블에 플래닛 개설자 정보를 매핑한다
         **/
        function insertPlanet($planet, $member_srl = 0) {
            $planet->module = 'planet';
            $planet->module_srl = getNextSequence();

            $oMemberModel = &getModel('member');
            $oModuleController = &getController('module');
            $output = $oModuleController->insertModule($planet);
            if(!$output->toBool()) return $output;

            if(!$member_srl) $member_info = Context::get('logged_info');
            else $member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);

            $args->planet_title = $planet->planet_title;
            $args->module_srl = $output->get('module_srl');
            $args->member_srl = $member_info->member_srl;
            $args->close_notice = 'N';
            $output = executeQuery('planet.insertPlanet', $args);
            if(!$output->toBool()) return $output;

            if($planet->tag) {
                $tmp_arr = explode(",",trim($planet->tag));
                $tag_list = null;
                for($i=0;$i<count($tmp_arr);$i++) {
                    $tag = trim($tmp_arr[$i]);
                    if(!$tag) continue;
                    unset($tag_args);
                    $tag_args->module_srl = $args->module_srl;
                    $tag_args->tag = $tag;
                    executeQuery('planet.insertPlanetTag', $tag_args);
                }
            }

            $output->add('module_srl', $planet->module_srl);
            return $output;
        }

        /**
         * @brief 플래닛 수정
         * 플래닛의 기본 정보를 수정
         **/
        function updatePlanet($planet) {
            $oModuleController = &getController('module');
            $output = $oModuleController->updateModule($planet);
            $output->add('module_srl', $planet->module_srl);
            return $output;
        }

        /**
         * @brief 플래닛 이미지 등록
         **/
        function insertPlanetPhoto($module_srl, $source) {
            $oPlanetModel = &getModel('planet');
            $path = $oPlanetModel->getPlanetPhotoPath($module_srl);
            if(!is_dir($path)) FileHandler::makeDir($path);

            $filename = sprintf('%s/%d.jpg', $path, $module_srl);

            FileHandler::createImageFile($source, $filename, 96, 96, 'jpg', 'crop');
        }

        /**
         * @brief 회원 - 플래닛 브라우져 제목 수정
         * 플래닛의 제목은 modules테이블의 browser_title컬럼을 이용한다
         **/
        function updatePlanetBrowserTitle($module_srl, $browser_title) {
            $args->module_srl = $module_srl;
            $args->browser_title = $browser_title;
            return executeQuery('planet.updatePlanetBrowserTitle', $args);
        }

        /**
         * @brief 회원 - 플래닛 컬러셋 변경
         **/
        function updatePlanetColorset($module_srl, $colorset) {
            $args->module_srl = $module_srl;
            $args->colorset = $colorset;
            $logged_info = Context::get('logged_info');
            $args->member_srl = $logged_info->member_srl;
            return executeQuery('planet.updatePlanetColorset', $args);
        }

        /**
         * @brief 회원 - 플래닛 제목 수정
         * 플래닛의 제목은 planet테이블의 planet_title컬럼을 이용한다
         **/
        function updatePlanetTitle($module_srl, $planet_title) {
            $args->module_srl = $module_srl;
            $args->planet_title = $planet_title;
            $logged_info = Context::get('logged_info');
            $args->member_srl = $logged_info->member_srl;
            return executeQuery('planet.updatePlanetTitle', $args);
        }

        /**
         * @brief 회원 - 플래닛 소개 수정
         * 플래닛의 소개는 modules 테이블의 description컬럼을 이용한다.
         **/
        function updatePlanetIntro() {
        }

        /**
         * @brief 회원 - 플래닛 인물 태그 수정
         * 플래닛의 인물 태그 수정
         **/
        function updatePlanetTag($module_srl,$arrTags) {
            $arrAddTags = array();
            $arrDeleteTags = array();
            $oPlanetModel = &getModel('planet');
            $output = $oPlanetModel->getPlanetTags($module_srl);

            $args->module_srl = $module_srl;
            executeQuery('planet.deletePlanetTags', $args);

            if(count($arrTags)) {
                $arrTags = array_unique($arrTags);
                foreach($arrTags as $tag){
                    if(strlen($tag) > 0){
                        unset($tag_args);
                        $tag_args->module_srl = $module_srl;
                        $tag_args->tag = $tag;
                        executeQuery('planet.insertPlanetTag', $tag_args);
                    }
                }
            }
        }

        /**
         * @brief 회원 - 플래닛에 메모 추가
         * 다른 회원들이 타회원의 플래닛에 메모를 추가
         **/
        function insertMemo($module_srl, $write_planet_srl, $memo_content) {
            $args->module_srl = $module_srl;
            $args->write_planet_srl = $write_planet_srl;
            $args->memo_content = $memo_content;
            return executeQuery('planet.insertPlanetMemo', $args);
        }

        /**
         * @brief 회원 - 플래닛 메모 삭제
         **/
        function deleteMemo($planet_memo_srl) {
            $args->planet_memo_srl = $planet_memo_srl;
            $output = executeQuery('planet.deletePlanetMemo', $args);
            return $output;
        }

        /**
         * @brief 회원 - 즐찾 플래닛 추가
         * 플래닛이 있는 사용자만 즐찾 플래닛을 추가할 수 있다
         **/
        function addFavoritePlanet() {
            return executeQuery('planet.insertPlanetFavorite', $args);
        }

        /**
         * @brief 회원 - 즐찾 플래닛 제거
         **/
        function removeFavoritePlanet() {
            return executeQuery('planet.deletePlanetFavorite', $args);
        }

        /**
         * @brief 회원 - 플래닛 이미지 수정
         * 플래닛에 표시되는 이미지를 수정
         **/
        function updatePlanetPhoto() {
        }

        /**
         * @brief 회원 - 플래닛 welcome 메세지 표시여부
         **/
        function procNotReadWelcome(){
            if(!Context::get('is_logged')) return new Object(-1,'msg_not_logged');
            if(!$this->grant->create) return new Object(-1,'msg_not_permitted');

            $args->close_notice = 'Y';
            $args->module_srl = Context::get('module_srl');

            $logged_info = Context::get('logged_info');
            $args->member_srl = $logged_info->member_srl;
            $output = executeQuery('planet.updateShowReadWelcome', $args);

            return $output;
        }


        /**
         * @brief 회원 - 플래닛 정보 수정
         * 플래닛정보수정
         **/
        function procPlanetInfoModify(){
            $target = Context::get('target');

            $oPlanetModel = &getModel('planet');
            $myplanet = $oPlanetModel->getMemberPlanet();
            $planet = $oPlanetModel->getPlanet($this->module_srl);
            if($myplanet->getModuleSrl()!=$planet->getModuleSrl()) return new ObjecT(-1,'msg_not_permitted');

            switch($target){
                case 'planet_tag':
                        $planet_tag = Context::get('planet_tag');
                        $planet_tag = explode(',',$planet_tag);
                        foreach($planet_tag as $v) $v = trim($v);

                        $this->updatePlanetTag($myplanet->getModuleSrl(),$planet_tag);
                    break;
                case 'planet_name':
                        $planet_name = Context::get('planet_name');
                        return $this->updatePlanetTitle($myplanet->getModuleSrl(), $planet_name);
                    break;
                case 'browser_title':
                        $browser_title = Context::get('browser_title');
                        return $this->updatePlanetBrowserTitle($myplanet->getModuleSrl(), $browser_title);
                    break;
                case 'planet_info_photo':
                    break;
                default:
            }

        }

        /**
         * @brief 회원 - 플래닛 글에 추천
         **/
        function procPlanetVoteContent(){
            $document_srl = Context::get('document_srl');
            $oDocumentController = &getController('document');
            return $oDocumentController->updateVotedCount($document_srl);
        }

        /**
         * @brief 관심태그 추가
         **/
        function procPlanetInsertInterestTag() {
            $tag = trim(Context::get('tag'));
            if(!$tag) return new Object(-1,'msg_invalid_request');

            $oPlanetModel = &getModel('planet');
            $myplanet = $oPlanetModel->getMemberPlanet();
            if(!$myplanet->isExists()) return new Object(-1,'msg_invalid_request');

            $interest_tags = $oPlanetModel->getInterestTags($myplanet->getModuleSrl());
            if(in_array($tag, $interest_tags)) return new Object(-1,'msg_planet_already_added_favorite');

            $args->module_srl = $myplanet->getModuleSrl();
            $args->tag = $tag;
            $output = executeQuery('planet.insertInterestTag', $args);
            if(!$output->toBool()) return $output;

            $this->add('tpl', $oPlanetModel->getInterestTagsHtml($myplanet->getModuleSrl()));
        }

        /**
         * @brief 관심태그 삭제
         **/
        function procPlanetDeleteInterestTag() {
            $tag = trim(Context::get('tag'));
            if(!$tag) return new Object(-1,'msg_invalid_request');

            $oPlanetModel = &getModel('planet');
            $myplanet = $oPlanetModel->getMemberPlanet();
            if(!$myplanet->isExists()) return new Object(-1,'msg_invalid_request');

            $args->module_srl = $myplanet->getModuleSrl();
            $args->tag = $tag;
            $output = executeQuery('planet.deleteInterestTag', $args);
            if(!$output->toBool()) return $output;

            $this->add('tpl', $oPlanetModel->getInterestTagsHtml($myplanet->getModuleSrl()));
        }

        /**
         * @brief 즐겨찾기추가
         **/
        function procPlanetInsertFavorite() {
            $module_srl = Context::get('module_srl');
            if(!$module_srl) return new Object(-1,'msg_invalid_request');

            $oPlanetModel = &getModel('planet');
            $myplanet = $oPlanetModel->getMemberPlanet();
            if(!$myplanet->isExists()) return new Object(-1,'msg_invalid_request');

            if($myplanet->getModuleSrl() == $module_srl) return new Object(-1,'msg_invalid_request');

            if($oPlanetModel->isInsertedFavorite($myplanet->getModuleSrl(), $module_srl)) return new Object(-1,'msg_planet_already_added_favorite');

            $args->list_order = $args->planet_favorite_srl = getNextSequence();
            $args->module_srl = $myplanet->getModuleSrl();
            $args->reg_planet_srl = $module_srl;
            return executeQuery('planet.insertPlanetFavorite', $args);
        }

        /**
         * @brief 회원 - 플래닛에 댓글 추가
         *
         **/
        function procPlanetReplyWrite() {

            // 권한 체크
            // 댓글 입력에 필요한 데이터 추출
            $req = Context::gets('document_srl','planet_reply_content');
            $obj->module_srl = $this->module_srl;
            $obj->document_srl = $req->document_srl;
            $obj->content = $req->planet_reply_content;


            // 원글이 존재하는지 체크
            $oDocumentModel = &getModel('document');
            $oDocument = $oDocumentModel->getDocument($obj->document_srl);
            if(!$oDocument->isExists()) return new Object(-1,'msg_not_permitted');

            // comment 모듈의 model 객체 생성
            $oCommentModel = &getModel('comment');

            // comment 모듈의 controller 객체 생성
            $oCommentController = &getController('comment');

            $obj->comment_srl = getNextSequence();
            $output = $oCommentController->insertComment($obj);

            if(!$output->toBool()) return $output;

            // notice 남김
            $logged_info = Context::get('logged_info');
            if($oDocument->get('member_srl') != $logged_info->member_srl) {
                $h_args->module_srl = $obj->module_srl;
                $h_args->document_srl = $obj->document_srl;
                $h_args->list_order = -1*$obj->comment_srl;
                $checkOutput = executeQuery('planet.getCatch', $h_args);
                if($checkOutput->data->count) executeQuery('planet.deleteCatch', $h_args);
                executeQuery('planet.insertCatch', $h_args);
            }

            $this->setMessage('success_registed');
            $this->add('mid', Context::get('mid'));
            $this->add('document_srl', $obj->document_srl);
            $this->add('comment_srl', $obj->comment_srl);
        }

        function procPlanetEnableRss() {
            $oPlanetModel = &getModel('planet');
            $myplanet = $oPlanetModel->getMemberPlanet();
            if(!$myplanet->isExists()) return new Object(-1,'msg_not_permitted');

            $oRssAdminController = &getAdminController('rss');
            $oRssAdminController->setRssModuleConfig($myplanet->getModuleSrl(), 'Y');
        }

        function procPlanetDisableRss() {
            $oPlanetModel = &getModel('planet');
            $myplanet = $oPlanetModel->getMemberPlanet();
            if(!$myplanet->isExists()) return new Object(-1,'msg_not_permitted');

            $oRssAdminController = &getAdminController('rss');
            $oRssAdminController->setRssModuleConfig($myplanet->getModuleSrl(), 'N');
        }

        function procPlanetMe2dayApi() {
            $oPlanetModel = &getModel('planet');
            $myplanet = $oPlanetModel->getMemberPlanet();
            if(!$myplanet->isExists()) return new Object(-1,'msg_not_permitted');

            $args = Context::gets('me2day_id','me2day_ukey','me2day_autopush');
            if(!$args->me2day_autopush) $args->me2day_autopush = 'N';

            $output = $this->doValidateMe2dayInfo($args->me2day_id, $args->me2day_ukey);
            if(!$output->toBool()) return $output;

            $args->module_srl = $myplanet->getModuleSrl();
            $args->member_srl = $myplanet->getMemberSrl();
            $output = executeQuery('planet.updatePlanetMe2day', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage('msg_success_auth_me2day');
        }

        function doValidateMe2dayInfo($user_id, $user_key) {
            require_once($this->module_path.'libs/me2day.api.php');
            $oMe2 = new me2api($user_id, $user_key);
            return $oMe2->chkNoop($user_id, $user_key);
        }

        function doPostToMe2day($user_id, $user_key, $body, $tags) {
            require_once($this->module_path.'libs/me2day.api.php');
            $oMe2 = new me2api($user_id, $user_key);
            return $oMe2->doPost($body, $tags);
        }



        /**
         * @brief SMS를 받는다
         *
         **/
        function procPlanetInsertSMS(){

            $phone_number = Context::get('phone_number');
            $message = Context::get('message');

            $message = Context::convertEncodingStr($message);

            //@골뱅이를 빼자
            if(substr($message,0,1)=='@') $message = substr($message,1);

            $args->phone_number = $phone_number;
            $oPlanetModel = &getModel('planet');
            $output = $oPlanetModel->getSMSUser($args);

            // SMS 사용자가 있으면 해당 planet에 등록
            if($output->data){

                $args->content = $message;
                $args->module_srl = $output->data->module_srl;
                $args->member_srl = $output->data->member_srl;

                $oMemberModel = &getModel('member');
                $output = $oMemberModel->getMemberInfoByMemberSrl($args->member_srl);

                $args->user_id = $output->user_id;
                $args->user_name = $output->user_name;
                $args->nick_name = $output->nick_name;
                $args->email_address = $output->email_address;
                $args->homepage = $output->homepage;
               
                $config = $oPlanetModel->getPlanetConfig();
                $args->tags = join(',',$config->smstag);

                $manual_inserted = true;
                $output = $this->insertContent($args,$manual_inserted);

            }else{
               // SMS 사용자가 아니라면 planet_sms_resv에 쌓는다
                $output = $this->insertSMSRecv($phone_number,$message);
            }


            if($output->toBool()){
                header("X-SMSMORESPONSE:0");
            }else{
                header("X-SMSMORESPONSE:1");
            }

            // response를 XMLRPC로 변환
            Context::setResponseMethod('XMLRPC');

            return $output;
        }


        function insertSMSRecv($phone_number,$message){
            $args->phone_number = $phone_number;
            $args->message = $message;
            $output = executeQuery('planet.insertSMSRecv', $args);
            return $output;
        }


        /**
         * @brief SMS를 위한 핸드폰 번호를 셋팅한다
         **/
        function procPlanetSetSMS(){

            // is login?
            if(!Context::get('is_logged')) return new Object(-1,'msg_not_logged');

            $phone_number = Context::get('phone_number');
            if(!$phone_number) return new Object(-1,'error');

            $oPlanetModel = &getModel('planet');
            $planet = $oPlanetModel->getMemberPlanet();
            $args->phone_number = $phone_number;


            // dont have planet!
            if(!$planet->isExists()) return new Object(-1,'error');

            $output = $oPlanetModel->getSMSUser($args);
            if($output->data) return new Object(-1,'msg_already_have_phone_number');

            $mid = $planet->getMid();
            $oModuleModel = &getModel('module');
            $output = $oModuleModel->getModuleInfoByMid($mid);
            $args->module_srl = $output->module_srl;

            // SMSUser에 이미 있다면 지워준다
            $this->removeSMSUser($args->module_srl);

            $logged_info = Context::get('logged_info');
            $args->member_srl = $logged_info->member_srl;

            $output = executeQuery('planet.insertSMSUser', $args);
            if(!$output->toBool()) return $output;

            // 이미 받아놓은 메세지들을 가져와 planet에 넣자
            $oPlanetModel = &getModel('planet');
            $output = $oPlanetModel->getSMSRecv($phone_number);
            if($output->data && is_array($output->data)){
                $config = $oPlanetModel->getPlanetConfig();
                $smstag = join(',',$config->smstag);
                for($i=0,$c=count($output->data);$i<$c;$i++){
                    unset($obj);
                    $obj->content = $output->data[$i]->message;
                    $obj->module_srl = $args->module_srl;
                    $args->tags = $smstag;

                    $this->insertContent($obj);
                }
                $this->removeSMSRecv($phone_number);
            }

            $this->setMessage('msg_success_set_phone_number');
        }

        function removeSMSRecv($phone_number){
            $args->phone_number = $phone_number;
            $output = executeQuery('planet.deleteSMSRecv', $args);
            return $output;
        }

        function removeSMSUser($module_srl){
            $args->module_srl = $module_srl;
            $output = executeQuery('planet.deleteSMSUser', $args);
            return $output;

        }
    }

?>
