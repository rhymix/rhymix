<?php
    if(!__ZB5__) exit();

    /**
    * @file spam_filter.addon.php
    * @author zero (zero@nzeo.com)
    * @brief 스팸필터링 애드온
    * @todo 미구현
    *
    * addOn은 ModuleObject 에서 모듈이 불러지기 전/후에 include되는 것으로 실행을 한다.
    * 즉 별도의 interface가 필요한 것이 아니고 모듈의 일부라고 판단하여 코드를 작성하면 된다.
    **/

    $ipaddress = $_SERVER['REMOTE_ADDR'];

    $effecived_target = array(
        'board' => array('procInsertDocument', 'procInsertComment', 'procReceiveTrackback'),
    );

    // point가 before일때만 실행
    if($this->point != 'before') return;

    // 현재 모듈의 관리자이거나 그에 준하는 manager권한이면 그냥 패스~
    if($this->grant->is_admin || $this->grant->manager) return;

    // spam filter모듈이 적용될 module+act를 체크
    if(!in_array($this->act, $effecived_target[$this->module])) return;

    // spamfilter 모듈 객체 생성
    $oSpamFilterController = &getController('spamfilter');
    $oSpamFilterModel = &getModel('spamfilter');

    // 스팸필터 기본 설정 출력
    $config = $oSpamFilterModel->getConfig();

    // 스팸 간격을 체크하는 변수
    $interval = $config->interval?$config->interval:60;
    $limit_count = $config->limit_count?$config->limit_count:5;

    // 스팸 IP에 등록되어 있는지 체크하여 등록되어 있으면 return
    $is_denied = $oSpamFilterModel->isDeniedIP($ipaddress);
    if($is_denied) {
        $output = new Object(-1, 'msg_alert_registered_denied_ip');
        $this->stop_proc = true;
        return;
    }

    // 정해진 시간내에 글 작성 시도를 하였는지 체크
    $count = $oSpamFilterModel->getLogCount($interval, $ipaddress);

    // 정해진 시간내에 정해진 글의 수를 초과시 스팸 IP로 등록시킴
    if($count>=$limit_count) {
        $oSpamFilterController->insertIP($ipaddress);
        $output = new Object(-1, 'msg_alert_registered_denied_ip');
        $this->stop_proc = true;
        return;

    // 제한 글수까지는 아니지만 정해진 시간내에 글 작성을 계속 할때
    } elseif($count) {
        $message = sprintf(Context::getLang('msg_alert_limited_by_config'), $interval);
        $output = new Object(-1, $message);
        $this->stop_proc = true;
    }

    // 금지 단어 체크를 위해서 몇가지 지정된 변수들을 한데 묶음 
    $check_vars = implode("\n",get_object_vars(Context::getRequestVars()));

    // 금지 단어를 이용하여 본문 내용을 체크
    $denied_word_list = $oSpamFilterModel->getDeniedWordList();
    $denied_word_count = count($denied_word_list);
    if($denied_word_count>0) {
        for($i=0;$i<$denied_word_count;$i++) {
            $word = preg_quote($denied_word_list[$i]->word,'/');
            if(preg_match('/'.$word.'/i', $check_vars)) {
                $message = sprintf(Context::getLang('msg_alert_denied_word'), $word);
                $output = new Object(-1, $message);
                $this->stop_proc = true;
                return;
            }
        }
    }

    // 로그를 남김
    $oSpamFilterController->insertLog();
?>
