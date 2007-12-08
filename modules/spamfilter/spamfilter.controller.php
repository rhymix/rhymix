<?php
    /**
     * @class  spamfilterController
     * @author zero (zero@nzeo.com)
     * @brief  spamfilter 모듈의 controller class
     **/

    class spamfilterController extends spamfilter {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 글 작성시 글 작성 시간 체크 및 금지 ip/단어 처리 루틴
         **/
        function triggerinsertDocument(&$obj) {
            // 로그인 여부, 로그인 정보, 권한 유무 체크
            $is_logged = Context::get('is_logged');
            $logged_info = Context::get('logged_info');
            $grant = Context::get('grant');

            // 로그인 되어 있을 경우 관리자 여부를 체크
            if($is_logged) {
                if($logged_info->is_admin == 'Y') return new Object();
                if($grant->manager) return new Object();
            }

            $oFilterModel = &getModel('spamfilter');

            // ip가 금지되어 있는 경우를 체크
            $output = $oFilterModel->isDeniedIP();
            if(!$output->toBool()) return $output;

            // 금지 단어에 있을 경우 체크
            $text = $obj->title.$obj->content;
            $output = $oFilterModel->isDeniedWord($text);
            if(!$output->toBool()) return $output;

            // 지정된 시간 체크
            $output = $oFilterModel->checkLimited();
            if(!$output->toBool()) return $output;

            // 로그 남김
            $this->insertLog();

            return new Object();
        }

        /**
         * @brief 댓글 작성 시간 및 금지 ip/ 단어 처리 루틴
         **/
        function triggerInsertComment(&$obj) {
            // 로그인 여부, 로그인 정보, 권한 유무 체크
            $is_logged = Context::get('is_logged');
            $logged_info = Context::get('logged_info');
            $grant = Context::get('grant');

            // 로그인 되어 있을 경우 관리자 여부를 체크
            if($is_logged) {
                if($logged_info->is_admin == 'Y') return new Object();
                if($grant->manager) return new Object();
            }

            $oFilterModel = &getModel('spamfilter');

            // ip가 금지되어 있는 경우를 체크
            $output = $oFilterModel->isDeniedIP();
            if(!$output->toBool()) return $output;

            // 금지 단어에 있을 경우 체크
            $text = $obj->content;
            $output = $oFilterModel->isDeniedWord($text);
            if(!$output->toBool()) return $output;

            // 지정된 시간 체크
            $output = $oFilterModel->checkLimited();
            if(!$output->toBool()) return $output;

            // 로그 남김
            $this->insertLog();

            return new Object();
        }

        /**
         * @brief 엮인글 작성시 시간 및 ip 검사
         **/
        function triggerInsertTrackback(&$obj) {
            $oFilterModel = &getModel('spamfilter');

            // 해당 글에 엮인글을 한번 이상 추가하였는지를 확인
            $output = $oFilterModel->isInsertedTrackback($obj->document_srl);
            if(!$output->toBool()) return $output;
            
            // ip가 금지되어 있는 경우를 체크
            $output = $oFilterModel->isDeniedIP();
            if(!$output->toBool()) return $output;

            // 금지 단어에 있을 경우 체크
            $text = $obj->blog_name.$obj->title.$obj->excerpt.$obj->url;
            $output = $oFilterModel->isDeniedWord($text);
            if(!$output->toBool()) return $output;

            // 3분 이내에 1개 이상의 한 C클래스의 ip에서 엮인글 등록 시도시 금지 아이피로 지정하고 해당 ip의 글을 모두 삭제
            $oTrackbackModel = &getModel('trackback');
            list($ipA,$ipB,$ipC,$ipD) = explode('.',$_SERVER['REMOTE_ADDR']);
            $ipaddress = $ipA.'.'.$ipB.'.'.$ipC;
            $count = $oTrackbackModel->getRegistedTrackback(3*60, $ipaddress, $obj->url, $obj->blog_name, $obj->title, $obj->excerpt);
            if($count > 1) {
                $oTrackbackController = &getController('trackback');
                $oTrackbackController->deleteTrackbackSender(3*60, $ipaddress, $obj->url, $obj->blog_name, $obj->title, $obj->excerpt);
                $this->insertIP($ipaddress.'.*');
                return new Object(-1,'msg_alert_trackback_denied');
            }

            return new Object();
        }

        /**
         * @brief IP 등록
         * 등록된 IP는 스패머로 간주
         **/
        function insertIP($ipaddress) {
            $args->ipaddress = $ipaddress;
            return executeQuery('spamfilter.insertDeniedIP', $args);
        }

        /**
         * @brief 로그 등록
         * 현 접속 IP를 로그에 등록, 로그의 간격이 특정 시간 이내일 경우 도배로 간주하여
         * 스패머로 등록할 수 있음
         **/
        function insertLog() {
            $output = executeQuery('spamfilter.insertLog');
            return $output;
        }
    }
?>
